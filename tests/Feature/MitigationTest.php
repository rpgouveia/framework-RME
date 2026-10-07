<?php

use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\ReferenceDataset;
use App\Models\User;
use App\Support\InvalidMitigationCatalog;
use App\Support\MitigationCatalog;
use Database\Seeders\MitigationSeeder;
use Database\Seeders\TaxonomySeeder;
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
        'source_name' => 'Fairness impact assessment',
        'source_reference' => 'FICT-001',
        'source_document' => 'nist-2024',
        'saeri_subcategory' => '1.7',
        'description' => 'Avaliar periodicamente se as saídas tratam os grupos de forma equivalente.',
        'suggested_target_risk' => 'Viés contra grupos protegidos',
        'expected_evidence' => 'Relatório de auditoria assinado',
        'suggested_cost' => CostLevel::Medium->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'estimate_source' => 'Estimativa do grupo',
    ], $overrides);
}

/**
 * A catalogue file content: its metadata and entries.
 *
 * @param  list<mixed>  $entries
 * @param  array<string, mixed>  $meta
 * @return array<string, mixed>
 */
function catalogue(array $entries, array $meta = []): array
{
    return [
        'meta' => array_merge([
            'saeri_database_version' => 'Preliminar, dezembro de 2025',
            'accessed_at' => '2026-10-06',
            'curation_criterion' => 'Aplicáveis a quem implanta e opera sistemas de IA.',
            'fictional' => true,
        ], $meta),
        'entries' => $entries,
    ];
}

/**
 * Write a catalogue to a temporary file and return its path.
 */
function catalogueFile(mixed $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'catalogue');
    file_put_contents($path, is_string($content) ? $content : json_encode($content));

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

// The category is derived from the subcategory.

test('a mitigation takes its category from its subcategory', function () {
    $mitigation = Mitigation::factory()->inSubcategory('3.1')->create();

    expect($mitigation->saeriSubcategory->code)->toBe('3.1')
        ->and($mitigation->saeriSubcategory->parent->code)->toBe('3')
        ->and($mitigation->saeriSubcategory->parent->name)->toBe('Processos Operacionais');
});

// The index: filter by category and subcategory and search, on the server.

