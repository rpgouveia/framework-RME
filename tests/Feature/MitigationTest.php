<?php

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\User;
use App\Support\InvalidMitigationCatalog;
use App\Support\MitigationCatalog;
use Database\Seeders\MitigationSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A valid catalogue entry, as the data file holds it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function catalogueEntry(array $overrides = []): array
{
    return array_merge([
        'name' => 'Auditoria de equidade',
        'saeri_category' => SaeriCategory::Governance->value,
        'description' => 'Avaliar periodicamente se as saídas tratam os grupos de forma equivalente.',
        'suggested_target_risk' => 'Viés contra grupos protegidos',
        'expected_evidence' => 'Relatório de auditoria assinado',
        'suggested_cost' => CostLevel::Medium->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'bibliography_source' => 'Saeri et al.',
    ], $overrides);
}

/**
 * Write catalogue entries to a temporary file and return its path.
 *
 * @param  mixed  $entries
 */
function catalogueFile($entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'catalogue');
    file_put_contents($path, is_string($entries) ? $entries : json_encode($entries));

    return $path;
}

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('mitigations.index'))->assertRedirect(route('login'));
});

// The catalogue is consulted, never edited (UC004, R-8).

test('the catalogue cannot be changed through the app', function () {
    $mitigation = Mitigation::factory()->create();

    $this->get('/mitigations/create')->assertNotFound();
    $this->post('/mitigations', catalogueEntry())->assertMethodNotAllowed();
    $this->get("/mitigations/{$mitigation->id}/edit")->assertNotFound();
    $this->put("/mitigations/{$mitigation->id}", catalogueEntry())->assertMethodNotAllowed();
    $this->delete("/mitigations/{$mitigation->id}")->assertMethodNotAllowed();

    expect(Mitigation::count())->toBe(1);
});

// The index: filter by SAERI category and search by name, on the server.

