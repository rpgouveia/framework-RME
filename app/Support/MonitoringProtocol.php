<?php

namespace App\Support;

use App\Enums\AiSystemCategory;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

/**
 * The monitoring protocol (C3): what may happen to an AI system in production.
 *
 * An adverse event is a risk that materialized, so it is classified by the
 * same MIT AI risk subdomains as the risk register: one or more, never free
 * text and never "other". As the group decided, the EU AI Act tier of the
 * system sets how closely it is monitored (not yet modelled here), and its
 * risk profile sets which events are expected.
 *
 * The risk profile of a system is the set of subdomains of all its risks,
 * linked or not. The event form shows those first; every other subdomain
 * stays available, since an event outside the profile may reveal a risk not
 * yet identified. This is the one place that decides it.
 *
 * Its parameters (the review interval of each tier and the dashboard
 * windows) come from a versioned data file, validated as a whole, so a
 * change to the protocol is a reviewed change to that file (RNF05). There is
 * no interval of a system's own. Sections the file does not use yet, such as
 * the definition of an adverse event, may be added without breaking it.
 *
 * @phpstan-type Parameters array{meta: array{key: string, version: string, date: string, description: string}, review: array{intervals: array<string, array{days: int|null, justification: string}>}, dashboard: array{recent_event_days: int, upcoming_review_days: int}}
 */
class MonitoringProtocol
{
    /** @var Parameters|null */
    protected ?array $parameters = null;

    /**
     * @param  string|null  $path  Another protocol file, such as one a test
     *                             writes; the versioned one by default.
     */
    public function __construct(
        protected ?string $path = null,
    ) {}

    public static function path(): string
    {
        return database_path('data/protocols/c3-monitoring-protocol.json');
    }

    /**
     * The protocol in force, read once per request.
     *
     * @return Parameters
     */
    public function parameters(): array
    {
        return $this->parameters ??= $this->read($this->path ?? self::path());
    }

    /**
     * The version of the protocol, as the reports quote it.
     *
     * @return array{key: string, version: string, date: string}
     */
    public function version(): array
    {
        $meta = $this->parameters()['meta'];

        return ['key' => $meta['key'], 'version' => $meta['version'], 'date' => $meta['date']];
    }

    /**
     * Days between reviews for a tier; null for the unacceptable tier, which
     * is never reviewed because it never operates.
     */
    public function reviewIntervalDays(AiSystemCategory $tier): ?int
    {
        return $this->parameters()['review']['intervals'][$tier->value]['days'];
    }

    /**
     * The interval of every tier, keyed by tier, for the forms.
     *
     * @return array<string, int|null>
     */
    public function reviewIntervals(): array
    {
        $intervals = [];

        foreach (AiSystemCategory::cases() as $tier) {
            $intervals[$tier->value] = $this->reviewIntervalDays($tier);
        }

        return $intervals;
    }

    /**
     * R-7: a link is first reviewed one interval of its system's tier after
     * it was created, or never, when the system cannot operate.
     */
    public function nextReviewDate(AiSystem $aiSystem, CarbonImmutable $from): ?CarbonImmutable
    {
        $days = $this->reviewIntervalDays($aiSystem->category);

        return $days === null ? null : $from->addDays($days);
    }

    /** How far back the dashboard counts an adverse event as recent. */
    public function recentEventDays(): int
    {
        return $this->parameters()['dashboard']['recent_event_days'];
    }

    /** How far ahead the dashboard lists upcoming reviews. */
    public function upcomingReviewDays(): int
    {
        return $this->parameters()['dashboard']['upcoming_review_days'];
    }

