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
        $this->service->store($request->validated());

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urządzenie eWeLink dodane.']);
    }

    public function update(EwelinkDeviceRequest $request, EwelinkDevice $device): RedirectResponse
    {
        $this->service->update($device, $request->validated());

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urządzenie eWeLink zaktualizowane.']);
    }

    public function destroy(EwelinkDevice $device): RedirectResponse
    {
        $this->service->destroy($device);

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Urządzenie eWeLink usunięte.']);
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
            'Synchronizacja zakończona. Zaktualizowano: %d/%d, brak w chmurze: %d.',
            $result['updated'],
            $result['total'],
            $result['missing']
        );

        if ($result['errors'] > 0) {
            $message .= sprintf(' Ostrzeżenia: %d.', $result['errors']);
        }

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * @return array{total:int, updated:int, missing:int, errors:int}
     */
    private function syncWithAutoAuthorization(): array
    {
        $state = trim((string) config('services.ewelink.oauth_state', 'panel'));

        if (!$this->cloudClient->hasSavedToken() && $this->cloudClient->hasCredentialAuthConfig()) {
            $this->cloudClient->authorizeWithCredentials($state);
        }

        try {
            return $this->syncService->syncAll();
        } catch (RuntimeException $exception) {
            if (!$this->cloudClient->hasCredentialAuthConfig()) {
                throw $exception;
            }

            $this->cloudClient->authorizeWithCredentials($state);

            return $this->syncService->syncAll();
        }
    }

    private function redirectToTab(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'ewelink-devices']);
    }
}
