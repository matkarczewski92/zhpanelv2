<?php

namespace App\ViewModels;

class PublicAnimalProfileViewModel
{
    /**
     * @param array<int, array{url:string,is_featured_on_homepage:bool}> $galleryPhotos
     * @param array<int, array{label:string,value:string}> $details
     * @param array<int, array{id:int,label:string,type_code:string,type_label:string}> $genotypeChips
     * @param array<int, array{code:string,category_label:string,category_code:string}> $litters
     * @param array<int, array{year:int,months:array<int, array{month:int,month_label_full:string,entries:array<int,array{date_display:string,feed_name:string,quantity:int}>}>}> $feedingsTree
     * @param array<int, array{date_label:string}> $molts
     * @param array<int, array{date:string,value:float}> $weightsSeries
     * @param array<int, array{date_label:string,value:float}> $weights
     */
    public function __construct(
        public readonly string $animalTypeName,
        public readonly string $sexLabel,
        public readonly ?string $dateOfBirth,
        public readonly ?string $litterCode,
        public readonly string $nameDisplayHtml,
        public readonly string $secondNameText,
        public readonly string $bannerUrl,
        public readonly string $avatarUrl,
        public readonly array $galleryPhotos,
        public readonly array $details,
        public readonly array $genotypeChips,
        public readonly array $litters,
        public readonly array $feedingsTree,
        public readonly array $molts,
        public readonly array $weightsSeries,
        public readonly array $weights,
        public readonly ?string $offerValue,
        public readonly bool $hasReservation,
        public readonly ?string $publicProfileTag,
    ) {
    }
}
