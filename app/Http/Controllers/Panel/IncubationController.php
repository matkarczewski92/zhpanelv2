<?php

namespace App\Http\Controllers\Panel;

use App\Application\Litters\Queries\GetIncubationIndexQuery;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IncubationController extends Controller
{
    public function index(GetIncubationIndexQuery $query): View
    {
        return view('panel.incubation.index', [
            'page' => $query->handle(),
        ]);
    }
}
