<?php

namespace App\Application\Public\ViewModels;

class LandingPageViewModel
{
    /**
     * @param array<int, array{url:string, title:string}> $gallery
     * @param array<int, array{
     *     type_id:int,
     *     type_name:string,
     *     title:string,
     *     male_name:string|null,
     *     female_name:string|null,
     *     offers:array<int, array{
     *         id:int,
     *         name_html:string,
     *         sex_label:string,
     *         date_of_birth:string|null,
     *         price_label:string,
     *         profile_url:string|null,
     *         photo_url:string|null
     *     }>
     * }> $offerGroups
     * @param array<int, array{id:int, name:string, sort_order:int}> $offerColorGroups
     * @param array<int, array{id:int, title:string, status_label:string, male_name:string|null, female_name:string|null}> $breedingPlans
     */
    public function __construct(
        public readonly array $gallery,
        public readonly array $offerGroups,
        public readonly array $offerColorGroups,
        public readonly array $breedingPlans
    ) {
    }
}
