<?php

namespace App\Console\Commands;

use App\Filament\Pages\HomePageSettings;
use App\Models\HomePageSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class VerifyHomePageSettingsCommand extends Command
{
    protected $signature = 'home-page:verify';

    protected $description = 'Проверка автомиграции и страницы настроек главной';

    public function handle(): int
    {
        $this->info('1. Проверка наличия таблицы home_page_settings...');
        if (Schema::hasTable('home_page_settings')) {
            $this->info('   Таблица есть.');
        } else {
            $this->warn('   Таблицы нет. Запуск миграций...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('   Миграции выполнены.');
        }

        $this->info('2. Повторная проверка таблицы...');
        if (! Schema::hasTable('home_page_settings')) {
            $this->error('   ОШИБКА: таблица не создана.');
            return 1;
        }
        $this->info('   Таблица есть.');

        $this->info('3. Загрузка модели HomePageSetting::instance()...');
        try {
            $setting = HomePageSetting::instance();
            $this->info("   OK (id={$setting->id}).");
        } catch (\Throwable $e) {
            $this->error('   ОШИБКА: ' . $e->getMessage());
            return 1;
        }

        $this->info('4. Создание страницы Filament и вызов mount()...');
        try {
            $page = app(HomePageSettings::class);
            $page->mount();
            $this->info('   OK.');
        } catch (\Throwable $e) {
            $this->error('   ОШИБКА: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('Все проверки пройдены. При первом открытии страницы настроек миграции выполнятся автоматически.');

        return 0;
    }
}
