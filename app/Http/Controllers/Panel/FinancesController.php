<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FinancesController extends Controller
{
    public function index(): View
    {
        return view('panel.finances.index');
    }
}