test('the index lists the catalogue with its link counts', function () {
    $mitigation = Mitigation::factory()->create();
    Link::factory(2)->for($mitigation)->create();

    $this->get(route('mitigations.index'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('mitigations/index')
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.links_count', 2)
            ->has('saeriCategories', 4)
            ->where('filters', ['saeri_category' => null, 'q' => ''])
    );
});

test('the catalogue can be filtered by SAERI category', function () {
    Mitigation::factory()->create(['name' => 'Red teaming', 'saeri_category' => SaeriCategory::Technical]);
    Mitigation::factory()->create(['name' => 'Auditoria de equidade', 'saeri_category' => SaeriCategory::Governance]);

    $this->get(route('mitigations.index', ['saeri_category' => 'technical']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Red teaming')
            ->where('filters.saeri_category', 'technical')
    );
});

test('an unknown category is ignored rather than emptying the list', function () {
    Mitigation::factory(2)->create();

    $this->get(route('mitigations.index', ['saeri_category' => 'nonsense']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 2)
            ->where('filters.saeri_category', null)
    );
});

test('the catalogue can be searched by name, ignoring letter case', function () {
    Mitigation::factory()->create(['name' => 'Revisão humana das decisões']);
    Mitigation::factory()->create(['name' => 'Red teaming']);

    $this->get(route('mitigations.index', ['q' => 'HUMANA']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Revisão humana das decisões')
    );
});

test('a search term is matched literally, not as a pattern', function () {
    Mitigation::factory()->create(['name' => 'Cobertura de 100% dos testes']);
    Mitigation::factory()->create(['name' => 'Red teaming']);

    $this->get(route('mitigations.index', ['q' => '%']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Cobertura de 100% dos testes')
    );
});

test('the category filter and the search combine', function () {
    Mitigation::factory()->create(['name' => 'Testes de robustez', 'saeri_category' => SaeriCategory::Process]);
    Mitigation::factory()->create(['name' => 'Testes adversariais', 'saeri_category' => SaeriCategory::Technical]);
    Mitigation::factory()->create(['name' => 'Retreinamento periódico', 'saeri_category' => SaeriCategory::Process]);

    $this->get(route('mitigations.index', ['saeri_category' => 'process', 'q' => 'testes']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Testes de robustez')
            ->where('filters', ['saeri_category' => 'process', 'q' => 'testes'])
    );
});

test('the pages keep the category filter and the search', function () {
    // Names that all match the search, so there is surely a second page.
    Mitigation::factory(20)
        ->sequence(fn ($sequence) => ['name' => "Mitigação técnica {$sequence->index}"])
        ->create(['saeri_category' => SaeriCategory::Technical]);

    $this->get(route('mitigations.index', ['saeri_category' => 'technical', 'q' => 'a']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('mitigations.next_page_url', function (?string $url): bool {
            parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

            return $query === ['saeri_category' => 'technical', 'q' => 'a', 'page' => '2'];
        })
    );
});

// The detail page.

test('the detail page loads the links that apply the mitigation', function () {
    $mitigation = Mitigation::factory()->create();
    Link::factory()->for($mitigation)->create();

    $this->get(route('mitigations.show', $mitigation))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('mitigations/show')
            ->has('mitigation.links', 1)
            ->has('mitigation.links.0.risk')
            ->has('mitigation.links.0.mitigation')
            ->has('mitigation.links.0.owner')
    );
});

// The catalogue data file.

test('the seeder loads the catalogue from its data file', function () {
    $entries = json_decode((string) file_get_contents(database_path('data/mitigation-catalog.json')), true);

    $this->seed(MitigationSeeder::class);

    expect(Mitigation::count())->toBe(count($entries))
        ->and(Mitigation::where('name', $entries[0]['name'])->exists())->toBeTrue();
});

test('the catalogue data file in the repository is valid', function () {
    // Guards the curated file: a mistake there breaks this test, not a seed.
    $entries = app(MitigationCatalog::class)->entries(database_path('data/mitigation-catalog.json'));

    expect($entries)->not->toBeEmpty();
});

test('a valid catalogue file is read as it is', function () {
    $entries = app(MitigationCatalog::class)->entries(catalogueFile([catalogueEntry()]));

    expect($entries)->toBe([catalogueEntry()]);
});

test('an invalid catalogue file is refused with a clear message', function (mixed $content, string $message) {
    expect(fn () => app(MitigationCatalog::class)->entries(catalogueFile($content)))
        ->toThrow(InvalidMitigationCatalog::class, $message);
})->with([
    'not a list' => ['{"name": "Red teaming"}', 'deve ser um JSON com uma lista'],
    'not JSON' => ['not json', 'deve ser um JSON com uma lista'],
    'unknown category' => [[catalogueEntry(['saeri_category' => 'ethics'])], 'entrada 1 ("Auditoria de equidade")'],
    'cost outside the scale' => [[catalogueEntry(['suggested_cost' => 'huge'])], 'entrada 1'],
    'uncertainty outside the scale' => [[catalogueEntry(['uncertainty_level' => 'unknown'])], 'entrada 1'],
    'missing field' => [[array_diff_key(catalogueEntry(), ['expected_evidence' => true])], 'entrada 1'],
    'unknown field' => [[catalogueEntry(['bibliografy' => 'typo'])], 'campos desconhecidos: bibliografy'],
    'repeated name' => [
        [catalogueEntry(), catalogueEntry(['name' => 'AUDITORIA de equidade'])],
        'se repete nas entradas 1, 2',
    ],
]);

test('every problem in the catalogue file is reported at once', function () {
    $file = catalogueFile([
        catalogueEntry(['saeri_category' => 'ethics']),
        catalogueEntry(['name' => 'Red teaming', 'suggested_cost' => 'huge']),
    ]);

    try {
        app(MitigationCatalog::class)->entries($file);
        $this->fail('The catalogue should have been refused.');
    } catch (InvalidMitigationCatalog $exception) {
        expect($exception->getMessage())
            ->toContain('entrada 1 ("Auditoria de equidade")')
            ->toContain('entrada 2 ("Red teaming")');
    }
});

test('a missing catalogue file is reported', function () {
    expect(fn () => app(MitigationCatalog::class)->entries('/nowhere/catalogue.json'))
        ->toThrow(InvalidMitigationCatalog::class, 'não encontrado');
});

// Names stay unique, so the catalogue can be picked by name.

test('the database refuses a mitigation name in another letter case', function () {
    Mitigation::factory()->create(['name' => 'Red teaming']);

    expect(fn () => Mitigation::factory()->create(['name' => 'red TEAMING']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the factory never repeats a mitigation name, even past its list', function () {
    $names = Mitigation::factory(25)->create()->pluck('name')->map(fn (string $name): string => mb_strtolower($name));

    expect($names->unique())->toHaveCount(25);
});
