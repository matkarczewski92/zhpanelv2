<?php

namespace App\Http\Controllers\Public;

use App\Application\Public\Queries\GetLandingPageQuery;
use App\Application\Public\Queries\ResolvePublicProfileLookupQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicProfileLookupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(GetLandingPageQuery $query): View
    {
        return view('welcome', [
            'page' => $query->handle(),
        ]);
    }

    public function lookup(
        PublicProfileLookupRequest $request,
        ResolvePublicProfileLookupQuery $query
    ): RedirectResponse {
        $validated = $request->validated();
        $result = $query->handle($validated['code']);

        if ($result['status'] === 'ok') {
            return redirect()->route('profile.show', ['code' => $result['code']]);
        }

        $message = $result['status'] === 'not_public'
            ? 'Profil publiczny nie jest dostępny dla podanego kodu.'
            : 'Brak profilu dla podanego kodu.';

        return redirect(route('web.home') . '#profile')
            ->withInput($validated)
            ->with('profile_lookup_error', $message);
    }
}
