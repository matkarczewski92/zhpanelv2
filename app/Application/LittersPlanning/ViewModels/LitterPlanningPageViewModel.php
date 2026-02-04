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
     */
    public function __construct(
        public readonly array $females,
        public readonly array $males,
        public readonly array $plans,
        public readonly array $seasons,
        public readonly int $selectedSeason,
        public readonly array $seasonOffspringRows,
    ) {
    }
}
