<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicProfileService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Response;

class PublicProfileController extends Controller
{
    public function show(string $code, PublicProfileService $service): View
    {
        $profile = $service->getByCode($code);

        abort_if(!$profile, 404);

        $weightsPage = $service->getWeightsPage($code, 1, 5);
        $moltsPage = $service->getMoltsPage($code, 1, 5);

        return view('public.profile.show', [
            'profile' => $profile,
            'weightsPage' => $weightsPage,
            'moltsPage' => $moltsPage,
            'publicCode' => $code,
        ]);
    }

    public function weights(string $code, Request $request, PublicProfileService $service): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $data = $service->getWeightsPage($code, $page, 5);
        abort_if(!$data, 404);

        return response()->view('public.profile.partials.weights', [
            'items' => $data['items'],
            'pagination' => $data['pagination'],
        ]);
    }

    public function molts(string $code, Request $request, PublicProfileService $service): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $data = $service->getMoltsPage($code, $page, 5);
        abort_if(!$data, 404);

        return response()->view('public.profile.partials.molts', [
            'items' => $data['items'],
            'pagination' => $data['pagination'],
        ]);
    }
}
