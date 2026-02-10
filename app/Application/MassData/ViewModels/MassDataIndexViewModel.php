<?php

namespace App\Application\MassData\ViewModels;

class MassDataIndexViewModel
{
    /**
     * @param array<int, array{id:int, name:string}> $feeds
     * @param array<int, array{
     *     category_id:int,
     *     title:string,
     *     animals:array<int, array{
     *         id:int,
     *         name_html:string,
     *         profile_url:string,
     *         default_feed_id:int|null,
     *         default_amount:int,
     *         default_feed_check:bool,
     *         is_wintering:bool
     *     }>
     * }> $sections
     */
    public function __construct(
        public readonly array $feeds,
        public readonly array $sections
    ) {
    }
}