test('the index lists the catalogue with the two filter levels', function () {
    $mitigation = Mitigation::factory()->create();
    Link::factory(2)->for($mitigation)->create();

    $this->get(route('mitigations.index'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('mitigations/index')
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.links_count', 2)
            ->has('mitigations.data.0.saeri_subcategory.parent')
            ->has('categories', 4)
            ->has('categories.2.children', 6)
            ->where('filters', ['category' => null, 'subcategory' => null, 'q' => ''])
    );
});

test('the catalogue can be filtered by category', function () {
    Mitigation::factory()->inSubcategory('2.3')->create(['name' => 'Treinamento adversarial']);
    Mitigation::factory()->inSubcategory('2.4')->create(['name' => 'Filtragem de conteúdo']);
    Mitigation::factory()->inSubcategory('3.1')->create(['name' => 'Red teaming']);

    $this->get(route('mitigations.index', ['category' => '2']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 2)
            ->where('filters.category', '2')
            ->where('filters.subcategory', null)
    );
});

test('the catalogue can be filtered by subcategory', function () {
    Mitigation::factory()->inSubcategory('2.3')->create(['name' => 'Treinamento adversarial']);
    Mitigation::factory()->inSubcategory('2.4')->create(['name' => 'Filtragem de conteúdo']);

    $this->get(route('mitigations.index', ['category' => '2', 'subcategory' => '2.4']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Filtragem de conteúdo')
            ->where('filters', ['category' => '2', 'subcategory' => '2.4', 'q' => ''])
    );
});

test('a subcategory alone implies its category', function () {
    Mitigation::factory()->inSubcategory('2.4')->create();

    $this->get(route('mitigations.index', ['subcategory' => '2.4']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('filters.category', '2')
    );
});

test('a subcategory from another category is ignored', function () {
    Mitigation::factory()->inSubcategory('2.3')->create();
    Mitigation::factory()->inSubcategory('2.4')->create();

    $this->get(route('mitigations.index', ['category' => '2', 'subcategory' => '3.1']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 2)
            ->where('filters.subcategory', null)
    );
});

test('the search matches the Portuguese or the original name, ignoring letter case', function () {
    Mitigation::factory()->create(['name' => 'Revisão humana das decisões', 'source_name' => 'Human review']);
    Mitigation::factory()->create(['name' => 'Red teaming', 'source_name' => 'Red teaming exercises']);

    $this->get(route('mitigations.index', ['q' => 'HUMANA']))->assertInertia(
        fn (AssertableInertia $page) => $page->has('mitigations.data', 1)
    );

    $this->get(route('mitigations.index', ['q' => 'exercises']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Red teaming')
    );
});

test('a search term is matched literally, not as a pattern', function () {
    Mitigation::factory()->create(['name' => 'Cobertura de 100% dos testes']);
    Mitigation::factory()->create(['name' => 'Red teaming', 'source_name' => 'Red teaming']);

    $this->get(route('mitigations.index', ['q' => '%']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Cobertura de 100% dos testes')
    );
});

test('the filters and the search combine', function () {
    Mitigation::factory()->inSubcategory('3.1')->create(['name' => 'Testes de robustez', 'source_name' => 'Robustness testing']);
    Mitigation::factory()->inSubcategory('3.5')->create(['name' => 'Testes em produção', 'source_name' => 'Production checks']);
    Mitigation::factory()->inSubcategory('2.3')->create(['name' => 'Testes adversariais', 'source_name' => 'Adversarial tests']);

    $this->get(route('mitigations.index', ['category' => '3', 'q' => 'testes']))->assertInertia(
        fn (AssertableInertia $page) => $page->has('mitigations.data', 2)
    );

    $this->get(route('mitigations.index', ['category' => '3', 'subcategory' => '3.1', 'q' => 'testes']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('mitigations.data', 1)
            ->where('mitigations.data.0.name', 'Testes de robustez')
    );
});

test('the pages keep both filter levels and the search', function () {
    // Names that all match the search, so there is surely a second page.
    Mitigation::factory(20)
        ->inSubcategory('2.3')
        ->sequence(fn ($sequence) => ['name' => "Mitigação técnica {$sequence->index}"])
        ->create();

    $this->get(route('mitigations.index', ['category' => '2', 'subcategory' => '2.3', 'q' => 'a']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('mitigations.next_page_url', function (?string $url): bool {
            parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

            return $query === ['category' => '2', 'subcategory' => '2.3', 'q' => 'a', 'page' => '2'];
        })
    );
});

// The detail page and the fictional notice.

test('the detail page carries the Saeri block and the framework block apart', function () {
    $mitigation = Mitigation::factory()->inSubcategory('3.1')->create(['source_document' => 'nist-2024']);
    Link::factory()->for($mitigation)->create();

    $this->get(route('mitigations.show', $mitigation))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('mitigations/show')
            // Saeri et al.: classification and trace to the source.
            ->where('mitigation.saeri_subcategory.code', '3.1')
            ->where('mitigation.saeri_subcategory.original_name', 'Testing & Auditing')
            ->where('mitigation.saeri_subcategory.parent.code', '3')
            ->has('mitigation.source_name')
            ->has('mitigation.source_reference')
            ->where('sourceDocument.title', 'NIST AI Risk Management Framework: Generative AI Profile')
            ->has('taxonomy.citation')
            // The framework's contribution, with its source (RNF03).
            ->has('mitigation.estimate_source')
            ->has('mitigation.suggested_target_risk')
            ->has('mitigation.links', 1)
            ->has('mitigation.links.0.risk')
    );
});

test('the screens say when the loaded catalogue is fictional', function () {
    $mitigation = Mitigation::factory()->create();

    ReferenceDataset::create(['key' => MitigationCatalog::DATASET, 'metadata' => ['fictional' => true]]);

    $this->get(route('mitigations.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('catalog.fictional', true)
    );
    $this->get(route('mitigations.show', $mitigation))->assertInertia(
        fn (AssertableInertia $page) => $page->where('catalog.fictional', true)
    );

    ReferenceDataset::where('key', MitigationCatalog::DATASET)->update(['metadata' => ['fictional' => false]]);

    $this->get(route('mitigations.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('catalog.fictional', false)
    );
});

// The catalogue data file.

test('the seeder loads the catalogue and its metadata from the data file', function () {
    $file = json_decode((string) file_get_contents(MitigationCatalog::path()), true);

    $this->seed([TaxonomySeeder::class, MitigationSeeder::class]);

    $first = Mitigation::where('source_reference', $file['entries'][0]['source_reference'])->sole();

    expect(Mitigation::count())->toBe(count($file['entries']))
        ->and($first->saeriSubcategory->code)->toBe($file['entries'][0]['saeri_subcategory'])
        ->and(MitigationCatalog::loadedMeta()['fictional'])->toBe($file['meta']['fictional']);
});

test('the catalogue data file in the repository is valid', function () {
    // Guards the curated file: a mistake there breaks this test, not a seed.
    $data = app(MitigationCatalog::class)->read(MitigationCatalog::path());

    expect($data['entries'])->not->toBeEmpty();
});

test('a valid catalogue file is read as it is', function () {
    $data = app(MitigationCatalog::class)->read(catalogueFile(catalogue([catalogueEntry()])));

    expect($data['entries'])->toBe([catalogueEntry()])
        ->and($data['meta']['fictional'])->toBeTrue();
});

test('an invalid catalogue file is refused with a clear message', function (mixed $content, string $message) {
    expect(fn () => app(MitigationCatalog::class)->read(catalogueFile($content)))
        ->toThrow(InvalidMitigationCatalog::class, $message);
})->with([
    'not an object' => ['[]', 'deve ser um JSON com "meta" e uma lista "entries"'],
    'not JSON' => ['not json', 'deve ser um JSON com "meta"'],
    'unknown subcategory' => [catalogue([catalogueEntry(['saeri_subcategory' => '9.9'])]), 'A subcategoria 9.9 não existe'],
    'category instead of subcategory' => [catalogue([catalogueEntry(['saeri_subcategory' => '1'])]), 'A subcategoria 1 não existe'],
    'unknown document' => [catalogue([catalogueEntry(['source_document' => 'nowhere-2030'])]), 'O documento nowhere-2030 não está entre'],
    'repeated source reference' => [
        catalogue([catalogueEntry(), catalogueEntry(['name' => 'Outra', 'source_reference' => 'FICT-001'])]),
        'o source_reference "FICT-001" se repete nas entradas 1, 2',
    ],
    'repeated name' => [
        catalogue([catalogueEntry(), catalogueEntry(['name' => 'AUDITORIA de equidade', 'source_reference' => 'FICT-002'])]),
        'se repete nas entradas 1, 2',
    ],
    'cost outside the scale' => [catalogue([catalogueEntry(['suggested_cost' => 'huge'])]), 'entrada 1'],
    'missing field' => [catalogue([array_diff_key(catalogueEntry(), ['estimate_source' => true])]), 'estimate_source'],
    'unknown field' => [catalogue([catalogueEntry(['bibliography_source' => 'old'])]), 'campos desconhecidos: bibliography_source'],
    'fictional not a boolean' => [catalogue([catalogueEntry()], ['fictional' => 1]), 'meta:'],
    'missing curation criterion' => [catalogue([catalogueEntry()], ['curation_criterion' => null]), 'meta:'],
]);

test('every problem in the catalogue file is reported at once', function () {
    $file = catalogueFile(catalogue([
        catalogueEntry(['saeri_subcategory' => '9.9']),
        catalogueEntry(['name' => 'Red teaming', 'source_reference' => 'FICT-002', 'source_document' => 'nowhere-2030']),
    ], ['fictional' => 'sim']));

    try {
        app(MitigationCatalog::class)->read($file);
        $this->fail('The catalogue should have been refused.');
    } catch (InvalidMitigationCatalog $exception) {
        expect($exception->getMessage())
            ->toContain('meta:')
            ->toContain('entrada 1 ("Auditoria de equidade")')
            ->toContain('entrada 2 ("Red teaming")');
    }
});

test('a missing catalogue file is reported', function () {
    expect(fn () => app(MitigationCatalog::class)->read('/nowhere/catalogue.json'))
        ->toThrow(InvalidMitigationCatalog::class, 'não encontrado');
});

// Names and references stay unique.

test('the database refuses a mitigation name in another letter case', function () {
    Mitigation::factory()->create(['name' => 'Red teaming']);

    expect(fn () => Mitigation::factory()->create(['name' => 'red TEAMING']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the database refuses a repeated source reference', function () {
    Mitigation::factory()->create(['source_reference' => 'FICT-001']);

    expect(fn () => Mitigation::factory()->create(['source_reference' => 'FICT-001']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the factory never repeats a mitigation name, even past its list', function () {
    $names = Mitigation::factory(25)->create()->pluck('name')->map(fn (string $name): string => mb_strtolower($name));

    expect($names->unique())->toHaveCount(25);
});
