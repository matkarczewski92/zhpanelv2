<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\PlannedLittersBulkDeleted;
use App\Models\Litter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BulkDeletePlannedLittersBySeasonCommand
{
    /**
     * @return array{season:int,deleted:int,blocked:int,has_season:bool}
     */
    public function handle(int $season): array
    {
        $today = CarbonImmutable::today()->format('Y-m-d');

        $eligibleQuery = Litter::query()
            ->where('category', 2)
            ->where('season', $season)
            ->whereNull('connection_date')
            ->where(function ($query) use ($today): void {
                $query->whereNull('planned_connection_date')
                    ->orWhere('planned_connection_date', '>=', $today);
            });

        $deletableCount = (clone $eligibleQuery)->count();
        $hasSeason = Litter::query()
            ->where('category', 2)
            ->where('season', $season)
            ->exists();

        if ($deletableCount === 0) {
            return [
                'season' => $season,
                'deleted' => 0,
                'blocked' => 0,
                'has_season' => $hasSeason,
            ];
        }

        DB::transaction(function () use ($eligibleQuery, $season, $deletableCount): void {
            $eligibleQuery->delete();

            DB::afterCommit(static function () use ($season, $deletableCount): void {
                event(new PlannedLittersBulkDeleted($season, $deletableCount));
            });
        });

        $blockedCount = Litter::query()
            ->where('category', 2)
            ->where('season', $season)
            ->count();

        return [
            'season' => $season,
            'deleted' => $deletableCount,
            'blocked' => $blockedCount,
            'has_season' => $hasSeason,
        ];
    }
}

