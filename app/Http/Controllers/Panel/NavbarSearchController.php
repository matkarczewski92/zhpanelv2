<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Litter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NavbarSearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:id,public_tag,litter_id,litter_code'],
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $scope = (string) ($validated['scope'] ?? 'id');
        $q = trim((string) ($validated['q'] ?? ''));
        if ($q === '') {
            return response()->json(['items' => []]);
        }

        $items = match ($scope) {
            'id' => $this->suggestAnimalsByIdPrefix($q),
            'public_tag' => $this->suggestAnimalsByPublicTag($q),
            'litter_id' => $this->suggestLittersByIdPrefix($q),
            default => $this->suggestLittersByCode($q),
        };

        return response()->json(['items' => $items]);
    }

    public function go(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:id,public_tag,litter_id,litter_code'],
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $scope = (string) ($validated['scope'] ?? 'id');
        $q = trim((string) ($validated['q'] ?? ''));

        $url = match ($scope) {
            'id' => $this->resolveAnimalById($q),
            'public_tag' => $this->resolveAnimalByPublicTag($q),
            'litter_id' => $this->resolveLitterById($q),
            default => $this->resolveLitterByCode($q),
        };

        if ($url !== null) {
            return redirect()->to($url);
        }

        return back()->with('toast', [
            'type' => 'warning',
            'message' => 'Nie znaleziono wyniku dla podanej frazy.',
        ]);
    }

    /**
     * @return array<int, array{label:string,subtitle:string,url:string}>
     */
    private function suggestAnimalsByIdPrefix(string $q): array
    {
        if (!preg_match('/^\d+$/', $q)) {
            return [];
        }

        return Animal::query()
            ->whereRaw('CAST(id AS CHAR) LIKE ?', [$q . '%'])
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'name', 'public_profile_tag'])
            ->map(fn (Animal $animal): array => [
                'label' => $this->normalizePlainText((string) $animal->name, 'Waz #' . $animal->id),
                'subtitle' => 'ID: ' . $animal->id . ($animal->public_profile_tag ? ' | Tag: ' . $animal->public_profile_tag : ''),
                'url' => route('panel.animals.show', $animal->id),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string,subtitle:string,url:string}>
     */
    private function suggestAnimalsByPublicTag(string $q): array
    {
        return Animal::query()
            ->whereNotNull('public_profile_tag')
            ->where('public_profile_tag', 'like', '%' . $q . '%')
            ->orderBy('public_profile_tag')
            ->limit(10)
            ->get(['id', 'name', 'public_profile_tag'])
            ->map(fn (Animal $animal): array => [
                'label' => $this->normalizePlainText((string) $animal->name, 'Waz #' . $animal->id),
                'subtitle' => 'Tag: ' . (string) $animal->public_profile_tag . ' | ID: ' . $animal->id,
                'url' => route('panel.animals.show', $animal->id),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string,subtitle:string,url:string}>
     */
    private function suggestLittersByIdPrefix(string $q): array
    {
        if (!preg_match('/^\d+$/', $q)) {
            return [];
        }

        return Litter::query()
            ->whereRaw('CAST(id AS CHAR) LIKE ?', [$q . '%'])
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'litter_code', 'season'])
            ->map(fn (Litter $litter): array => [
                'label' => $this->normalizePlainText((string) $litter->litter_code, 'Miot #' . $litter->id),
                'subtitle' => 'ID miotu: ' . $litter->id . ($litter->season ? ' | Sezon: ' . $litter->season : ''),
                'url' => route('panel.litters.show', $litter->id),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string,subtitle:string,url:string}>
     */
    private function suggestLittersByCode(string $q): array
    {
        return Litter::query()
            ->whereNotNull('litter_code')
            ->where('litter_code', 'like', '%' . $q . '%')
            ->orderBy('litter_code')
            ->limit(10)
            ->get(['id', 'litter_code', 'season'])
            ->map(fn (Litter $litter): array => [
                'label' => $this->normalizePlainText((string) $litter->litter_code, 'Miot #' . $litter->id),
                'subtitle' => 'ID miotu: ' . $litter->id . ($litter->season ? ' | Sezon: ' . $litter->season : ''),
                'url' => route('panel.litters.show', $litter->id),
            ])
            ->all();
    }

    private function resolveAnimalById(string $q): ?string
    {
        if (!preg_match('/^\d+$/', $q)) {
            return null;
        }

        $animal = Animal::query()->find((int) $q);

        return $animal ? route('panel.animals.show', $animal->id) : null;
    }

    private function resolveAnimalByPublicTag(string $q): ?string
    {
        $animal = Animal::query()
            ->whereNotNull('public_profile_tag')
            ->whereRaw('LOWER(public_profile_tag) = ?', [mb_strtolower($q)])
            ->first(['id']);

        return $animal ? route('panel.animals.show', $animal->id) : null;
    }

    private function resolveLitterById(string $q): ?string
    {
        if (!preg_match('/^\d+$/', $q)) {
            return null;
        }

        $litter = Litter::query()->find((int) $q);

        return $litter ? route('panel.litters.show', $litter->id) : null;
    }

    private function resolveLitterByCode(string $q): ?string
    {
        $litter = Litter::query()
            ->whereNotNull('litter_code')
            ->whereRaw('LOWER(litter_code) = ?', [mb_strtolower($q)])
            ->first(['id']);

        return $litter ? route('panel.litters.show', $litter->id) : null;
    }

    private function normalizePlainText(string $value, string $fallback): string
    {
        $normalized = trim(strip_tags($value));

        return $normalized !== '' ? $normalized : $fallback;
    }
}
