<?php

namespace App\Http\Controllers\Panel;

use App\Application\MassData\Commands\CommitMassDataCommand;
use App\Application\MassData\Queries\GetMassDataIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CommitMassDataRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MassDataController extends Controller
{
    public function index(GetMassDataIndexQuery $query): View
    {
        return view('panel.massdata.index', [
            'page' => $query->handle(),
        ]);
    }

    public function commit(CommitMassDataRequest $request, CommitMassDataCommand $command): RedirectResponse
    {
        try {
            $result = $command->handle($request->validated());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('panel.massdata.index')
                ->withErrors($exception->errors(), 'massData')
                ->withInput();
        }

        return redirect()
            ->route('panel.massdata.index')
            ->with('toast', [
                'type' => 'success',
                'message' => sprintf(
                    'Dane zapisane. Karmienia: %d, wazenia: %d.',
                    $result['feedings_count'],
                    $result['weights_count']
                ),
            ]);
    }
}

