<?php

namespace Database\Factories;

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use App\Models\AiSystem;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSystem>
 */
class AiSystemFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * The systems of the demonstration portfolio: systems an organization
     * that deploys and operates AI would keep, which is the actor the
     * framework addresses (0024). Each entry carries the application domain,
     * the relation of the organization with the system and the EU AI Act
     * tier it falls in, so the three read coherently on the screens.
     *
     * Every entry has an application domain of its own, so a portfolio built
     * from the whole list shows one system per domain.
     *
     * @var array<string, array{application_domain: string, source_type: SystemSourceType, category: AiSystemCategory}>
     */
    public const SYSTEMS = [
        'Modelo de score de crédito' => [
            'application_domain' => 'Crédito e concessão financeira',
            'source_type' => SystemSourceType::Internal,
            'category' => AiSystemCategory::High,
        ],
        'Triagem automática de currículos' => [
            'application_domain' => 'Recrutamento e seleção',
            'source_type' => SystemSourceType::Hybrid,
            'category' => AiSystemCategory::High,
        ],
        'Apoio ao diagnóstico por imagem' => [
            'application_domain' => 'Apoio a decisões clínicas',
            'source_type' => SystemSourceType::ThirdParty,
            'category' => AiSystemCategory::High,
        ],
        'Detecção de fraudes em transações' => [
            'application_domain' => 'Prevenção a fraudes',
            'source_type' => SystemSourceType::Internal,
            'category' => AiSystemCategory::High,
        ],
        'Assistente virtual de atendimento' => [
            'application_domain' => 'Atendimento ao cliente',
            'source_type' => SystemSourceType::ThirdParty,
            'category' => AiSystemCategory::Limited,
        ],
        'Chatbot de suporte técnico interno' => [
            'application_domain' => 'Suporte técnico interno',
            'source_type' => SystemSourceType::Hybrid,
            'category' => AiSystemCategory::Limited,
        ],
        'Moderação automática de comentários' => [
            'application_domain' => 'Moderação de conteúdo',
            'source_type' => SystemSourceType::OpenSource,
            'category' => AiSystemCategory::Limited,
        ],
        'Recomendação de conteúdo do portal' => [
            'application_domain' => 'Recomendação de conteúdo',
            'source_type' => SystemSourceType::OpenSource,
            'category' => AiSystemCategory::Minimal,
        ],
        'Previsão de demanda de estoque' => [
            'application_domain' => 'Planejamento de demanda',
            'source_type' => SystemSourceType::Internal,
            'category' => AiSystemCategory::Minimal,
        ],
        'Roteirização de entregas' => [
            'application_domain' => 'Logística e distribuição',
            'source_type' => SystemSourceType::Internal,
            'category' => AiSystemCategory::Minimal,
        ],
    ];

    /**
     * Prohibited practices of the EU AI Act (art. 5), which fall in the
     * unacceptable tier: they may be registered, to plan the discontinuation,
     * but are never considered in operation (0017).
     *
     * @var array<string, array{application_domain: string, source_type: SystemSourceType, category: AiSystemCategory}>
     */
    public const PROHIBITED = [
        'Reconhecimento de emoções no monitoramento de atendentes' => [
            'application_domain' => 'Gestão de pessoas',
            'source_type' => SystemSourceType::ThirdParty,
            'category' => AiSystemCategory::Unacceptable,
        ],
        'Identificação biométrica remota em tempo real' => [
            'application_domain' => 'Segurança patrimonial',
            'source_type' => SystemSourceType::ThirdParty,
            'category' => AiSystemCategory::Unacceptable,
        ],
    ];

    /**
     * Define the model's default state.
     *
     * The tier comes from the system drawn, and is always an operable one, so
     * a link of it gets a review date; ask for unacceptable() explicitly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->unusedName(array_keys(self::SYSTEMS), AiSystem::query()->pluck('name')->all());

        return [
            'name' => $name,
            ...$this->curated(self::SYSTEMS, $name),
            'registration_date' => fake()->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Indicate that the system falls in the unacceptable tier: a prohibited
     * practice, never in operation.
     */
    public function unacceptable(): static
    {
        return $this->state(function (array $attributes): array {
            $name = $this->unusedName(array_keys(self::PROHIBITED), AiSystem::query()->pluck('name')->all());

            return ['name' => $name, ...$this->curated(self::PROHIBITED, $name)];
        });
    }

    /**
     * Indicate that the system falls in the high risk tier.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => AiSystemCategory::High,
        ]);
    }

    /**
     * The curated attributes of a system, by the name the factory drew: the
     * trait numbers a name once the list runs out, so the number is dropped
     * before the lookup.
     *
     * @param  array<string, array<string, mixed>>  $systems
     * @return array<string, mixed>
     */
    protected function curated(array $systems, string $name): array
    {
        return $systems[(string) preg_replace('/ \d+$/', '', $name)];
    }
}
