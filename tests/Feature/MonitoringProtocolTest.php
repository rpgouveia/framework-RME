<?php

use App\Enums\AiSystemCategory;
use App\Support\InvalidMonitoringProtocol;
use App\Support\MonitoringProtocol;

/**
 * A valid protocol, as the data file holds it.
 *
 * @param  array<string, mixed>  $overrides  Top level sections to replace.
 * @return array<string, mixed>
 */
function protocolDefinition(array $overrides = []): array
{
    return array_replace([
        'meta' => [
            'key' => 'c3-monitoring-protocol',
            'version' => '9.9',
            'date' => '2026-10-08',
            'description' => 'Protocolo de teste.',
        ],
        'review' => [
            'intervals' => [
                'high' => ['days' => 30, 'justification' => 'Mensal.'],
                'limited' => ['days' => 60, 'justification' => 'Bimestral.'],
                'minimal' => ['days' => 120, 'justification' => 'Quadrimestral.'],
                'unacceptable' => ['days' => null, 'justification' => 'Não opera.'],
            ],
        ],
        'dashboard' => [
            'recent_event_days' => 7,
            'upcoming_review_days' => 3,
        ],
    ], $overrides);
}

/**
 * Write a protocol to a temporary file and return its path.
 */
function protocolFile(mixed $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'protocol');
    file_put_contents($path, is_string($content) ? $content : json_encode($content));

    return $path;
}

/**
 * The protocol definition with one review interval replaced.
 *
 * @return array<string, mixed>
 */
function protocolWithInterval(string $tier, mixed $entry): array
{
    $definition = protocolDefinition();
    $definition['review']['intervals'][$tier] = $entry;

    return $definition;
}

// The versioned protocol file.

test('the protocol file in the repository is valid', function () {
    // Guards the versioned file: a mistake there breaks this test.
    $protocol = app(MonitoringProtocol::class);

    expect($protocol->read(MonitoringProtocol::path())['meta']['key'])->toBe('c3-monitoring-protocol')
        ->and($protocol->version()['version'])->toBe('1.0');
});

test('the protocol sets the review interval of each EU AI Act tier', function () {
    $protocol = app(MonitoringProtocol::class);

    expect($protocol->reviewIntervalDays(AiSystemCategory::High))->toBe(90)
        ->and($protocol->reviewIntervalDays(AiSystemCategory::Limited))->toBe(180)
        ->and($protocol->reviewIntervalDays(AiSystemCategory::Minimal))->toBe(365)
        // Prohibited practices never operate, so they are never reviewed.
        ->and($protocol->reviewIntervalDays(AiSystemCategory::Unacceptable))->toBeNull()
        ->and($protocol->recentEventDays())->toBe(30)
        ->and($protocol->upcomingReviewDays())->toBe(14);
});

test('every interval of the versioned protocol says why', function () {
    $intervals = app(MonitoringProtocol::class)->parameters()['review']['intervals'];

    expect($intervals['high']['justification'])->toContain('Trimestral')
        ->and($intervals['limited']['justification'])->toContain('Semestral')
        ->and($intervals['minimal']['justification'])->toContain('Anual');
});

// Reading any protocol file.

test('a valid protocol file is read as it is', function () {
    $protocol = new MonitoringProtocol(protocolFile(protocolDefinition()));

    expect($protocol->reviewIntervalDays(AiSystemCategory::High))->toBe(30)
        ->and($protocol->upcomingReviewDays())->toBe(3)
        ->and($protocol->version())->toBe(['key' => 'c3-monitoring-protocol', 'version' => '9.9', 'date' => '2026-10-08']);
});

test('a section the protocol does not use yet does not break the reading', function () {
    // Room for the adverse event definition, still under discussion.
    $protocol = new MonitoringProtocol(protocolFile(protocolDefinition([
        'adverse_event' => ['definition' => 'Em decisão.'],
    ])));

    expect($protocol->recentEventDays())->toBe(7);
});

