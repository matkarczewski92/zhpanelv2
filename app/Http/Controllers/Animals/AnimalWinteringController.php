<?php

namespace App\Http\Controllers\Animals;

use App\Application\Winterings\Commands\CloseAnimalWinteringCommand;
use App\Application\Winterings\Commands\EndWinteringStageCommand;
use App\Application\Winterings\Commands\SaveAnimalWinteringPlanCommand;
use App\Application\Winterings\Commands\StartWinteringStageCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SaveAnimalWinteringPlanRequest;
use App\Models\Animal;
use App\Models\Wintering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AnimalWinteringController extends Controller
{
    private function winteringUrl(Animal $animal): string
    {
        return route('panel.animals.show', $animal->id) . '#wintering';
    }

    public function save(
        SaveAnimalWinteringPlanRequest $request,
        Animal $animal,
        SaveAnimalWinteringPlanCommand $command
    ): RedirectResponse {
        $payload = $request->validated();
        $payload['animal_id'] = (int) $animal->id;

        try {
            $command->handle($payload);
        } catch (ValidationException $exception) {
            return redirect()
                ->to($this->winteringUrl($animal))
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->to($this->winteringUrl($animal))
            ->with('toast', ['type' => 'success', 'message' => 'Plan zimowania zapisany.']);
    }

    public function startStage(
        Animal $animal,
        Wintering $wintering,
        StartWinteringStageCommand $command
    ): RedirectResponse {
        try {
            $command->handle((int) $animal->id, (int) $wintering->id);
        } catch (ValidationException $exception) {
            return redirect()
                ->to($this->winteringUrl($animal))
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->to($this->winteringUrl($animal))
            ->with('toast', ['type' => 'success', 'message' => 'Etap zimowania rozpoczety.']);
    }

    public function endStage(
        Animal $animal,
        Wintering $wintering,
        EndWinteringStageCommand $command
    ): RedirectResponse {
        try {
            $command->handle((int) $animal->id, (int) $wintering->id);
        } catch (ValidationException $exception) {
            return redirect()
                ->to($this->winteringUrl($animal))
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->to($this->winteringUrl($animal))
            ->with('toast', ['type' => 'success', 'message' => 'Etap zimowania zakonczony.']);
    }

    public function closeWintering(
        Animal $animal,
        CloseAnimalWinteringCommand $command
    ): RedirectResponse {
        try {
            $command->handle((int) $animal->id);
        } catch (ValidationException $exception) {
            return redirect()
                ->to($this->winteringUrl($animal))
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->to($this->winteringUrl($animal))
            ->with('toast', ['type' => 'success', 'message' => 'Zimowanie zakonczone.']);
    }
}
