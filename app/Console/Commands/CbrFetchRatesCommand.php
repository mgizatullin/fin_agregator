<?php

namespace App\Console\Commands;

use App\Services\CbrRatesService;
use Illuminate\Console\Command;

class CbrFetchRatesCommand extends Command
{
    protected $signature = 'cbr:fetch-rates {--date= : Дата в формате Y-m-d (по умолчанию — сегодня)} {--force : Перезаписать курсы (привести к цене за 1 единицу, в т.ч. JPY)}';

    protected $description = 'Загрузить курсы валют с сайта ЦБ РФ и сохранить в БД. Все курсы — за 1 единицу валюты (йена как остальные).';

    public function handle(CbrRatesService $cbr): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $force = $this->option('force');
        $this->info("Загрузка курсов ЦБ за {$date}" . ($force ? ' (перезапись)' : '') . '...');

        if (! $cbr->fetchAndStoreRates($date, (bool) $force)) {
            $this->error('Не удалось загрузить или сохранить курсы.');
            $this->line('Проверьте: доступ в интернет, доступ к https://www.cbr-xml-daily.ru/daily_json.js (в браузере откройте ссылку).');
            $this->line('Подробности в storage/logs/laravel.log (ищите CbrRatesService).');

            return self::FAILURE;
        }

        $this->info('Курсы успешно загружены и сохранены в БД.');

        return self::SUCCESS;
    }
}
