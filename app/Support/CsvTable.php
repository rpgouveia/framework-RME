<?php

namespace App\Support;

/**
 * A CSV file as a spreadsheet or an online form exports it: comma or
 * semicolon separated, with or without a byte order mark, cells that may span
 * several lines. Read by the catalogue import (0024), the OE1 agreement
 * measure (0025) and the expert panel's CVR (0027).
 */
class CsvTable
{
    /**
     * The header and the rows of the CSV, each row keyed by column and paired
     * with the line it starts on. Every value is trimmed.
     *
     * @param  string  $name  How the messages name the file, such as "do catálogo".
     * @return array{list<string>, list<array{int, array<string, string>}>}
     *
     * @throws InvalidCsvFile When the file is missing or unreadable.
     */
    public function rows(string $path, string $name): array
    {
        if (! is_file($path)) {
            throw new InvalidCsvFile("CSV {$name} não encontrado: {$path}");
        }

        $content = (string) file_get_contents($path);
        $content = str_starts_with($content, "\u{FEFF}") ? substr($content, 3) : $content;
        $firstLine = strtok($content, "\n");
        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new InvalidCsvFile("Não foi possível ler o CSV {$name}: {$path}");
        }

        fwrite($handle, $content);
        rewind($handle);

        $header = array_map(fn (?string $column): string => trim((string) $column), fgetcsv($handle, null, $delimiter, '"', '') ?: []);
        $rows = [];

        while (true) {
            // A quoted cell may span lines, so the line is counted from the
            // position where the row starts.
            $line = substr_count($content, "\n", 0, (int) ftell($handle)) + 1;
            $values = fgetcsv($handle, null, $delimiter, '"', '');

            if ($values === false) {
                break;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = trim((string) ($values[$index] ?? ''));
            }

            $rows[] = [$line, $row];
        }

        fclose($handle);

        return [$header, $rows];
    }
}
