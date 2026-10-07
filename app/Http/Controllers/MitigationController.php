<?php

namespace App\Http\Controllers;

use App\Models\Mitigation;
use App\Support\AiRiskDomains;
use App\Support\MitigationCatalog;
use App\Support\SaeriTaxonomy;
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
    public function __construct(
        protected SaeriTaxonomy $saeri,
    ) {}

    /**
     * Display the catalogue, filtered by Saeri category and subcategory and
     * searched by name, on the server.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Mitigation::class);

        $categories = $this->saeri->categories();
        $subcategory = $categories->flatMap->children->firstWhere('code', (string) $request->query('subcategory'));
        $category = $categories->firstWhere('code', (string) $request->query('category'));

        // A subcategory implies its category; one from another category is
        // ignored rather than emptying the list.
        if ($subcategory !== null && $category !== null && $subcategory->parent_id !== $category->id) {
            $subcategory = null;
        }

        $category ??= $subcategory === null ? null : $categories->firstWhere('id', $subcategory->parent_id);
        $search = trim((string) $request->query('q'));

        return Inertia::render('mitigations/index', [
            'mitigations' => Mitigation::query()
                ->when($subcategory, fn (Builder $query) => $query->where('saeri_subcategory_id', $subcategory?->id))
                ->when($subcategory === null && $category !== null, fn (Builder $query) => $query->whereIn(
                    'saeri_subcategory_id',
                    $category?->children->pluck('id') ?? [],
                ))
                // The Portuguese name or Saeri's original one.
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $term = '%'.$this->escapeLike(mb_strtolower($search)).'%';

                    $query->where(fn (Builder $match) => $match
                        ->whereRaw("lower(name) like ? escape '!'", [$term])
                        ->orWhereRaw("lower(source_name) like ? escape '!'", [$term]));
                })
                ->with('saeriSubcategory.parent')
                ->withCount('links')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'category' => $category?->code,
                'subcategory' => $subcategory?->code,
                'q' => $search,
            ],
            'categories' => $this->saeri->tree(),
            'catalog' => $this->catalog(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitigation $mitigation): Response
    {
        Gate::authorize('view', $mitigation);

        $taxonomy = $this->saeri->taxonomy();

        return Inertia::render('mitigations/show', [
            'mitigation' => $mitigation->load([
                'saeriSubcategory.parent',
                'targetRiskSubdomains.parent',
                'links.risk',
                'links.mitigation',
                'links.owner',
            ]),
            'sourceDocument' => $this->saeri->documents()[$mitigation->source_document] ?? null,
            'taxonomy' => $taxonomy->only(['citation', 'version', 'url']),
            'riskTaxonomy' => app(AiRiskDomains::class)->taxonomy()->only(['citation', 'version', 'url']),
            'catalog' => $this->catalog(),
        ]);
    }

    /**
     * What the screens tell about the catalogue as loaded.
     *
     * @return array{fictional: bool}
     */
    protected function catalog(): array
    {
        return ['fictional' => (bool) (MitigationCatalog::loadedMeta()['fictional'] ?? false)];
    }

    /**
     * Match a search term literally, so "%" or "_" in it are not wildcards.
     */
    protected function escapeLike(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }
}
