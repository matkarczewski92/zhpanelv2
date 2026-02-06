<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\EwelinkDeviceRequest;
use App\Models\EwelinkDevice;
use App\Services\Admin\Settings\EwelinkDeviceService;
use App\Services\Ewelink\EwelinkCloudClient;
use App\Services\Ewelink\EwelinkDeviceSyncService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class EwelinkDeviceController extends Controller
{
    public function __construct(
        private readonly EwelinkDeviceService $service,
        private readonly EwelinkCloudClient $cloudClient,
        private readonly EwelinkDeviceSyncService $syncService
    ) {
    }

    public function store(EwelinkDeviceRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        try {
            $payload['name'] = $this->resolveDeviceNameFromCloud((string) ($payload['device_id'] ?? ''));
            $this->service->store($payload);
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urzadzenie eWeLink dodane.']);
    }

    public function update(EwelinkDeviceRequest $request, EwelinkDevice $device): RedirectResponse
    {
        $payload = $request->validated();
        $newDeviceId = trim((string) ($payload['device_id'] ?? $device->device_id));
        $newName = trim((string) ($payload['name'] ?? ''));
        $nameChanged = $newName !== '' && ($newName !== trim((string) $device->name) || $newDeviceId !== (string) $device->device_id);

        if ($nameChanged) {
            try {
                $this->runCloudOperation(function () use ($newDeviceId, $newName): void {
                    $this->cloudClient->updateDeviceInfo($newDeviceId, $newName);
                });
            } catch (RuntimeException $exception) {
                return $this->redirectToTab()->with('toast', [
                    'type' => 'error',
                    'message' => 'Nie udalo sie zaktualizowac nazwy w eWeLink: ' . $exception->getMessage(),
                ]);
            }
        }

        $this->service->update($device, $payload);

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urzadzenie eWeLink zaktualizowane.']);
    }

    public function destroy(EwelinkDevice $device): RedirectResponse
    {
        $this->service->destroy($device);

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urzadzenie eWeLink usuniete.']);
    }

    public function sync(): RedirectResponse
    {
        try {
            $result = $this->syncWithAutoAuthorization();
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $message = sprintf(
            'Synchronizacja zakonczona. Zaktualizowano: %d/%d, brak w chmurze: %d.',
            $result['updated'],
            $result['total'],
            $result['missing']
        );

        if ($result['errors'] > 0) {
            $message .= sprintf(' Ostrzezenia: %d.', $result['errors']);
        }

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * @return array{total:int, updated:int, missing:int, errors:int}
     */
    private function syncWithAutoAuthorization(): array
    {
        return $this->runCloudOperation(fn (): array => $this->syncService->syncAll());
    }

    private function resolveDeviceNameFromCloud(string $deviceId): string
    {
        $resolvedDeviceId = trim($deviceId);
        if ($resolvedDeviceId === '') {
            throw new RuntimeException('Brak device_id.');
        }

        $itemData = $this->runCloudOperation(
            fn (): ?array => $this->cloudClient->findThingByDeviceId($resolvedDeviceId)
        );

        if (!is_array($itemData)) {
            throw new RuntimeException(sprintf('Nie znaleziono urzadzenia %s na koncie eWeLink.', $resolvedDeviceId));
        }

        $name = trim((string) ($itemData['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException(sprintf('Urzadzenie %s nie ma nazwy w eWeLink.', $resolvedDeviceId));
        }

        return $name;
    }

    private function runCloudOperation(callable $callback): mixed
    {
        $state = trim((string) config('services.ewelink.oauth_state', 'panel'));

        if (!$this->cloudClient->hasSavedToken() && $this->cloudClient->hasCredentialAuthConfig()) {
            $this->cloudClient->authorizeWithCredentials($state);
        }

        try {
            return $callback();
        } catch (RuntimeException $exception) {
            if (!$this->cloudClient->hasCredentialAuthConfig()) {
                throw $exception;
            }

            $this->cloudClient->authorizeWithCredentials($state);

            return $callback();
        }
    }

    private function redirectToTab(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'ewelink-devices']);
    }
}
