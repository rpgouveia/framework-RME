<?php

namespace App\Console\Commands;

use App\Support\ExpertPanel;
use App\Support\InvalidExpertPanel;
use App\Support\InvalidMonitoringProtocol;
use Illuminate\Console\Command;

/**
 * Compute the expert panel's result (0027) from the second round's CSV
 * export: the CVR of every item, the critical value for the panel's size
 * (Ayre and Scally, 2014), the items retained, the OE3 and OE6 criteria, the
 * CVI, and the review intervals the experts suggest. The result is shown as
 * tables and written to a file that only changes when the responses do.
 * Every answer is mandatory, so the run is always strict.
 */
class MeasureExpertPanelCvr extends Command
{
    protected $signature = 'expert-panel:cvr
        {--csv= : The second round\'s responses (default: database/data/expert-panel/round-2-responses.csv)}
        {--items= : The item bank (default: database/data/expert-panel/items.csv)}
        {--output= : The report file to write (default: database/data/expert-panel/oe6-cvr.json)}';

    protected $description = 'Compute the content validity ratio of the expert panel (OE3 and OE6)';

    /**
     * Execute the console command.
     */
    public function handle(ExpertPanel $panel): int
    {
        $output = $this->option('output') ?: ExpertPanel::path();

        try {
            $report = $panel->measure(
                $this->option('csv') ?: ExpertPanel::responsesPath(),
                $this->option('items') ?: ExpertPanel::itemsPath(),
            );
        } catch (InvalidExpertPanel|InvalidMonitoringProtocol $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $critical = $report['critical'];

        $this->table(['Medida', 'Valor'], [
            ['Respondentes (N)', $report['n']],
            ['Valor crítico (nₑ)', "{$critical['essential']} de {$report['n']}"],
            ['CVR crítico', self::number($critical['cvr'])],
            ['Método', "{$critical['method']}, alfa ".self::number($critical['alpha'], 2)],
            ['CVI geral (todos os itens)', self::number($report['cvi']['all'])],
            ['CVI geral (itens mantidos)', self::number($report['cvi']['retained'])],
            ['OE3 (itens da definição mantidos)', self::criterion($report['oe3'])],
            ['OE6 (todos os itens mantidos)', self::criterion($report['oe6'])],
        ]);

        $this->table(
            ['Item', 'Objetivo', 'Essencial', 'Útil', 'Desnecessário', 'CVR', 'Mantido'],
            array_map(fn (array $item): array => [
                $item['id'],
                implode(', ', $item['objectives']),
                $item['essential'],
                $item['useful'],
                $item['unnecessary'],
                self::number($item['cvr']),
                $item['retained'] ? 'sim' : 'não',
            ], $report['items']),
        );

        $this->table(
            ['Bloco', 'Itens', 'Mantidos', 'CVI (todos)', 'CVI (mantidos)'],
            array_map(fn (array $block): array => [
                $block['block'],
                $block['items'],
                $block['retained'],
                self::number($block['cvi_all']),
                self::number($block['cvi_retained']),
            ], $report['blocks']),
        );

        $this->table(
            ['Faixa', 'Protocolo (dias)', 'Mediana', 'Q1', 'Q3', 'Sustentado'],
            array_map(fn (array $interval): array => [
                $interval['tier'],
                $interval['protocol_days'],
                self::number($interval['median'], 2),
                self::number($interval['q1'], 2),
                self::number($interval['q3'], 2),
                $interval['sustained'] ? 'sim' : 'não',
            ], $report['intervals']),
        );

        file_put_contents($output, ExpertPanel::encode($report));

        $this->info("Resultado gravado em {$output}.");

        return self::SUCCESS;
    }

    /**
     * Whether a criterion is met, with the count behind it.
     *
     * @param  array{items: int, retained: int, met: bool}  $criterion
     */
    protected static function criterion(array $criterion): string
    {
        return ($criterion['met'] ? 'atendido' : 'não atendido')." ({$criterion['retained']} de {$criterion['items']} mantidos)";
    }

    /** Three decimals (or the given number) with a decimal comma, or a dash when undefined. */
    protected static function number(mixed $value, int $decimals = 3): string
    {
        return is_float($value) || is_int($value) ? number_format($value, $decimals, ',', '.') : '—';
    }
}
