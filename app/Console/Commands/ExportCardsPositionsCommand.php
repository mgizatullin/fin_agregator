<?php

namespace App\Console\Commands;

use App\Models\Card;
use Illuminate\Console\Command;

class ExportCardsPositionsCommand extends Command
{
    protected $signature = 'app:export-cards-positions
        {--out=storage/app/exports/cards-positions.json : Output file (absolute or relative to project)}
        {--only-active=1 : 1 = only active cards, 0 = all}
        {--pretty=1 : 1 = pretty JSON, 0 = compact}';

    protected $description = 'Export cards section positions as a JSON array (url, title, bank, limit, grace, cashback, fees, atm withdrawal).';

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

        $query = Card::query()
            ->with('bank:id,name')
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $query->chunkById(500, function ($cards) use (&$first, $handle, $flags, $pretty): void {
            foreach ($cards as $card) {
                $row = [
                    'url' => url_section('karty/'.$card->slug),
                    'title' => $card->name,
                    'bank' => $card->bank?->name,
                    'credit_limit' => $this->normalizeNumber($card->credit_limit),
                    'grace_period' => $card->grace_period,
                    'cashback' => $this->stringOrNumber($card->cashback_text, $card->cashback),
                    'annual_fee' => $this->stringOrNumber($card->annual_fee_text, $card->annual_fee),
                    'atm_withdrawal' => $this->stringOrNumber($card->atm_withdrawal_text, $card->atm_withdrawal),
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
            $out = 'storage/app/exports/cards-positions.json';
        }
        if (str_starts_with($out, '/')) {
            return $out;
        }

        return base_path($out);
    }

    private function normalizeNumber(mixed $value): int|float|null
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            $f = (float) $value;
            $i = (int) $f;

            return abs($f - $i) < 0.000001 ? $i : $f;
        }

        return null;
    }

    private function stringOrNumber(mixed $preferredString, mixed $fallbackNumber): string|int|float|null
    {
        if (is_string($preferredString)) {
            $s = trim($preferredString);
            if ($s !== '') {
                return $s;
            }
        }

        return $this->normalizeNumber($fallbackNumber);
    }

    private function indentJson(string $json): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $json) ?: [$json];

        return implode("\n", array_map(fn ($l) => '  '.$l, $lines));
    }
}

