<?php

namespace App\Console\Commands;

use App\Models\Deposit;
use Illuminate\Console\Command;

class ExportDepositsPositionsCommand extends Command
{
    protected $signature = 'app:export-deposits-positions
        {--out=storage/app/exports/deposits-positions.json : Output file (absolute or relative to project)}
        {--only-active=1 : 1 = only active deposits, 0 = all}
        {--pretty=1 : 1 = pretty JSON, 0 = compact}';

    protected $description = 'Export deposits section positions as a JSON array (url, title, bank, max_amount, min/max term, max rate, flags).';

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

        $query = Deposit::query()
            ->with([
                'bank:id,name',
                'currencies:id,deposit_id,min_amount,max_amount,sort_order',
                'currencies.conditions:id,deposit_currency_id,term_days_min,term_days_max,amount_min,amount_max,rate,is_active,sort_order',
            ])
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $query->chunkById(200, function ($deposits) use (&$first, $handle, $flags, $pretty): void {
            foreach ($deposits as $deposit) {
                $maxAmount = null;
                $minTermDays = null;
                $maxTermDays = null;
                $maxRate = null;

                foreach ($deposit->currencies as $currency) {
                    $maxAmount = $this->maxNumber($maxAmount, $currency->max_amount);

                    foreach ($currency->conditions as $condition) {
                        if ($condition->is_active === false) {
                            continue;
                        }

                        $maxAmount = $this->maxNumber($maxAmount, $condition->amount_max);
                        $minTermDays = $this->minNumber($minTermDays, $condition->term_days_min);
                        $maxTermDays = $this->maxNumber($maxTermDays, $condition->term_days_max);
                        $maxRate = $this->maxNumber($maxRate, $condition->rate);
                    }
                }

                $row = [
                    'url' => url_section('vklady/'.$deposit->slug),
                    'title' => $deposit->name,
                    'bank' => $deposit->bank?->name,
                    'max_amount' => $this->normalizeNumber($maxAmount),
                    'min_term_days' => $minTermDays,
                    'max_term_days' => $maxTermDays,
                    'max_rate' => $this->normalizeNumber($maxRate),
                    'online_opening' => (bool) $deposit->online_opening,
                    'monthly_interest_payment' => (bool) $deposit->monthly_interest_payment,
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
            $out = 'storage/app/exports/deposits-positions.json';
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

    private function maxNumber(mixed $a, mixed $b): mixed
    {
        $na = $this->normalizeNumber($a);
        $nb = $this->normalizeNumber($b);
        if ($na === null) {
            return $nb;
        }
        if ($nb === null) {
            return $na;
        }

        return $na >= $nb ? $na : $nb;
    }

    private function minNumber(mixed $a, mixed $b): mixed
    {
        $na = $this->normalizeNumber($a);
        $nb = $this->normalizeNumber($b);
        if ($na === null) {
            return $nb;
        }
        if ($nb === null) {
            return $na;
        }

        return $na <= $nb ? $na : $nb;
    }

    private function indentJson(string $json): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $json) ?: [$json];

        return implode("\n", array_map(fn ($l) => '  '.$l, $lines));
    }
}

