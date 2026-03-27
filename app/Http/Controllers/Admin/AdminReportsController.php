<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Commands\DeleteAdminReportCommand;
use App\Application\Admin\Commands\GenerateAdminReportCommand;
use App\Application\Admin\Queries\GetReportsIndexQuery;
use App\Application\Admin\Queries\GetStoredAdminReportDownloadQuery;
use App\Application\Admin\Queries\GetStoredAdminReportPreviewQuery;
use App\Application\Admin\Queries\PreviewAdminReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReportActionRequest;
use App\Models\AdminReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminReportsController extends Controller
{
    public function index(GetReportsIndexQuery $query): View
    {
        return view('admin.reports.index', [
            'page' => $query->handle(),
        ]);
    }

    public function preview(AdminReportActionRequest $request, PreviewAdminReportQuery $query): View|RedirectResponse
    {
        $result = $query->handle($request->validated());

        if (($result['status'] ?? null) !== 'ok') {
            return redirect()
                ->route('admin.reports.index')
                ->with('toast', [
                    'type' => 'warning',
                    'message' => $result['message'] ?? 'Brak danych do podgladu.',
                ])
                ->withInput();
        }

        return view('admin.reports.preview', $result);
    }

    public function store(AdminReportActionRequest $request, GenerateAdminReportCommand $command): RedirectResponse
    {
        $result = $command->handle($request->validated());

        return redirect()
            ->route('admin.reports.index')
            ->with('toast', [
                'type' => ($result['status'] ?? null) === 'ok' ? 'success' : 'warning',
                'message' => $result['message'] ?? 'Nie udalo sie wygenerowac raportu.',
            ]);
    }

    public function previewStored(AdminReport $report, GetStoredAdminReportPreviewQuery $query): View
    {
        return view('admin.reports.preview', $query->handle((int) $report->id));
    }

    public function download(AdminReport $report, GetStoredAdminReportDownloadQuery $query)
    {
        $result = $query->handle((int) $report->id);

        if (($result['status'] ?? null) !== 'ok') {
            return redirect()
                ->route('admin.reports.index')
                ->with('toast', [
                    'type' => 'warning',
                    'message' => $result['message'] ?? 'Nie znaleziono pliku raportu.',
                ]);
        }

        return Storage::disk('local')->download(
            (string) $result['report']->pdf_path,
            (string) $result['report']->file_name
        );
    }

    public function destroy(AdminReport $report, DeleteAdminReportCommand $command): RedirectResponse
    {
        $result = $command->handle((int) $report->id);

        return redirect()
            ->route('admin.reports.index')
            ->with('toast', [
                'type' => 'success',
                'message' => $result['message'] ?? 'Raport zostal usuniety.',
            ]);
    }
}
