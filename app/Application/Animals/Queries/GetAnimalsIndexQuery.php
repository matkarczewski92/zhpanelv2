<?php

namespace App\Application\Animals\Queries;

use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use App\Models\AnimalCategory;
use App\Models\AnimalFeeding;
use App\Models\AnimalType;
use App\Models\AnimalWeight;
use App\Models\ColorGroup;
use App\Models\Feed;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GetAnimalsIndexQuery
{
    public function handle(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $typeId = $this->intOrNull($request->query('type_id'));
        $categoryId = $this->intOrNull($request->query('category_id'));
        $feedId = $this->intOrNull($request->query('feed_id'));
        $sex = $this->normalizeSex($request->query('sex'));
        $colorGroupIds = $this->normalizeIdList($request->query('color_groups', []));
        $sortMap = [
            'id' => 'animals.id',
            'name' => 'animals.name',
            'sex' => 'animals.sex',
            'weight' => 'latest_weight',
            'feed' => 'feed_name',
        ];
        $sort = strtolower(trim((string) $request->query('sort', 'id')));
        if (!array_key_exists($sort, $sortMap)) {
            $sort = 'id';
        }

        $direction = strtolower(trim((string) $request->query('direction', 'asc')));

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $types = AnimalType::orderBy('id')->get();
        $categories = AnimalCategory::orderBy('name')->get();
        $feeds = Feed::orderBy('name')->get();
        $activeColorGroups = ColorGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if (!$categoryId) {
            $categoryId = $this->resolveDefaultCategoryId($categories);
        }

        $latestWeightValue = AnimalWeight::query()
            ->select('value')
            ->whereColumn('animal_id', 'animals.id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);

        $lastFeedingAt = AnimalFeeding::query()
            ->select('created_at')
            ->whereColumn('animal_id', 'animals.id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);

        $query = Animal::query()
            ->leftJoin('feeds', 'feeds.id', '=', 'animals.feed_id')
            ->select([
                'animals.id',
                'animals.name',
                'animals.second_name',
                'animals.sex',
                'animals.animal_type_id',
                'animals.animal_category_id',
                'animals.feed_interval',
            ])
            ->addSelect('feeds.name as feed_name')
            ->addSelect('feeds.feeding_interval as feed_interval_default')
            ->selectSub($latestWeightValue, 'latest_weight')
            ->selectSub($lastFeedingAt, 'last_feeding_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('animals.name', 'like', "%{$search}%")
                    ->orWhere('animals.second_name', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('animals.id', (int) $search);
                }
            });
        }

        if ($typeId) {
            $query->where('animals.animal_type_id', $typeId);
        }

        if ($categoryId) {
            $query->where('animals.animal_category_id', $categoryId);
        }

        if ($feedId) {
            $query->where('animals.feed_id', $feedId);
        }

        if ($sex !== null) {
            $query->where('animals.sex', $sex);
        }

        if ($colorGroupIds !== []) {
            $query->whereHas('colorGroups', function ($builder) use ($colorGroupIds): void {
                $builder->whereIn('color_groups.id', $colorGroupIds);
            });
        }

        if (!$typeId) {
            $query->orderBy('animals.animal_type_id');
        }

        $query->orderBy($sortMap[$sort], $direction);

        $paginator = $query->paginate(50);

        $leadTimeDays = (int) SystemConfig::where('key', 'feedLeadTime')->value('value');
        if ($leadTimeDays <= 0) {
            $leadTimeDays = 7;
        }

        $collection = $paginator->getCollection()->map(function (Animal $animal) use ($leadTimeDays): array {
            $lastFeeding = $animal->last_feeding_at ? Carbon::parse($animal->last_feeding_at) : null;
            $nextFeeding = $lastFeeding ? $lastFeeding->copy()->addDays($leadTimeDays) : null;
            $feedInterval = $animal->feed_interval ?: $animal->feed_interval_default;

            return [
                'id' => $animal->id,
                'animal_type_id' => $animal->animal_type_id,
                'animal_category_id' => $animal->animal_category_id,
                'name_display_html' => $this->buildNameDisplay($animal->second_name, $animal->name),
                'sex_label' => Sex::label((int) $animal->sex),
                'feed_name' => $animal->feed_name,
                'last_weight_grams' => $animal->latest_weight,
                'weight_label' => $this->formatWeightLabel($animal->latest_weight),
                'last_feed_at' => $lastFeeding ? $lastFeeding->format('Y-m-d') : null,
                'next_feed_at' => $nextFeeding ? $nextFeeding->format('Y-m-d') : null,
                'feed_interval_value' => $feedInterval,
                'feed_interval_label' => $feedInterval ? $feedInterval . ' dni' : null,
                'is_wintering' => (int) $animal->animal_category_id === 4,
            ];
        });

        $orderedIds = $collection
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $idsString = implode(',', $orderedIds);
        $backUrl = $this->encodeBackUrl($request->fullUrl());
        $positionById = [];
        foreach ($orderedIds as $position => $id) {
            $positionById[$id] = $position;
        }

        $collection = $collection->map(function (array $row) use ($idsString, $backUrl, $positionById): array {
            $id = (int) $row['id'];
            $row['profile_url'] = route('panel.animals.show', [
                'animal' => $id,
                'nav_ids' => $idsString,
                'nav_back' => $backUrl,
            ]);
            $row['nav_position'] = ($positionById[$id] ?? 0) + 1;

            return $row;
        });

        $paginator->setCollection($collection);

        return [
            'animals' => $paginator,
            'groups' => $this->groupByType($collection, $types, $typeId),
            'types' => $types->map(fn ($type) => ['id' => $type->id, 'name' => $type->name])->all(),
            'categories' => $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->all(),
            'feeds' => $feeds->map(fn ($feed) => ['id' => $feed->id, 'name' => $feed->name])->all(),
            'sexes' => collect(Sex::options())
                ->map(fn (string $label, int $value): array => ['id' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => [
                'q' => $search,
                'type_id' => $typeId,
                'category_id' => $categoryId,
                'feed_id' => $feedId,
                'sex' => $sex,
                'color_groups' => $colorGroupIds,
            ],
            'colorGroupFilters' => $this->buildColorGroupFilters($request, $activeColorGroups, $colorGroupIds),
            'colorGroupClearUrl' => $this->buildColorGroupClearUrl($request),
            'sort' => $sort,
            'direction' => $direction,
            'sortLinks' => $this->buildSortLinks($request, $sort, $direction),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $raw = is_array($value) ? $value : explode(',', (string) $value);

        $ids = [];
        foreach ($raw as $item) {
            if (!is_numeric($item)) {
                continue;
            }

            $id = (int) $item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function normalizeSex(mixed $value): ?int
    {
        $sex = $this->intOrNull($value);
        if ($sex === null) {
            return null;
        }

        return in_array($sex, [Sex::Unknown->value, Sex::Male->value, Sex::Female->value], true)
            ? $sex
            : null;
    }

    private function resolveDefaultCategoryId(Collection $categories): ?int
    {
        $default = $categories->first(function ($category): bool {
            return strtolower((string) $category->name) === 'w hodowli';
        });

        if ($default) {
            return (int) $default->id;
        }

        $fallback = $categories->sortBy('id')->first();

        if ($fallback) {
            Log::warning('Animal category "W hodowli" not found. Falling back to ID ' . $fallback->id . '.');
            return (int) $fallback->id;
        }

        Log::warning('No animal categories found when resolving default category.');
        return null;
    }

    private function buildSortLinks(Request $request, ?string $sort, string $direction): array
    {
        $fields = ['id', 'name', 'sex', 'weight', 'feed'];
        $baseQuery = $request->query();
        unset($baseQuery['page']);

        $links = [];

        foreach ($fields as $field) {
            $isActive = $sort === $field;
            $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
            $query = array_merge($baseQuery, [
                'sort' => $field,
                'direction' => $nextDirection,
            ]);

            $queryString = http_build_query($query);
            $url = $request->url();
            $links[$field] = [
                'url' => $queryString ? $url . '?' . $queryString : $url,
                'indicator' => $isActive ? ($direction === 'asc' ? '▲' : '▼') : '',
            ];
        }

        return $links;
    }

    private function groupByType(Collection $animals, Collection $types, ?int $typeId): array
    {
        $grouped = $animals->groupBy('animal_type_id');
        $groups = [];

        $orderedTypes = $typeId ? $types->where('id', $typeId) : $types;

        foreach ($orderedTypes as $type) {
            if ($grouped->has($type->id)) {
                $items = $grouped->get($type->id)->values()->all();
                $groups[] = [
                    'type' => ['id' => $type->id, 'name' => $type->name],
                    'count' => count($items),
                    'count_label' => count($items) . ' zwierząt',
                    'animals' => $items,
                ];
            }
        }

        if ($grouped->has(null)) {
            $items = $grouped->get(null)->values()->all();
            $groups[] = [
                'type' => ['id' => null, 'name' => 'Brak typu'],
                'count' => count($items),
                'count_label' => count($items) . ' zwierząt',
                'animals' => $items,
            ];
        }

        return $groups;
    }

    private function buildColorGroupFilters(Request $request, Collection $groups, array $selectedIds): array
    {
        $baseQuery = $request->query();
        unset($baseQuery['page']);

        return $groups->map(function ($group) use ($baseQuery, $request, $selectedIds) {
            $id = (int) $group->id;
            $nextIds = $selectedIds;

            if (in_array($id, $nextIds, true)) {
                $nextIds = array_values(array_diff($nextIds, [$id]));
            } else {
                $nextIds[] = $id;
                $nextIds = array_values(array_unique($nextIds));
            }

            $query = $baseQuery;
            if ($nextIds === []) {
                unset($query['color_groups']);
            } else {
                $query['color_groups'] = $nextIds;
            }

            $queryString = http_build_query($query);

            return [
                'id' => $id,
                'name' => $group->name,
                'slug' => $group->slug,
                'is_active' => in_array($id, $selectedIds, true),
                'toggle_url' => $queryString ? $request->url() . '?' . $queryString : $request->url(),
            ];
        })->values()->all();
    }

    private function buildColorGroupClearUrl(Request $request): string
    {
        $query = $request->query();
        unset($query['page'], $query['color_groups']);

        $queryString = http_build_query($query);

        return $queryString ? $request->url() . '?' . $queryString : $request->url();
    }

    private function formatWeightLabel(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;
            $label = $numeric == (int) $numeric ? (string) (int) $numeric : rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');
        } else {
            $label = (string) $value;
        }

        return $label . ' g.';
    }

    private function buildNameDisplay(?string $secondName, ?string $name): string
    {
        $main = $this->sanitizeName($name);
        $second = $this->sanitizeName($secondName);

        if ($main === '' && $second === '') {
            return '-';
        }

        if ($second !== '') {
            if ($main === '') {
                return '<i>' . $second . '</i>';
            }

            return '<i>' . $second . '</i> ' . $main;
        }

        return $main;
    }

    private function encodeBackUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return base64_encode($query ? ($path . '?' . $query) : $path);
    }

    private function sanitizeName(?string $value): string
    {
        return trim(strip_tags((string) $value, '<b><i><u>'));
    }
}
