<?php

namespace App\Console\Commands;

use App\Models\Bank;
use Illuminate\Console\Command;

class ExportBanksPositionsCommand extends Command
{
    protected $signature = 'app:export-banks-positions
        {--out=storage/app/exports/banks-positions.json : Output file (absolute or relative to project)}
        {--only-active=1 : 1 = only active banks, 0 = all}
        {--pretty=1 : 1 = pretty JSON, 0 = compact}';

    protected $description = 'Export banks section positions as a JSON array (url, title).';

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

        $query = Bank::query()
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $query->chunkById(1000, function ($banks) use (&$first, $handle, $flags, $pretty): void {
            foreach ($banks as $bank) {
                $row = [
                    'url' => url_section('banki/'.$bank->slug),
                    'title' => $bank->name,
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
            $out = 'storage/app/exports/banks-positions.json';
        }
        if (str_starts_with($out, '/')) {
            return $out;
        }

        return base_path($out);
    }

    private function indentJson(string $json): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $json) ?: [$json];

        return implode("\n", array_map(fn ($l) => '  '.$l, $lines));
    }
}

