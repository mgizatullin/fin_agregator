<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class ExportLoansPositionsCommand extends Command
{
    protected $signature = 'app:export-loans-positions
        {--out=storage/app/exports/loans-positions.json : Output file (absolute or relative to project)}
        {--only-active=1 : 1 = only active loans, 0 = all}
        {--pretty=1 : 1 = pretty JSON, 0 = compact}';

    protected $description = 'Export loans section positions as a JSON array (url, title, company, amounts, terms, no-interest term, rate).';

    public function handle(): int
    {
        $outPath = $this->resolveOutPath((string) $this->option('out'));
        $pretty = (int) $this->option('pretty') === 1;
        $onlyActive = (int) $this->option('only-active') !== 0;

        $dir = dirname($outPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error("Failed to create output directory: {$dir}");

            return self::FAILURE;
        }

        $handle = fopen($outPath, 'w');
        if (! $handle) {
            $this->error("Failed to open output file: {$outPath}");

            return self::FAILURE;
        }

        $this->info("Writing to: {$outPath}");

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0);

        fwrite($handle, '[');
        if ($pretty) {
            fwrite($handle, "\n");
        }

        $first = true;

        $query = Loan::query()
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $query->chunkById(500, function ($loans) use (&$first, $handle, $flags, $pretty): void {
            foreach ($loans as $loan) {
                $row = [
                    'url' => url_section('zaimy/'.$loan->slug),
                    'title' => $loan->name,
                    'mfo' => $loan->company_name,
                    'min_amount' => $this->normalizeNumber($loan->min_amount),
                    'max_amount' => $this->normalizeNumber($loan->max_amount),
                    'min_term_days' => $this->pickInt($loan->term_days_min),
                    'max_term_days' => $this->pickInt($loan->term_days),
                    'no_interest_term_days' => $this->pickInt($loan->term_no_interest_min, $loan->term_no_interest),
                    'rate' => $this->normalizeNumber($loan->rate_min, $loan->rate),
                ];

                $json = json_encode($row, $flags);
                if ($json === false) {
                    continue;
                }

                if (! $first) {
                    fwrite($handle, ',');
                    if ($pretty) {
                        fwrite($handle, "\n");
                    }
                }
                if ($pretty) {
                    fwrite($handle, '  ');
                }
                fwrite($handle, $pretty ? $this->indentJson($json) : $json);
                $first = false;
            }
        });

        if ($pretty && ! $first) {
            fwrite($handle, "\n");
        }
        fwrite($handle, ']');
        fclose($handle);

        return self::SUCCESS;
    }

    private function resolveOutPath(string $out): string
    {
        $out = trim($out);
        if ($out === '') {
            $out = 'storage/app/exports/loans-positions.json';
        }
        if (str_starts_with($out, '/')) {
            return $out;
        }

        return base_path($out);
    }

    private function normalizeNumber(mixed ...$values): int|float|null
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if (is_int($value) || is_float($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                $f = (float) $value;
                $i = (int) $f;

                return abs($f - $i) < 0.000001 ? $i : $f;
            }
        }

        return null;
    }

    private function pickInt(mixed ...$values): ?int
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if (is_int($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function indentJson(string $json): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $json) ?: [$json];

        return implode("\n", array_map(fn ($l) => '  '.$l, $lines));
    }
}