test('an invalid protocol file is refused with a clear message', function (array $definition, string $message) {
    expect(fn () => (new MonitoringProtocol)->read(protocolFile($definition)))
        ->toThrow(InvalidMonitoringProtocol::class, $message);
})->with([
    'missing tier' => [
        (function () {
            $definition = protocolDefinition();
            unset($definition['review']['intervals']['minimal']);

            return $definition;
        })(),
        'review.intervals.minimal: a faixa está ausente',
    ],
    'zero days' => [protocolWithInterval('high', ['days' => 0, 'justification' => 'x']), 'review.intervals.high.days: deve ser um número inteiro positivo'],
    'negative days' => [protocolWithInterval('limited', ['days' => -30, 'justification' => 'x']), 'review.intervals.limited.days'],
    'fractional days' => [protocolWithInterval('limited', ['days' => 90.5, 'justification' => 'x']), 'review.intervals.limited.days'],
    'days as text' => [protocolWithInterval('minimal', ['days' => '365', 'justification' => 'x']), 'review.intervals.minimal.days'],
    'no review missing for an operable tier' => [protocolWithInterval('high', ['days' => null, 'justification' => 'x']), 'review.intervals.high.days'],
    'a review for the unacceptable tier' => [protocolWithInterval('unacceptable', ['days' => 365, 'justification' => 'x']), 'review.intervals.unacceptable.days: deve ser null'],
    'unacceptable tier silent about its review' => [protocolWithInterval('unacceptable', ['justification' => 'x']), 'review.intervals.unacceptable.days: deve ser null'],
    'missing justification' => [protocolWithInterval('high', ['days' => 90]), 'review.intervals.high.justification'],
    'unknown tier' => [protocolWithInterval('extreme', ['days' => 7, 'justification' => 'x']), 'a faixa "extreme" não existe'],
    'window of zero days' => [protocolDefinition(['dashboard' => ['recent_event_days' => 0, 'upcoming_review_days' => 14]]), 'dashboard.recent_event_days'],
    'missing window' => [protocolDefinition(['dashboard' => ['recent_event_days' => 30]]), 'dashboard.upcoming_review_days'],
    'missing version' => [protocolDefinition(['meta' => ['key' => 'c3', 'date' => '2026-10-08', 'description' => 'x']]), 'meta: '],
    'date in another format' => [protocolDefinition(['meta' => ['key' => 'c3', 'version' => '1', 'date' => '08/10/2026', 'description' => 'x']]), 'meta: '],
]);

test('every problem in the protocol file is reported at once', function () {
    $definition = protocolDefinition([
        'meta' => ['key' => 'c3', 'date' => '2026-10-08', 'description' => 'x'],
        'dashboard' => ['recent_event_days' => -1, 'upcoming_review_days' => 14],
    ]);
    $definition['review']['intervals']['high']['days'] = 0;
    $definition['review']['intervals']['unacceptable']['days'] = 30;
    unset($definition['review']['intervals']['limited']);

    try {
        (new MonitoringProtocol)->read(protocolFile($definition));
        $this->fail('The protocol should have been refused.');
    } catch (InvalidMonitoringProtocol $exception) {
        expect($exception->getMessage())
            ->toContain('meta: ')
            ->toContain('review.intervals.high.days')
            ->toContain('review.intervals.limited: a faixa está ausente')
            ->toContain('review.intervals.unacceptable.days')
            ->toContain('dashboard.recent_event_days');
    }
});

test('a protocol file that is missing or not an object is refused', function () {
    expect(fn () => (new MonitoringProtocol)->read('/nowhere/protocol.json'))
        ->toThrow(InvalidMonitoringProtocol::class, 'não encontrado');

    expect(fn () => (new MonitoringProtocol)->read(protocolFile('[1, 2]')))
        ->toThrow(InvalidMonitoringProtocol::class, 'deve ser um objeto JSON');
});
