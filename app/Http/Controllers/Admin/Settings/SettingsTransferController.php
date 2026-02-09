<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Admin\Settings\SettingsTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsTransferController extends Controller
{
    private const SESSION_PREVIEW_KEY = 'admin_settings_import_preview';

    public function __construct(private readonly SettingsTransferService $service)
    {
    }

    public function export(): StreamedResponse
    {
        $payload = $this->service->exportPayload();
        $filename = 'settings-export-' . now()->format('Ymd-His') . '.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $json = is_string($json) ? $json : '{}';

        return response()->streamDownload(
            static function () use ($json): void {
                echo $json;
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public function importPreview(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
        ]);

        $file = $request->file('import_file');
        if ($file === null) {
            return back()->with('toast', ['type' => 'danger', 'message' => 'Brak pliku do importu.']);
        }

        $content = file_get_contents($file->getRealPath());
        if (!is_string($content) || trim($content) === '') {
            return back()->with('toast', ['type' => 'danger', 'message' => 'Plik importu jest pusty.']);
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                throw new \RuntimeException('Niepoprawny format JSON.');
            }
        } catch (\Throwable $e) {
            return back()->with('toast', ['type' => 'danger', 'message' => 'Niepoprawny plik JSON: ' . $e->getMessage()]);
        }

        $preview = $this->service->buildPreviewFromDecoded($decoded);
        $request->session()->put(self::SESSION_PREVIEW_KEY, $preview);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'system'])
            ->with('toast', ['type' => 'success', 'message' => 'Import wczytany. Sprawdz różnice i zatwierdź.']);
    }

    public function reject(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_PREVIEW_KEY);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'system'])
            ->with('toast', ['type' => 'warning', 'message' => 'Import odrzucony.']);
    }

    public function apply(Request $request): RedirectResponse
    {
        $mode = (string) $request->input('mode', 'merge');
        if (!in_array($mode, ['merge', 'replace'], true)) {
            $mode = 'merge';
        }

        $preview = $request->session()->get(self::SESSION_PREVIEW_KEY);
        if (!is_array($preview) || !isset($preview['sections']) || !is_array($preview['sections'])) {
            return back()->with('toast', ['type' => 'danger', 'message' => 'Brak aktywnego importu do zatwierdzenia.']);
        }

        $rawRows = $request->input('rows', []);
        if (!is_array($rawRows)) {
            $rawRows = [];
        }

        $definitions = $this->service->sectionDefinitions();
        $sectionsForApply = [];
        foreach (array_keys($definitions) as $sectionKey) {
            $sectionRows = $rawRows[$sectionKey] ?? [];
            if (!is_array($sectionRows)) {
                $sectionRows = [];
            }

            foreach ($sectionRows as &$row) {
                if (!is_array($row)) {
                    $row = [];
                    continue;
                }
                foreach ($definitions[$sectionKey]['fields'] as $field) {
                    if (($field['type'] ?? '') === 'bool') {
                        $f = (string) ($field['key'] ?? '');
                        $row[$f] = !empty($row[$f]) && in_array((string) $row[$f], ['1', 'true', 'on', 'yes'], true);
                    }
                }
            }
            unset($row);

            $sectionsForApply[$sectionKey] = array_values($sectionRows);
        }

        $stats = $this->service->applyImport($sectionsForApply, $mode === 'replace');
        $request->session()->forget(self::SESSION_PREVIEW_KEY);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'system'])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Import zakończony. Utworzono: ' . (int) ($stats['created'] ?? 0)
                    . ', zaktualizowano: ' . (int) ($stats['updated'] ?? 0)
                    . ', pominięto: ' . (int) ($stats['skipped'] ?? 0) . '.',
            ]);
    }
}