    /**
     * Read and validate a protocol file, reporting every problem at once.
     *
     * @return Parameters
     *
     * @throws InvalidMonitoringProtocol When the file is missing, unreadable
     *                                   or holds an invalid parameter.
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidMonitoringProtocol("Arquivo do protocolo de monitoramento não encontrado: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || array_is_list($data)) {
            throw new InvalidMonitoringProtocol("O protocolo de monitoramento em {$path} deve ser um objeto JSON.");
        }

        $problems = [
            ...$this->metaProblems($data['meta'] ?? null),
            ...$this->intervalProblems($data['review']['intervals'] ?? null),
        ];

        foreach (['recent_event_days', 'upcoming_review_days'] as $window) {
            if (! $this->isPositiveInteger($data['dashboard'][$window] ?? null)) {
                $problems[] = "dashboard.{$window}: deve ser um número inteiro positivo de dias.";
            }
        }

        if ($problems !== []) {
            throw new InvalidMonitoringProtocol(
                "Protocolo de monitoramento inválido ({$path}):\n- ".implode("\n- ", $problems),
            );
        }

        /** @var Parameters $data */
        return $data;
    }

    /**
     * @return list<string>
     */
    protected function metaProblems(mixed $meta): array
    {
        if (! is_array($meta)) {
            return ['meta: deve ser um objeto com key, version, date e description.'];
        }

        $fields = ['key', 'version', 'date', 'description'];

        return array_values(array_map(fn (string $message): string => "meta: {$message}", Validator::make($meta, [
            'key' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string'],
        ], [], array_combine($fields, array_map(fn (string $field): string => "meta.{$field}", $fields)))->errors()->all()));
    }

    /**
     * Every tier is present; an operable one has a positive number of days,
     * the unacceptable one says it has none; each one says why.
     *
     * @return list<string>
     */
    protected function intervalProblems(mixed $intervals): array
    {
        if (! is_array($intervals) || array_is_list($intervals)) {
            return ['review.intervals: deve ser um objeto com uma entrada por faixa do EU AI Act.'];
        }

        $problems = [];

        foreach (AiSystemCategory::cases() as $tier) {
            $label = "review.intervals.{$tier->value}";
            $entry = $intervals[$tier->value] ?? null;

            if (! is_array($entry)) {
                $problems[] = "{$label}: a faixa está ausente.";

                continue;
            }

            if ($tier->isOperable() && ! $this->isPositiveInteger($entry['days'] ?? null)) {
                $problems[] = "{$label}.days: deve ser um número inteiro positivo de dias.";
            }

            if (! $tier->isOperable() && (! array_key_exists('days', $entry) || $entry['days'] !== null)) {
                $problems[] = "{$label}.days: deve ser null, porque a faixa não tem revisão periódica.";
            }

            if (! is_string($entry['justification'] ?? null) || trim($entry['justification']) === '') {
                $problems[] = "{$label}.justification: deve explicar a escolha do intervalo.";
            }
        }

        $tiers = array_map(fn (AiSystemCategory $tier): string => $tier->value, AiSystemCategory::cases());

        foreach (array_diff(array_keys($intervals), $tiers) as $unknown) {
            $problems[] = "review.intervals: a faixa \"{$unknown}\" não existe (use ".implode(', ', $tiers).').';
        }

        return $problems;
    }

    protected function isPositiveInteger(mixed $value): bool
    {
        return is_int($value) && $value > 0;
    }

    /**
     * Every subdomain, grouped by domain and described, for the event form.
     *
     * @return list<array{code: string, name: string, children: list<array<string, mixed>>}>
     */
    public function riskSubdomainOptions(): array
    {
        return array_values(array_map(
            fn (array $domain): array => [...$domain, 'children' => array_values($domain['children'])],
            app(AiRiskDomains::class)->tree(withDescriptions: true),
        ));
    }

    /**
     * The expected subdomains of each system, in taxonomy order, with how
     * many of its risks fall in each. A system with no risks expects none.
     *
     * @param  iterable<AiSystem>  $aiSystems
     * @return array<int, list<array{code: string, risks_count: int}>>
     */
    public function expectedRiskSubdomainsBySystem(iterable $aiSystems): array
    {
        $ids = [];

        foreach ($aiSystems as $aiSystem) {
            $ids[] = $aiSystem->id;
        }

        $profile = array_fill_keys($ids, []);
        $subdomains = app(AiRiskDomains::class)->subdomains()->keyBy('id');

        $counts = Risk::query()
            ->whereIn('ai_system_id', $ids)
            ->selectRaw('ai_system_id, risk_subdomain_id, count(*) as risks_count')
            ->groupBy('ai_system_id', 'risk_subdomain_id')
            ->toBase()
            ->get();

        foreach ($counts as $row) {
            /** @var TaxonomyTerm|null $subdomain */
            $subdomain = $subdomains->get((int) $row->risk_subdomain_id);

            if ($subdomain !== null) {
                $profile[(int) $row->ai_system_id][$subdomain->position] = [
                    'code' => $subdomain->code,
                    'risks_count' => (int) $row->risks_count,
                ];
            }
        }

        return array_map(function (array $expected): array {
            ksort($expected);

            return array_values($expected);
        }, $profile);
    }

    /**
     * The expected subdomains of one system.
     *
     * @return list<array{code: string, risks_count: int}>
     */
    public function expectedRiskSubdomainsFor(AiSystem $aiSystem): array
    {
        return $this->expectedRiskSubdomainsBySystem([$aiSystem])[$aiSystem->id];
    }
}
