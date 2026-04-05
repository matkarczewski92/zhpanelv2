<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Commands\ExportAdminLitterLabelsCommand;
use App\Application\Admin\Queries\GetAdminLitterLabelsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLitterLabelsExportRequest;
use App\Http\Requests\Admin\AdminLitterLabelsIndexRequest;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLitterLabelsController extends Controller
{
    public function index(AdminLitterLabelsIndexRequest $request, GetAdminLitterLabelsQuery $query): View
    {
        return view('admin.labels.litters-print', [
            'vm' => $query->handle($request->validated()),
        ]);
    }

    public function export(
        AdminLitterLabelsExportRequest $request,
        ExportAdminLitterLabelsCommand $command
    ): StreamedResponse {
        $export = $command->handle($request->validated());

        return response()->streamDownload(function () use ($export): void {
            echo $export['content'];
        }, $export['filename'], [
            'Content-Type' => $export['content_type'],
        ]);
    }
}
