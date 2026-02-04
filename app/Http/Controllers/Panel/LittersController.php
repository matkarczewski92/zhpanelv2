<?php

namespace App\Http\Controllers\Panel;

use App\Application\Litters\Commands\AddLitterOffspringCommand;
use App\Application\Litters\Commands\BulkDeletePlannedLittersBySeasonCommand;
use App\Application\Litters\Commands\CreateLitterCommand;
use App\Application\Litters\Commands\DeleteLitterCommand;
use App\Application\Litters\Commands\DeleteLitterGalleryPhotoCommand;
use App\Application\Litters\Commands\UpdateLitterAdnotationCommand;
use App\Application\Litters\Commands\AddLitterGalleryPhotoCommand;
use App\Application\Litters\Commands\UpdateLitterOffspringBatchCommand;
use App\Application\Litters\Commands\UpdateLitterCommand;
use App\Application\Litters\Queries\GetLitterFormOptionsQuery;
use App\Application\Litters\Queries\GetLittersIndexQuery;
use App\Application\Litters\Queries\GetLitterShowQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\BulkDeletePlannedLittersRequest;
use App\Http\Requests\Panel\LitterIndexRequest;
use App\Http\Requests\Panel\LitterShowRequest;
use App\Http\Requests\Panel\StoreLitterOffspringRequest;
use App\Http\Requests\Panel\StoreLitterRequest;
use App\Http\Requests\Panel\StoreLitterGalleryPhotoRequest;
use App\Http\Requests\Panel\UpdateLitterAdnotationRequest;
use App\Http\Requests\Panel\UpdateLitterRequest;
use App\Http\Requests\Panel\UpdateLitterOffspringBatchRequest;
use App\Models\Litter;
use App\Models\LitterGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LittersController extends Controller
{
    public function index(LitterIndexRequest $request, GetLittersIndexQuery $query): View
    {
        return view('panel.litters.index', [
            'page' => $query->handle($request->validated()),
        ]);
    }

    public function create(GetLitterFormOptionsQuery $query): View
    {
        return view('panel.litters.create', [
            'form' => $query->handle(),
        ]);
    }

    public function store(StoreLitterRequest $request, CreateLitterCommand $command): RedirectResponse
    {
        $litter = $command->handle($request->validated());

        return redirect()
            ->route('panel.litters.show', $litter)
            ->with('toast', ['type' => 'success', 'message' => 'Dodano nowy miot.']);
    }

    public function show(LitterShowRequest $request, Litter $litter, GetLitterShowQuery $query): View
    {
        return view('panel.litters.show', [
            'page' => $query->handle($litter, $request->validated()),
            'offspringEditMode' => $request->boolean('edit_offspring'),
            'openGalleryModal' => $request->boolean('open_gallery'),
        ]);
    }

    public function edit(Litter $litter, GetLitterShowQuery $showQuery, GetLitterFormOptionsQuery $formQuery): View
    {
        return view('panel.litters.edit', [
            'page' => $showQuery->handle($litter),
            'form' => $formQuery->handle(),
        ]);
    }

    public function update(
        UpdateLitterRequest $request,
        Litter $litter,
        UpdateLitterCommand $command
    ): RedirectResponse {
        $command->handle($litter, $request->validated());

        return redirect()
            ->route('panel.litters.show', $litter)
            ->with('toast', ['type' => 'success', 'message' => 'Zmiany w miocie zostaly zapisane.']);
    }

    public function destroy(Litter $litter, DeleteLitterCommand $command): RedirectResponse
    {
        $command->handle($litter);

        return redirect()
            ->route('panel.litters.index')
            ->with('toast', ['type' => 'success', 'message' => 'Miot zostal usuniety.']);
    }

    public function bulkDestroyPlanned(
        BulkDeletePlannedLittersRequest $request,
        BulkDeletePlannedLittersBySeasonCommand $command
    ): RedirectResponse {
        $season = (int) $request->validated()['season'];
        $currentYear = (int) now()->format('Y');

        if ($season < $currentYear) {
            return redirect()
                ->route('panel.litters.index')
                ->with('toast', [
                    'type' => 'warning',
                    'message' => "Sezon {$season} jest w przeszlosci, nic nie usunieto.",
                ]);
        }

        $result = $command->handle($season);

        if ($result['deleted'] === 0) {
            $message = $result['has_season']
                ? "Brak miotow do usuniecia w sezonie {$season}. Sprawdz daty laczenia."
                : "Brak planowanych miotow dla sezonu {$season}.";

            return redirect()
                ->route('panel.litters.index')
                ->with('toast', [
                    'type' => $result['has_season'] ? 'info' : 'warning',
                    'message' => $message,
                ]);
        }

        $message = "Usunieto {$result['deleted']} planowanych miotow z sezonu {$season}.";
        if ($result['blocked'] > 0) {
            $message .= " Pominieto {$result['blocked']} miotow (posiadaja date laczenia albo termin w przeszlosci).";
        }

        return redirect()
            ->route('panel.litters.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function storeOffspring(
        StoreLitterOffspringRequest $request,
        Litter $litter,
        AddLitterOffspringCommand $command
    ): RedirectResponse {
        $command->handle($litter, (int) $request->validated()['amount']);

        return redirect()
            ->route('panel.litters.show', $litter)
            ->with('toast', ['type' => 'success', 'message' => 'Dodano potomstwo do miotu.']);
    }

    public function storeGalleryPhoto(
        StoreLitterGalleryPhotoRequest $request,
        Litter $litter,
        AddLitterGalleryPhotoCommand $command
    ): RedirectResponse {
        try {
            $command->handle($litter, $request->file('photo'));

            return redirect()
                ->route('panel.litters.show', ['litter' => $litter->id, 'open_gallery' => 1])
                ->with('toast', ['type' => 'success', 'message' => 'Zdjecie zostalo dodane.'])
                ->withFragment('litterGalleryModal');
        } catch (\Throwable) {
            return redirect()
                ->route('panel.litters.show', ['litter' => $litter->id, 'open_gallery' => 1])
                ->with('toast', ['type' => 'error', 'message' => 'Nie udalo sie przetworzyc zdjecia.'])
                ->withFragment('litterGalleryModal');
        }
    }

    public function destroyGalleryPhoto(
        Litter $litter,
        LitterGallery $photo,
        DeleteLitterGalleryPhotoCommand $command
    ): RedirectResponse {
        $command->handle($litter, $photo);

        return redirect()
            ->route('panel.litters.show', ['litter' => $litter->id, 'open_gallery' => 1])
            ->with('toast', ['type' => 'success', 'message' => 'Zdjecie zostalo usuniete.'])
            ->withFragment('litterGalleryModal');
    }

    public function updateAdnotation(
        UpdateLitterAdnotationRequest $request,
        Litter $litter,
        UpdateLitterAdnotationCommand $command
    ): RedirectResponse {
        $command->handle($litter, $request->validated()['adnotation'] ?? null);

        return redirect()
            ->route('panel.litters.show', $litter)
            ->with('toast', ['type' => 'success', 'message' => 'Adnotacje zostaly zaktualizowane.']);
    }

    public function updateOffspringBatch(
        UpdateLitterOffspringBatchRequest $request,
        Litter $litter,
        UpdateLitterOffspringBatchCommand $command
    ): RedirectResponse {
        $updated = $command->handle($litter, $request->validated()['rows']);

        return redirect()
            ->route('panel.litters.show', $litter)
            ->with('toast', [
                'type' => 'success',
                'message' => "Zapisano zmiany dla {$updated} rekordow potomstwa.",
            ]);
    }
}
