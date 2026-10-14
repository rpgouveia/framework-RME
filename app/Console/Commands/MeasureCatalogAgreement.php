<?php

namespace App\Console\Commands;

use App\Support\CatalogAgreement;
use App\Support\InvalidMitigationCatalog;
use App\Support\MitigationCatalogSource;
use Illuminate\Console\Command;

/**
 * Compute the OE1 agreement measure (0025) from the raw double annotation in
 * the exported "Catálogo" sheet: Krippendorff's alpha with the MASI distance
 * and its bootstrap interval, the per subdomain measures and their means.
 * The result is shown as tables and written to a file that only changes when
 * the sheet does. The official OE1 result comes from a strict run, where a
 * row with only one annotator is an error rather than a warning.
 */
class MeasureCatalogAgreement extends Command
{
    protected $signature = 'mitigations:agreement
        {--csv= : The exported "Catálogo" sheet (default: database/data/catalog-source/catalog.csv)}
        {--output= : The report file to write (default: database/data/catalog-source/oe1-agreement.json)}
        {--strict : Refuse rows with only one annotator, for the official OE1 result}';

    protected $description = 'Compute the agreement between the catalogue annotators (OE1)';

    /**
     * Execute the console command.
     */
    public function handle(CatalogAgreement $agreement): int
    {
        $output = $this->option('output') ?: CatalogAgreement::path();

        try {
            $result = $agreement->measure($this->option('csv') ?: MitigationCatalogSource::csvPath(), (bool) $this->option('strict'));
        } catch (InvalidMitigationCatalog $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['left_out'] !== []) {
            $this->warn('Linhas fora da medida, sem os subdomínios dos dois anotadores:');

            foreach ($result['left_out'] as $line) {
                $this->line("- {$line}");
            }
        }

        $report = $result['report'];
        $interval = $report['confidence_interval'];

        $this->table(['Medida', 'Valor'], [
            ['Cálculo', $report['strict'] ? 'estrito' : 'não estrito (linhas incompletas ficam de fora)'],
            ['Mitigações (N)', $report['n']],
            ['Linhas fora da medida', $report['left_out']],
            ['Alfa de Krippendorff (MASI)', self::number($report['alpha'])],
            ['Discordância observada (Do)', self::number($report['observed_disagreement'])],
            ['Discordância esperada (De)', self::number($report['expected_disagreement'])],
            ['IC 95% (percentil)', self::number($interval['lower']).' a '.self::number($interval['upper'])],
            ['Reamostragens descartadas (De = 0)', "{$interval['discarded']} de {$interval['resamples']}"],
            ['Conjuntos idênticos', self::number($report['identical_sets'])],
            ['Mínimo ('.self::number($report['minimum']['threshold']).')', $report['minimum']['met'] ? 'atende' : 'não atende'],
            ['Meta ('.self::number($report['target']['threshold']).')', $report['target']['met'] ? 'atinge' : 'não atinge'],
        ]);

        $marked = array_filter($report['subdomains'], fn (array $code): bool => $code['positive_agreement'] !== null);

        $this->table(
            ['Subdomínio', 'Ambos', 'Só A', 'Só B', 'po', 'g', 'AC1', 'Conc. positiva'],
            array_map(fn (array $code): array => [
                "{$code['code']} {$code['name']}",
                $code['both'],
                $code['only_a'],
                $code['only_b'],
                self::number($code['observed_agreement']),
                self::number($code['g']),
                self::number($code['ac1']),
                self::number($code['positive_agreement']),
            ], $marked),
        );

        $summary = $report['subdomain_summary'];

        $this->table(["Subdomínios marcados ({$summary['marked']} de ".count($report['subdomains']).')', 'Média', 'Mínimo', 'Máximo'], [
            ['AC1 de Gwet', ...array_map(self::number(...), array_values($summary['ac1']))],
            ['g de Guilford', ...array_map(self::number(...), array_values($summary['g']))],
        ]);

        file_put_contents($output, CatalogAgreement::encode($report));

        $this->info("Resultado gravado em {$output}.");

        return self::SUCCESS;
    }

    /** Three decimals with a decimal comma, or a dash when undefined. */
    protected static function number(mixed $value): string
    {
        return is_float($value) || is_int($value) ? number_format($value, 3, ',', '.') : '—';
    }
}
