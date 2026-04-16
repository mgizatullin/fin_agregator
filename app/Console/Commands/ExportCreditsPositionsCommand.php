<?php

namespace App\Console\Commands;

use App\Models\Credit;
use Illuminate\Console\Command;

class ExportCreditsPositionsCommand extends Command
{
    protected $signature = 'app:export-credits-positions
        {--out= : Output file path (default: stdout)}
        {--only-active=1 : 1 = only active credits, 0 = all}
        {--pretty=0 : 1 = pretty JSON, 0 = compact}';

    protected $description = 'Export credits section positions as a JSON array (url, title, bank, amount, term, rate, decision).';

    public function handle(): int
    {
        $out = $this->option('out');
        $pretty = (int) $this->option('pretty') === 1;
        $onlyActive = (int) $this->option('only-active') !== 0;

        $handle = null;
        if (filled($out)) {
            $outPath = $this->resolveOutPath((string) $out);
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
        } else {
            $handle = fopen('php://stdout', 'w');
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0);

        fwrite($handle, '[');
        if ($pretty) {
            fwrite($handle, "\n");
        }

        $first = true;

        $query = Credit::query()
            ->with('bank')
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $query->chunkById(500, function ($credits) use (&$first, $handle, $flags, $pretty): void {
            foreach ($credits as $credit) {
                $row = [
                    'url' => url_section('kredity/'.$credit->slug),
                    'title' => $credit->name,
                    'bank' => $credit->bank?->name,
                    'amount' => $this->pickNumber($credit->max_amount, $credit->min_amount),
                    'term' => $this->pickNumber($credit->term_months, $credit->max_term_months, $credit->min_term_months),
                    'rate' => $this->pickNumber($credit->rate, $credit->rate_min, $credit->rate_max),
                    'decision' => $credit->decision,
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

        if (is_resource($handle) && filled($out)) {
            fclose($handle);
        }

        return self::SUCCESS;
    }

    private function resolveOutPath(string $out): string
    {
        $out = trim($out);
        if ($out === '') {
            return base_path('storage/app/exports/credits-positions.json');
        }
        if (str_starts_with($out, '/')) {
            return $out;
        }

        return base_path($out);
    }

    private function pickNumber(mixed ...$values): int|float|null
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if (is_int($value) || is_float($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * json_encode(JSON_PRETTY_PRINT) already adds indentation/newlines. We add
     * one extra leading indentation for array elements.
     */
    private function indentJson(string $json): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $json) ?: [$json];

        return implode("\n", array_map(fn ($l) => '  '.$l, $lines));
    }
}

