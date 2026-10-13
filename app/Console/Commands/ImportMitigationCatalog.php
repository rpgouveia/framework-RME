<?php

namespace App\Console\Commands;

use App\Support\InvalidMitigationCatalog;
use App\Support\MitigationCatalog;
use App\Support\MitigationCatalogSource;
use Illuminate\Console\Command;

/**
 * Generate the catalogue file from the sheet the group fills (0024, item 7):
 * the "Catálogo" sheet exported as CSV and the metadata file, both in
 * database/data/catalog-source/. Only finished rows go in; the rest are
 * listed. Nothing is written while any row has a problem, and every problem
 * is reported at once, by line of the CSV.
 */
class ImportMitigationCatalog extends Command
{
    protected $signature = 'mitigations:import-catalog
        {--csv= : The exported "Catálogo" sheet (default: database/data/catalog-source/catalog.csv)}
        {--meta= : The metadata file (default: database/data/catalog-source/meta.json)}
        {--output= : The catalogue file to write (default: database/data/mitigation-catalog.json)}';

    protected $description = 'Generate the mitigation catalogue from the spreadsheet export';

    /**
     * Execute the console command.
     */
    public function handle(MitigationCatalogSource $source): int
    {
        $output = $this->option('output') ?: MitigationCatalog::path();

        try {
            $result = $source->convert(
                $this->option('csv') ?: MitigationCatalogSource::csvPath(),
                $this->option('meta') ?: MitigationCatalogSource::metaPath(),
            );
        } catch (InvalidMitigationCatalog $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['left_out'] !== []) {
            $this->warn('Linhas fora do catálogo, por não estarem com situação "'.MitigationCatalogSource::DONE.'":');

            foreach ($result['left_out'] as $line) {
                $this->line("- {$line}");
            }
        }

        file_put_contents($output, MitigationCatalogSource::encode($result['catalogue']));

        $this->info(sprintf('%d mitigações gravadas em %s.', count($result['catalogue']['entries']), $output));

        return self::SUCCESS;
    }
}
