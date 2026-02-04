<?php

namespace App\Http\Controllers\Public;

use App\Application\Public\Queries\ResolvePublicProfileLookupQuery;
use App\Http\Controllers\Controller;
use App\Services\PublicProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(
        string $code,
        PublicProfileService $service,
        ResolvePublicProfileLookupQuery $lookupQuery
    ): View|RedirectResponse {
        $lookup = $lookupQuery->handle($code);

        if ($lookup['status'] !== 'ok') {
            $message = $lookup['status'] === 'not_public'
                ? 'Profil publiczny nie jest dostêpny dla podanego kodu.'
                : 'Brak profilu dla podanego kodu.';

            return redirect(route('web.home') . '#profile')
                ->withInput(['code' => $code])
                ->with('profile_lookup_error', $message);
        }

        $profile = $service->getByCode($code);

        if (!$profile) {
            return redirect(route('web.home') . '#profile')
                ->withInput(['code' => $code])
                ->with('profile_lookup_error', 'Brak profilu dla podanego kodu.');
        }

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
