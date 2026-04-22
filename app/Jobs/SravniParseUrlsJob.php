<?php

namespace App\Jobs;

use App\Models\ParserRun;
use App\Services\Parsers\Sravni\SravniDepositParser;
use App\Services\Parsers\Sravni\SravniDepositImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SravniParseUrlsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The job processes many URLs with delays, so increase timeout significantly.
     */
    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(public int $parserRunId)
    {
    }

    public function handle(SravniDepositParser $parser): void
    {
        $run = ParserRun::query()->find($this->parserRunId);
        if (! $run instanceof ParserRun) {
            return;
        }

        $params = is_array($run->params) ? $run->params : [];
        $urls = is_array($params['urls'] ?? null) ? $params['urls'] : [];
        $mode = is_string($params['parse_mode'] ?? null) ? (string) $params['parse_mode'] : 'upsert';
        $delay = isset($params['delay_seconds']) && is_numeric((string) $params['delay_seconds'])
            ? max(0, min(10, (int) $params['delay_seconds']))
            : 1;

        $stats = is_array($run->stats) ? $run->stats : [];
        $stats = array_merge([
            'found' => count($urls),
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'banks_created' => 0,
            'missing_bank_or_name' => 0,
        ], $stats);

        $log = [];
        $log[] = 'Старт фонового парсинга URLов: ' . count($urls);
        $log[] = 'Режим: ' . $mode . '. Пауза: ' . $delay . ' сек.';

        $run->mode = 'parse_urls_bulk_running';
        $run->stats = $stats;
        $run->log_output = implode("\n", $log);
        $run->save();

        $importer = new SravniDepositImporter();

        foreach ($urls as $i => $url) {
            $run->refresh();
            $params = is_array($run->params) ? $run->params : [];
            if (! empty($params['cancel_requested'])) {
                $run->mode = 'parse_urls_bulk_cancelled';
                $log[] = 'Остановлено пользователем.';
                $run->log_output = implode("\n", $log);
                $run->save();
                return;
            }

            $stats['processed'] = (int) ($stats['processed'] ?? 0) + 1;

            try {
                $parsed = $parser->parse((string) $url);
            } catch (\Throwable $e) {
                $stats['errors'] = (int) ($stats['errors'] ?? 0) + 1;
                $log[] = '[' . ($i + 1) . '/' . count($urls) . '] ✖ ' . $url . ' — ' . $e->getMessage();
                $run->stats = $stats;
                $run->log_output = implode("\n", array_slice($log, -300));
                $run->save();
                if ($delay > 0) {
                    sleep($delay);
                }
                continue;
            }

            if ($parsed === []) {
                $stats['errors'] = (int) ($stats['errors'] ?? 0) + 1;
                $parserLog = $parser->getLog();
                $tail = is_array($parserLog) ? array_values(array_slice($parserLog, -3)) : [];
                $reason = $tail !== [] ? implode(' | ', array_map(fn ($v) => trim((string) $v), $tail)) : 'не удалось извлечь данные';
                $diag = $this->diagnoseUrl((string) $url);
                $log[] = '[' . ($i + 1) . '/' . count($urls) . '] ✖ ' . $url . ' — ' . $reason
                    . ($diag !== '' ? (' | diag: ' . $diag) : '');
                $run->stats = $stats;
                $run->log_output = implode("\n", array_slice($log, -300));
                $run->save();
                if ($delay > 0) {
                    sleep($delay);
                }
                continue;
            }

            $row = $parsed[0] ?? null;
            if (! is_array($row)) {
                $stats['errors'] = (int) ($stats['errors'] ?? 0) + 1;
                $log[] = '[' . ($i + 1) . '/' . count($urls) . '] ✖ ' . $url . ' — некорректный формат результата';
            } else {
                $bankName = trim((string) ($row['bank'] ?? ''));
                $depositName = trim((string) ($row['deposit_name'] ?? ''));
                if ($bankName === '' || $depositName === '') {
                    $stats['missing_bank_or_name'] = (int) ($stats['missing_bank_or_name'] ?? 0) + 1;
                    $stats['skipped'] = (int) ($stats['skipped'] ?? 0) + 1;
                    $log[] = '[' . ($i + 1) . '/' . count($urls) . '] ⚠ ' . $url . ' — пропущено: пустой bank/deposit_name';
                } else {
                    $importRes = $importer->importOne($row, $mode === 'upsert' ? 'update_existing' : $mode);
                    $status = (string) ($importRes['status'] ?? 'skipped');
                    if ($status === 'created') {
                        $stats['created'] = (int) ($stats['created'] ?? 0) + 1;
                        $stats['success'] = (int) ($stats['success'] ?? 0) + 1;
                    } elseif ($status === 'updated') {
                        $stats['updated'] = (int) ($stats['updated'] ?? 0) + 1;
                        $stats['success'] = (int) ($stats['success'] ?? 0) + 1;
                    } else {
                        $stats['skipped'] = (int) ($stats['skipped'] ?? 0) + 1;
                    }
                    if (! empty($importRes['bank_created'])) {
                        $stats['banks_created'] = (int) ($stats['banks_created'] ?? 0) + 1;
                    }
                    $log[] = '[' . ($i + 1) . '/' . count($urls) . '] ✔ ' . $url . ' — ' . ($importRes['message'] ?? 'OK');
                }
            }

            $run->stats = $stats;
            $run->log_output = implode("\n", array_slice($log, -300));
            $run->save();

            if ($delay > 0) {
                sleep($delay);
            }
        }

        $run->mode = 'parse_urls_bulk_done';
        $run->stats = $stats;
        $run->log_output = implode("\n", array_slice($log, -300));
        $run->save();
    }

    private function diagnoseUrl(string $url): string
    {
        try {
            $r = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9',
                'Referer' => 'https://www.sravni.ru/',
            ])->timeout(25)->get($url);
        } catch (\Throwable $e) {
            return 'http_ex: ' . $e->getMessage();
        }

        $body = (string) $r->body();
        $title = '';
        if (preg_match('~<title[^>]*>(.*?)</title>~isu', $body, $m) === 1) {
            $title = trim(strip_tags((string) ($m[1] ?? '')));
        }

        $lower = mb_strtolower($body);
        $robot = str_contains($lower, 'вы не робот') || str_contains($lower, 'captcha');
        $hasNext = str_contains($body, '__NEXT_DATA__');

        $snippet = preg_replace('/\s+/u', ' ', mb_substr($body, 0, 220));
        $snippet = trim((string) $snippet);

        $parts = [
            'status=' . $r->status(),
            'title=' . ($title !== '' ? mb_substr($title, 0, 80) : '—'),
            'robot=' . ($robot ? 'yes' : 'no'),
            'next=' . ($hasNext ? 'yes' : 'no'),
        ];

        if ($snippet !== '') {
            $parts[] = 'snip=' . mb_substr($snippet, 0, 200);
        }

        return implode(', ', $parts);
    }
}

