<?php

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use App\Support\AiRiskDomains;
use App\Support\InvalidTaxonomy;
use App\Support\SaeriTaxonomy;
use App\Support\TaxonomyFile;
use Database\Seeders\TaxonomySeeder;

/**
 * A valid taxonomy definition, as a data file holds it.
 *
 * @param  array<string, mixed>  $meta
 * @param  list<array<string, mixed>>|null  $terms
 * @return array<string, mixed>
 */
function taxonomyDefinition(array $meta = [], ?array $terms = null): array
{
    return [
        'meta' => array_merge([
            'key' => 'example',
            'name' => 'Exemplo',
            'citation' => 'Autor (2025). Título.',
            'version' => '1.0',
            'url' => 'https://example.com',
            'accessed_at' => '2026-10-06',
            'documents' => [['key' => 'doc-a', 'title' => 'Documento A']],
        ], $meta),
        'terms' => $terms ?? [
            ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'Categoria', 'original_name' => 'Category'],
            ['code' => '1.1', 'parent' => '1', 'level' => 2, 'name' => 'Subcategoria', 'original_name' => 'Subcategory', 'description' => 'A description.'],
        ],
    ];
}

/**
 * Write a taxonomy to a temporary file and return its path.
 */
function taxonomyFile(mixed $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'taxonomy');
    file_put_contents($path, is_string($content) ? $content : json_encode($content));

    return $path;
}

// The Saeri et al. taxonomy shipped with the project.

test('the Saeri taxonomy file in the repository is valid', function () {
    $definition = app(TaxonomyFile::class)->read(SaeriTaxonomy::path());

    expect($definition['meta']['key'])->toBe(SaeriTaxonomy::KEY);
});

test('the Saeri taxonomy has its four categories and 23 subcategories', function () {
    $saeri = app(SaeriTaxonomy::class);

    expect($saeri->categories()->pluck('code')->all())->toBe(['1', '2', '3', '4'])
        ->and($saeri->categories()->pluck('name')->all())->toBe([
            'Governança e Supervisão',
            'Técnica e Segurança',
            'Processos Operacionais',
            'Transparência e Responsabilização',
        ])
        ->and($saeri->categories()->map(fn (TaxonomyTerm $category) => $category->children->count())->all())->toBe([7, 4, 6, 6])
        ->and($saeri->subcategories())->toHaveCount(23);
});

test('a Saeri subcategory keeps its original name and description', function () {
    $term = app(SaeriTaxonomy::class)->subcategories()->firstWhere('code', '3.1');

    expect($term->original_name)->toBe('Testing & Auditing')
        ->and($term->description)->toStartWith('Systematic internal and external evaluations')
        ->and($term->parent->code)->toBe('3')
        ->and($term->level)->toBe(2);
});

test('the Saeri taxonomy lists the 13 source documents of the evidence scan', function () {
    $documents = app(SaeriTaxonomy::class)->documents();

    expect($documents)->toHaveCount(13)
        ->and($documents['nist-2024']['title'])->toBe('NIST AI Risk Management Framework: Generative AI Profile');
});

// The MIT AI Risk Repository domain taxonomy shipped with the project.

test('the MIT risk domain taxonomy file in the repository is valid', function () {
    $definition = app(TaxonomyFile::class)->read(AiRiskDomains::path());

    expect($definition['meta']['key'])->toBe(AiRiskDomains::KEY)
        ->and($definition['meta']['url'])->toBe('https://airisk.mit.edu')
        ->and($definition['meta']['version_notes'])->toContain('7.6');
});

test('the MIT taxonomy has its seven domains and 24 subdomains', function () {
    $domains = app(AiRiskDomains::class);

    expect($domains->domains()->pluck('code')->all())->toBe(['1', '2', '3', '4', '5', '6', '7'])
        ->and($domains->domains()->map(fn (TaxonomyTerm $domain) => $domain->children->count())->all())->toBe([3, 2, 2, 3, 2, 6, 6])
        ->and($domains->subdomains())->toHaveCount(24)
        // Added in the April 2025 update; the 2024 preprint had 23.
        ->and($domains->subdomains()->firstWhere('code', '7.6')->original_name)->toBe('Multi-agent risks');
});

test('an MIT subdomain keeps its original name and description', function () {
    $term = app(AiRiskDomains::class)->subdomains()->firstWhere('code', '2.2');

    expect($term->original_name)->toBe('AI system security vulnerabilities and attacks')
        ->and($term->name)->toBe('Vulnerabilidades de segurança e ataques a sistemas de IA')
        ->and($term->description)->toStartWith('Vulnerabilities that can be exploited in AI systems')
        ->and($term->parent->original_name)->toBe('Privacy & security');
});

