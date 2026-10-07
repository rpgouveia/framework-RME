<?php

namespace App\Http\Controllers;

use App\Enums\SaeriCategory;
use App\Models\Mitigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The mitigation catalogue (C2), read only: it is consulted (UC004) and every
 * mitigation comes from it (R-8). The catalogue is curated in a data file and
 * loaded by the MitigationSeeder, never edited through the app.
 */
class MitigationController extends Controller
{
    /**
     * Display the catalogue, filtered by SAERI category and searched by name.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Mitigation::class);

        $category = SaeriCategory::tryFrom((string) $request->query('saeri_category'));
        $search = trim((string) $request->query('q'));

        return Inertia::render('mitigations/index', [
            'mitigations' => Mitigation::query()
                ->when($category, fn (Builder $query) => $query->where('saeri_category', $category))
                ->when($search !== '', fn (Builder $query) => $query->whereRaw(
                    "lower(name) like ? escape '!'",
                    ['%'.$this->escapeLike(mb_strtolower($search)).'%'],
                ))
                ->withCount('links')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'saeri_category' => $category?->value,
                'q' => $search,
            ],
            'saeriCategories' => SaeriCategory::options(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitigation $mitigation): Response
    {
        Gate::authorize('view', $mitigation);

        return Inertia::render('mitigations/show', [
            'mitigation' => $mitigation->load(['links.risk', 'links.mitigation', 'links.owner']),
        ]);
    }

    /**
     * Match a search term literally, so "%" or "_" in it are not wildcards.
     */
    protected function escapeLike(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }
}
