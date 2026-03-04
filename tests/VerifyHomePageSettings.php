<?php

/**
 * Проверка автомиграции и страницы настроек главной.
 * Запуск: php tests/VerifyHomePageSettings.php (из корня проекта в WSL)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

echo "1. Проверка наличия таблицы home_page_settings... ";
if (Schema::hasTable('home_page_settings')) {
    echo "есть.\n";
} else {
    echo "нет. Запуск миграций... ";
    Artisan::call('migrate', ['--force' => true]);
    echo "выполнено.\n";
}

echo "2. Повторная проверка таблицы... ";
if (! Schema::hasTable('home_page_settings')) {
    echo "ОШИБКА: таблица не создана.\n";
    exit(1);
}
echo "есть.\n";

echo "3. Загрузка модели HomePageSetting::instance()... ";
try {
    $setting = \App\Models\HomePageSetting::instance();
    echo "OK (id={$setting->id}).\n";
} catch (\Throwable $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

echo "4. Создание страницы Filament и вызов mount()... ";
try {
    $page = app(\App\Filament\Pages\HomePageSettings::class);
    $page->mount();
    echo "OK.\n";
} catch (\Throwable $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nВсе проверки пройдены.\n";

echo "5. Проверка автомиграции при отсутствии таблицы... ";
Schema::dropIfExists('home_page_advantages');
Schema::dropIfExists('home_page_settings');
$page2 = app(\App\Filament\Pages\HomePageSettings::class);
$page2->mount();
if (! Schema::hasTable('home_page_settings')) {
    echo "ОШИБКА: автомиграция не создала таблицу.\n";
    exit(1);
}
echo "OK.\n";
echo "Автомиграция работает.\n";