test('the seeder loads every taxonomy file', function () {
    $this->seed(TaxonomySeeder::class);

    expect(Taxonomy::query()->orderBy('key')->pluck('key')->all())->toBe([AiRiskDomains::KEY, SaeriTaxonomy::KEY])
        ->and(Taxonomy::firstWhere('key', SaeriTaxonomy::KEY)->terms()->count())->toBe(27)
        ->and(Taxonomy::firstWhere('key', AiRiskDomains::KEY)->terms()->count())->toBe(31)
        ->and(Taxonomy::firstWhere('key', SaeriTaxonomy::KEY)->accessed_at->toDateString())->toBe('2026-10-06');
});

test('the seeder keeps a taxonomy that is already loaded', function () {
    $loaded = app(AiRiskDomains::class)->taxonomy();

    $this->seed(TaxonomySeeder::class);

    expect(Taxonomy::count())->toBe(2)
        ->and(Taxonomy::firstWhere('key', AiRiskDomains::KEY)->id)->toBe($loaded->id);
});

// Loading any taxonomy file.

test('a valid taxonomy is stored with its tree', function () {
    $taxonomy = app(TaxonomyFile::class)->load(taxonomyFile(taxonomyDefinition()));

    $child = $taxonomy->terms()->where('code', '1.1')->sole();

    expect($child->parent->code)->toBe('1')
        ->and($taxonomy->metadata['documents'][0]['key'])->toBe('doc-a');
});

test('an invalid taxonomy is refused with a clear message', function (array $definition, string $message) {
    expect(fn () => app(TaxonomyFile::class)->read(taxonomyFile($definition)))
        ->toThrow(InvalidTaxonomy::class, $message);
})->with([
    'repeated code' => [taxonomyDefinition(terms: [
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'A', 'original_name' => 'A'],
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'B', 'original_name' => 'B'],
    ]), 'o código se repete'],
    'missing parent' => [taxonomyDefinition(terms: [
        ['code' => '1.1', 'parent' => '9', 'level' => 2, 'name' => 'A', 'original_name' => 'A'],
    ]), 'o pai "9" não existe'],
    'level not below its parent' => [taxonomyDefinition(terms: [
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'A', 'original_name' => 'A'],
        ['code' => '1.1', 'parent' => '1', 'level' => 3, 'name' => 'B', 'original_name' => 'B'],
    ]), 'level 3 não é coerente com o pai "1"'],
    'root not at level 1' => [taxonomyDefinition(terms: [
        ['code' => '1', 'parent' => null, 'level' => 2, 'name' => 'A', 'original_name' => 'A'],
    ]), 'um termo sem pai deve ter level 1'],
    'missing original name' => [taxonomyDefinition(terms: [
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'A'],
    ]), 'original_name'],
    'unknown field' => [taxonomyDefinition(terms: [
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'A', 'original_name' => 'A', 'nmae' => 'typo'],
    ]), 'campos desconhecidos: nmae'],
    'missing citation' => [taxonomyDefinition(['citation' => null]), 'meta:'],
    'repeated document key' => [taxonomyDefinition(['documents' => [
        ['key' => 'doc', 'title' => 'A'],
        ['key' => 'doc', 'title' => 'B'],
    ]]), 'a chave de documento "doc" se repete'],
]);

test('every problem in a taxonomy file is reported at once', function () {
    $file = taxonomyFile(taxonomyDefinition(['url' => 'not a url'], [
        ['code' => '1', 'parent' => null, 'level' => 1, 'name' => 'A', 'original_name' => 'A'],
        ['code' => '1.1', 'parent' => '9', 'level' => 2, 'name' => 'B', 'original_name' => 'B'],
        ['code' => '1.2', 'parent' => '1', 'level' => 5, 'name' => 'C', 'original_name' => 'C'],
    ]));

    try {
        app(TaxonomyFile::class)->read($file);
        $this->fail('The taxonomy should have been refused.');
    } catch (InvalidTaxonomy $exception) {
        expect($exception->getMessage())
            ->toContain('meta:')
            ->toContain('termo 2 ("1.1")')
            ->toContain('termo 3 ("1.2")');
    }

    expect(Taxonomy::count())->toBe(0);
});

test('a taxonomy file that is not a definition is refused', function () {
    expect(fn () => app(TaxonomyFile::class)->read(taxonomyFile('[1, 2]')))
        ->toThrow(InvalidTaxonomy::class, 'deve ser um JSON com "meta" e uma lista "terms"');

    expect(fn () => app(TaxonomyFile::class)->read('/nowhere/taxonomy.json'))
        ->toThrow(InvalidTaxonomy::class, 'não encontrado');
});
