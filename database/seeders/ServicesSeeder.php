<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        Service::updateOrCreate(
            ['type' => Service::TYPE_CREDIT_CALCULATOR],
            [
                'slug' => 'kreditnyy-kalkulyator',
                'title' => 'Калькулятор кредитов',
                'seo_title' => 'Калькулятор кредитов',
                'seo_description' => null,
                'is_active' => true,
            ],
        );

        Service::updateOrCreate(
            ['type' => Service::TYPE_DEPOSIT_CALCULATOR],
            [
                'slug' => 'kalkulyator-vkladov',
                'title' => 'Калькулятор вкладов',
                'seo_title' => 'Калькулятор вкладов',
                'seo_description' => null,
                'is_active' => true,
            ],
        );

        Service::updateOrCreate(
            ['type' => Service::TYPE_CURRENCY_RATES],
            [
                'slug' => 'kurs-valut-cbr',
                'title' => 'Курсы валют ЦБ РФ',
                'seo_title' => 'Курс валют ЦБ РФ на сегодня — калькулятор',
                'seo_description' => 'Официальные курсы валют Центробанка России. Пересчёт любой суммы в рубли по курсу ЦБ.',
                'is_active' => true,
            ],
        );
    }
}
