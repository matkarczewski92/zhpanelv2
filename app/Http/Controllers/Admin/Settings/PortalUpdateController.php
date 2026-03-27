<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\PortalArtisanRunRequest;
use App\Http\Requests\Admin\Settings\PortalMaintenanceRequest;
use App\Http\Requests\Admin\Settings\PortalUpdateRunRequest;
use App\Services\Admin\Settings\PortalUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PortalUpdateController extends Controller
{
    public function __construct(private readonly PortalUpdateService $service)
    {
    }

    public function check(Request $request): RedirectResponse
    {
        try {
            $result = $this->service->checkForUpdates();
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $request->session()->put('admin_update_last_check', $result);

        $message = $result['has_updates']
            ? sprintf('Dostepna aktualizacja: %d commit(ow) do pobrania.', (int) $result['behind'])
            : 'Brak nowych commitow w repozytorium.';

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function run(PortalUpdateRunRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $runMigrate = (bool) ($payload['run_migrate'] ?? false);
        $runBuild = (bool) ($payload['run_build'] ?? false);
        $forceOverwrite = (bool) ($payload['force_overwrite'] ?? false);

        try {
            $result = $this->service->runUpdate($runMigrate, $runBuild, $forceOverwrite);
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->withInput()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $request->session()->put('admin_update_last_run', $result);

        if ($result['success']) {
            $message = $result['updated']
                ? sprintf('Aktualizacja zakonczona. Wersja: %s -> %s.', $result['before_sha_short'] ?? '-', $result['after_sha_short'] ?? '-')
                : 'Aktualizacja zakonczona bez zmiany commita (repo juz aktualne).';
            $type = 'success';
        } else {
            $message = 'Aktualizacja zakonczona bledem. Sprawdz log aktualizacji.';
            if (is_string($result['error']) && $result['error'] !== '') {
                $message .= ' ' . $result['error'];
            }
            $type = 'error';
        }

        return $this->redirectToTab()
            ->with('toast', ['type' => $type, 'message' => $message]);
    }

    public function artisan(PortalArtisanRunRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $confirmed = (bool) ($payload['confirmed'] ?? false);

        try {
            $result = $this->service->runArtisanCommand((string) $payload['command'], $confirmed);
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->withInput()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $request->session()->put('admin_update_last_artisan_run', $result);

        $message = !empty($result['success'])
            ? 'Komenda artisan zostala wykonana.'
            : sprintf('Komenda artisan zakonczyla sie bledem (exit code: %d).', (int) ($result['exit_code'] ?? 1));

        return $this->redirectToTab()
            ->with('toast', ['type' => !empty($result['success']) ? 'success' : 'error', 'message' => $message]);
    }

    public function maintenanceOn(PortalMaintenanceRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        try {
            $result = $this->service->enableMaintenanceMode((string) $payload['allowed_ip']);
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->withInput()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $request->session()->put('admin_update_last_maintenance_run', $result);

        return $this->redirectToTab()
            ->with('toast', [
                'type' => 'success',
                'message' => sprintf('Maintenance mode wlaczony. Przepuszczone IP: %s.', $result['allowed_ip'] ?? '-'),
            ]);
    }

    public function maintenanceOff(Request $request): RedirectResponse
    {
        try {
            $result = $this->service->disableMaintenanceMode();
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $request->session()->put('admin_update_last_maintenance_run', $result);

        return $this->redirectToTab()
            ->with('toast', ['type' => 'success', 'message' => 'Maintenance mode wylaczony.']);
    }

    private function redirectToTab(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'update']);
    }
}
