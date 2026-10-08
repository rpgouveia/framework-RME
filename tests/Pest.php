<?php

use App\Support\AiRiskDomains;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * The id of a subdomain of the MIT AI risk domain taxonomy, such as "2.2",
 * loading the taxonomy if the test has not yet.
 */
function riskSubdomainId(string $code): int
{
    return app(AiRiskDomains::class)->subdomains()->firstWhere('code', $code)->id;
}

/**
 * Parse a streamed CSV download into rows, dropping the byte order mark.
 *
 * @return list<list<string|null>>
 */
function parseCsv(string $content): array
{
    $lines = preg_split('/\R/', trim(str_replace("\u{FEFF}", '', $content)));

    return array_map(fn (string $line): array => str_getcsv($line, ';', escape: ''), $lines);
}
