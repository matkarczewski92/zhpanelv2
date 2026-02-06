<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\EwelinkDevice;
use App\Services\Ewelink\EwelinkCloudClient;
use App\Services\Ewelink\EwelinkDeviceDataFormatter;
use App\Services\Ewelink\EwelinkDeviceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class DevicesController extends Controller
{
    public function __construct(
        private readonly EwelinkCloudClient $cloudClient,
        private readonly EwelinkDeviceSyncService $syncService,
        private readonly EwelinkDeviceDataFormatter $dataFormatter
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (trim((string) $request->query('code', '')) !== '') {
            return $this->handleOAuthCallback($request);
        }

        $rows = $this->buildRows();

        return view('panel.devices.index', [
            'rows' => $rows,
            'hasToken' => $this->cloudClient->hasSavedToken(),
            'savedRegion' => $this->cloudClient->getSavedRegion(),
        ]);
    }

    public function data(): JsonResponse
    {
        $warning = null;

        try {
            $this->syncWithAutoAuthorization();
        } catch (RuntimeException $exception) {
            $warning = $exception->getMessage();
        }

        $rows = $this->buildRows();
        $rowsHtml = view('panel.devices._rows', ['rows' => $rows])->render();

        return response()->json([
            'rows_html' => $rowsHtml,
            'warning' => $warning,
            'has_token' => $this->cloudClient->hasSavedToken(),
            'saved_region' => $this->cloudClient->getSavedRegion(),
            'server_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        return $this->handleOAuthCallback($request);
    }

    public function authorize(Request $request): RedirectResponse
    {
        $flow = trim((string) $request->query('flow', ''));

        try {
            $configuredState = trim((string) config('services.ewelink.oauth_state', ''));
            $state = $configuredState !== '' ? $configuredState : Str::random(32);
            $request->session()->put('ewelink.oauth_state', $state);
        } catch (RuntimeException $exception) {
            return redirect()->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        if ($flow !== 'oauth' && $this->cloudClient->hasCredentialAuthConfig()) {
            try {
                $this->cloudClient->authorizeWithCredentials($state);
                $result = $this->syncService->syncAll();

                return redirect()
                    ->route('panel.devices.index')
                    ->with('toast', [
                        'type' => 'success',
                        'message' => sprintf(
                            'Połączono konto eWeLink (backend). Zaktualizowano: %d/%d.',
                            $result['updated'],
                            $result['total']
                        ),
                    ]);
            } catch (RuntimeException $exception) {
                return redirect()->route('panel.devices.index')
                    ->with('toast', [
                        'type' => 'error',
                        'message' => 'Backend OAuth nie powiódł się: ' . $exception->getMessage(),
                    ]);
            }
        }

        try {
            return redirect()->away($this->cloudClient->buildAuthorizationUrl($state));
        } catch (RuntimeException $exception) {
            return redirect()->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    public function refresh(): RedirectResponse
    {
        try {
            $result = $this->syncWithAutoAuthorization();
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('panel.devices.index')
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

        return redirect()
            ->route('panel.devices.index')
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

    private function handleOAuthCallback(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Brak kodu OAuth w odpowiedzi eWeLink.']);
        }

        $returnedState = trim((string) $request->query('state', ''));
        $sessionState = trim((string) $request->session()->pull('ewelink.oauth_state', ''));
        $configuredState = trim((string) config('services.ewelink.oauth_state', ''));

        if ($sessionState !== '' && $returnedState !== $sessionState) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Niepoprawny parametr state w odpowiedzi OAuth eWeLink.']);
        }

        if ($sessionState === '' && $configuredState !== '' && $returnedState !== $configuredState) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Niepoprawny parametr state w odpowiedzi OAuth eWeLink.']);
        }

        $region = (string) ($request->query('region') ?: $request->query('regin') ?: config('services.ewelink.region', 'eu'));

        try {
            $this->cloudClient->exchangeCodeForToken($code, $region);
            $syncResult = $this->syncService->syncAll();

            $message = sprintf(
                'Połączono konto eWeLink. Zaktualizowano urządzenia: %d/%d.',
                $syncResult['updated'],
                $syncResult['total']
            );

            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'success', 'message' => $message]);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @return Collection<int, array{device:EwelinkDevice, snapshot:array<string, mixed>}>
     */
    private function buildRows(): Collection
    {
        return EwelinkDevice::query()
            ->orderBy('id')
            ->get()
            ->map(function (EwelinkDevice $device): array {
                return [
                    'device' => $device,
                    'snapshot' => $this->dataFormatter->formatForDevice($device),
                ];
            });
    }
}
