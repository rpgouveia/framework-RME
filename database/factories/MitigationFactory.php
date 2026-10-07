<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use App\Models\Mitigation;
use App\Support\AiRiskDomains;
use App\Support\SaeriTaxonomy;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mitigation>
 */
class MitigationFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * Common AI risk mitigation measures, so seeded data reads like a real
     * catalogue.
     *
     * @var list<string>
     */
    protected const NAMES = [
        'Auditoria de equidade',
        'Red teaming',
        'Filtragem de entradas do usuário',
        'Revisão humana das decisões',
        'Monitoramento de deriva de dados',
        'Anonimização dos dados de treino',
        'Privacidade diferencial',
        'Controle de acesso ao modelo',
        'Rebalanceamento do conjunto de treino',
        'Documentação com model cards',
        'Verificação de respostas com fontes',
        'Limitação de taxa de requisições',
        'Plano de resposta a incidentes',
        'Treinamento adversarial',
        'Explicações locais das predições',
        'Registro de auditoria das inferências',
        'Testes de robustez',
        'Retreinamento periódico',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Unique across the whole catalogue.
            'name' => fn (): string => $this->unusedName(self::NAMES, Mitigation::query()->pluck('name')->all()),
            'source_name' => fake()->sentence(3),
            'source_reference' => fn (): string => 'TEST-'.Str::upper(Str::random(12)),
            // Real subcategories and documents of the Saeri taxonomy, loaded
            // on demand in a fresh test database.
            'source_document' => fn (): string => (string) array_rand(app(SaeriTaxonomy::class)->documents()),
            'saeri_subcategory_id' => fn (): int => app(SaeriTaxonomy::class)->subcategories()->random()->id,
            'description' => fake()->sentence(),
            'suggested_target_risk' => fake()->sentence(),
            'expected_evidence' => fake()->sentence(),
            'suggested_cost' => fake()->randomElement(CostLevel::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'estimate_source' => 'Estimativa do grupo',
        ];
    }

    /**
     * Classify the mitigation under a Saeri subcategory, such as "1.2".
     */
    public function inSubcategory(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'saeri_subcategory_id' => app(SaeriTaxonomy::class)->subcategories()->firstWhere('code', $code)?->id,
        ]);
    }

    /**
     * Indicate that the measure is cheap to put in place.
     */
    public function lowCost(): static
    {
        return $this->state(fn (array $attributes) => [
            'suggested_cost' => CostLevel::Low,
        ]);
    }

    /**
     * Every catalogue entry treats at least one risk subdomain.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Mitigation $mitigation): void {
            if ($mitigation->targetRiskSubdomains()->doesntExist()) {
                $mitigation->targetRiskSubdomains()->attach(
                    app(AiRiskDomains::class)->subdomains()->random(fake()->numberBetween(1, 2))->pluck('id'),
                );
            }
        });
    }

    /**
     * Treat exactly these risk subdomains, such as ["2.2", "7.3"].
     *
     * @param  list<string>  $codes
     */
    public function targeting(array $codes): static
    {
        return $this->afterCreating(fn (Mitigation $mitigation) => $mitigation->targetRiskSubdomains()->sync(
            app(AiRiskDomains::class)->subdomains()->whereIn('code', $codes)->pluck('id'),
        ));
    }
}
