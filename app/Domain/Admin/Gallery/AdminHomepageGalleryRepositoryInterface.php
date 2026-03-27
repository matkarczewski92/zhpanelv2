<?php

namespace App\Domain\Admin\Gallery;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminHomepageGalleryRepositoryInterface
{
    public function paginateFeatured(int $perPage): LengthAwarePaginator;

    public function removeFromHomepage(int $photoId): bool;
}
