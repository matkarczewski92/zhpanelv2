<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
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

        try {
            $result = $this->service->runUpdate($runMigrate, $runBuild);
        } catch (RuntimeException $exception) {
            return $this->redirectToTab()
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

    private function redirectToTab(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'update']);
    }
}
