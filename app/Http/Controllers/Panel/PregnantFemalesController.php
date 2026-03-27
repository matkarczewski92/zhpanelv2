<?php

namespace App\Http\Controllers\Panel;

use App\Application\Animals\Queries\GetPregnantFemalesIndexQuery;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PregnantFemalesController extends Controller
{
    public function index(GetPregnantFemalesIndexQuery $query): View
    {
        return view('panel.pregnancies.index', [
            'page' => $query->handle(),
        ]);
    }
}
