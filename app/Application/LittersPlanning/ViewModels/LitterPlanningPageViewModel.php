<?php

namespace App\Application\LittersPlanning\ViewModels;

class LitterPlanningPageViewModel
{
    /**
     * @param array<int, array{id:int,name:string,animal_type_id:int|null,weight:int,color:string,is_used:bool}> $females
     * @param array<int, array{id:int,name:string,animal_type_id:int|null,weight:int,color:string}> $males
     * @param array<int, array{
     *     id:int,
     *     name:string,
     *     planned_year:int|null,
     *     updated_at_label:string,
     *     pairs:array<int, array{
     *         female_id:int,
     *         female_name:string,
     *         male_id:int,
     *         male_name:string
     *     }>
     * }> $plans
     * @param array<int, int> $seasons
     * @param array<int, array{
     *     litter_id:int,
     *     litter_code:string,
     *     season:int,
     *     traits_name:string,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>,
     *     traits_count:int,
     *     percentage:float,
     *     percentage_label:string,
     *     litter_url:string
     * }> $seasonOffspringRows
     * @param array<int, string> $connectionExpectedTraits
     * @param array<int, string> $connectionGeneSuggestions
     * @param array<int, array{
     *     female_id:int,
     *     female_name:string,
     *     male_id:int,
     *     male_name:string,
     *     probability:float,
     *     probability_label:string,
     *     matched_rows_count:int,
     *     matched_rows:array<int, array{
     *         percentage:float,
     *         percentage_label:string,
     *         traits_name:string,
     *         visual_traits:array<int, string>,
     *         carrier_traits:array<int, string>
     *     }>
     * }> $connectionSearchRows
     * @param array<int, string> $roadmapExpectedTraits
     * @param array<int, array{
     *     generation:int,
     *     pairing_label:string,
     *     keeper_label:string,
     *     probability_label:string,
     *     can_create_litter:bool,
     *     parent_male_id:int|null,
     *     parent_female_id:int|null,
     *     existing_litter_id:int|null,
     *     existing_litter_code:string|null,
     *     existing_litter_season:int|null,
     *     existing_litter_url:string|null,
     *     is_realized:bool,
     *     matched_targets:array<int, string>,
     *     matched_count:int,
     *     total_targets:int,
     *     offspring_rows:array<int, array{
     *         is_keeper:bool,
     *         is_target:bool,
     *         percentage_label:string,
     *         traits_name:string,
     *         visual_traits:array<int, string>,
     *         carrier_traits:array<int, string>,
     *         matched_targets:array<int, string>
     *     }>
     * }> $roadmapSteps
     * @param array<int, array{
     *     id:int,
     *     name:string,
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     completed_generations:array<int, int>,
     *     steps_count:int,
     *     last_refreshed_at_label:string,
     *     updated_at_label:string
     * }> $roadmaps
     * @param array<int, array{
     *     roadmap_id:int,
     *     roadmap_name:string,
     *     generation:int,
     *     pairing_label:string,
     *     keeper_label:string,
     *     parent_male_id:int|null,
     *     parent_female_id:int|null,
     *     litter_id:int|null,
     *     litter_code:string|null,
     *     litter_url:string|null
     * }> $roadmapKeepers
     */
    public function __construct(
        public readonly array $females,
        public readonly array $males,
        public readonly array $plans,
        public readonly array $seasons,
        public readonly int $selectedSeason,
        public readonly array $seasonOffspringRows,
        public readonly string $connectionSearchInput,
        public readonly array $connectionExpectedTraits,
        public readonly bool $connectionStrictVisualOnly,
        public readonly array $connectionGeneSuggestions,
        public readonly int $connectionCheckedPairs,
        public readonly array $connectionSearchRows,
        public readonly string $roadmapSearchInput,
        public readonly string $roadmapPriorityMode,
        public readonly array $roadmapExcludedRootPairs,
        public readonly string $roadmapRootPairKey,
        public readonly int $roadmapGenerations,
        public readonly array $roadmapExpectedTraits,
        public readonly bool $roadmapTargetReachable,
        public readonly array $roadmapMatchedTraits,
        public readonly array $roadmapMissingTraits,
        public readonly array $roadmapSteps,
        public readonly array $roadmaps,
        public readonly int $activeRoadmapId,
        public readonly array $roadmapKeepers,
    ) {
    }
}
