<?php

namespace Database\Seeders;

use App\Models\AiSystem;
use App\Models\Risk;
use Illuminate\Database\Seeder;

class RiskSeeder extends Seeder
{
    /**
     * The risk profile of each system of the portfolio: the risks that make
     * sense for what the system does and for the relation the organization
     * has with it (0016). The names are the ones of the risk catalogue of the
     * RiskFactory, which brings the subdomain and the description of each.
     *
     * @var array<string, list<string>>
     */
    protected const PROFILES = [
        'Modelo de score de crédito' => [
            'Viés de seleção',
            'Discriminação algorítmica',
            'Falta de explicabilidade',
            'Deriva de dados',
            'Exposição de dados pessoais',
        ],
        'Triagem automática de currículos' => [
            'Viés de rótulo',
            'Discriminação algorítmica',
            'Falta de explicabilidade',
            'Dependência excessiva da automação',
            'Automação de postos de trabalho',
        ],
        'Apoio ao diagnóstico por imagem' => [
            'Desempenho desigual entre grupos',
            'Dependência excessiva da automação',
            'Degradação de desempenho',
            'Exposição de dados pessoais',
            'Falha de governança do fornecedor',
        ],
        'Detecção de fraudes em transações' => [
            'Ataque adversarial',
            'Deriva de dados',
            'Falta de explicabilidade',
            'Golpe com conteúdo sintético',
            'Envenenamento de dados',
        ],
        'Assistente virtual de atendimento' => [
            'Prompt injection',
            'Alucinação',
            'Vazamento de dados de treino',
            'Conteúdo tóxico nas respostas',
            'Falha de governança do fornecedor',
        ],
        'Chatbot de suporte técnico interno' => [
            'Jailbreak',
            'Alucinação',
            'Exposição de dados pessoais',
            'Uso do sistema para ciberataque',
            'Dependência excessiva da automação',
        ],
        'Moderação automática de comentários' => [
            'Conteúdo tóxico nas respostas',
            'Desempenho desigual entre grupos',
            'Discriminação algorítmica',
            'Perda de autonomia na decisão',
            'Uso indevido do sistema',
        ],
        'Recomendação de conteúdo do portal' => [
            'Poluição da base com conteúdo gerado',
            'Desinformação em escala',
            'Perda de autonomia na decisão',
            'Inferência de pertencimento',
        ],
        'Previsão de demanda de estoque' => [
            'Deriva de dados',
            'Degradação de desempenho',
            'Objetivo mal especificado',
            'Consumo energético da inferência',
        ],
        'Roteirização de entregas' => [
            'Degradação de desempenho',
            'Objetivo mal especificado',
            'Conflito entre agentes automatizados',
            'Consumo energético da inferência',
        ],
        'Reconhecimento de emoções no monitoramento de atendentes' => [
            'Perda de autonomia na decisão',
            'Discriminação algorítmica',
            'Exposição de dados pessoais',
        ],
        'Identificação biométrica remota em tempo real' => [
            'Exposição de dados pessoais',
            'Desempenho desigual entre grupos',
            'Uso indevido do sistema',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aiSystems = AiSystem::all();

        if ($aiSystems->isEmpty()) {
            $aiSystems = AiSystem::factory(3)->create();
        }

        foreach ($aiSystems as $aiSystem) {
            $profile = self::PROFILES[$aiSystem->name] ?? null;

            // A system outside the portfolio, such as the ones the trigger
            // seeders create, takes risks from the whole catalogue.
            if ($profile === null) {
                Risk::factory(fake()->numberBetween(2, 4))->for($aiSystem)->create();

                continue;
            }

            // Not every risk of the profile is registered yet.
            foreach (fake()->randomElements($profile, fake()->numberBetween(3, count($profile))) as $name) {
                Risk::factory()->for($aiSystem)->named($name)->create();
            }
        }
    }
}
