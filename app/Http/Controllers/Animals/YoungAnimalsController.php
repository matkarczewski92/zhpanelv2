<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Queries\GetYoungAnimalsIndexQuery;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class YoungAnimalsController extends Controller
{
    public function __invoke(GetYoungAnimalsIndexQuery $query): View
    {
        return view('panel.animals.young', ['animals' => $query->handle()]);
    }
}