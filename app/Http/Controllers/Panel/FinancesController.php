<?php

namespace App\Http\Controllers\Panel;

use App\Application\Finances\Commands\CreateFinanceTransactionCommand;
use App\Application\Finances\Commands\DeleteFinanceTransactionCommand;
use App\Application\Finances\Commands\UpdateFinanceTransactionCommand;
use App\Application\Finances\Queries\GetFinancesIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\FinanceIndexRequest;
use App\Http\Requests\Panel\StoreFinanceTransactionRequest;
use App\Http\Requests\Panel\UpdateFinanceTransactionRequest;
use App\Models\Finance;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FinancesController extends Controller
{
    public function index(FinanceIndexRequest $request, GetFinancesIndexQuery $query): View
    {
        return view('panel.finances.index', [
            'page' => $query->handle($request->validated()),
        ]);
    }

    public function storeTransaction(
        StoreFinanceTransactionRequest $request,
        CreateFinanceTransactionCommand $command
    ): RedirectResponse {
        $command->handle($request->validated());

        return redirect()
            ->route('panel.finances.index')
            ->with('toast', ['type' => 'success', 'message' => 'Transakcja zostala dodana.']);
    }

    public function updateTransaction(
        UpdateFinanceTransactionRequest $request,
        Finance $finance,
        UpdateFinanceTransactionCommand $command
    ): RedirectResponse {
        $command->handle(array_merge($request->validated(), ['id' => $finance->id]));

        return redirect()
            ->route('panel.finances.index')
            ->with('toast', ['type' => 'success', 'message' => 'Transakcja zostala zaktualizowana.']);
    }

    public function destroyTransaction(Finance $finance, DeleteFinanceTransactionCommand $command): RedirectResponse
    {
        $command->handle($finance->id);

        return redirect()
            ->route('panel.finances.index')
            ->with('toast', ['type' => 'success', 'message' => 'Transakcja zostala usunieta.']);
    }
}
