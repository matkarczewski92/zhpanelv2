<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\EwelinkDevice;
use App\Services\Ewelink\EwelinkCloudClient;
use App\Services\Ewelink\EwelinkDeviceDataFormatter;
use App\Services\Ewelink\EwelinkDeviceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function index(): View
    {
        $rows = EwelinkDevice::query()
            ->orderBy('id')
            ->get()
            ->map(function (EwelinkDevice $device): array {
                return [
                    'device' => $device,
                    'snapshot' => $this->dataFormatter->formatForDevice($device),
                ];
            });

        return view('panel.devices.index', [
            'rows' => $rows,
            'hasToken' => $this->cloudClient->hasSavedToken(),
            'savedRegion' => $this->cloudClient->getSavedRegion(),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        return $this->handleOAuthCallback($request);
    }

    public function authorize(Request $request): RedirectResponse
    {
        try {
            $configuredState = trim((string) config('services.ewelink.oauth_state', ''));
            $state = $configuredState !== '' ? $configuredState : Str::random(32);
            $request->session()->put('ewelink.oauth_state', $state);

            return redirect()->away($this->cloudClient->buildAuthorizationUrl($state));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    public function refresh(): RedirectResponse
    {
        try {
            $result = $this->syncService->syncAll();
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

        if ($sessionState === '' || $returnedState !== $sessionState) {
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
}
