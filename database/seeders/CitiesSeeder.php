<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $cities = [
            [
                'name' => 'Москва',
                'slug' => 'moskva',
                'region' => 'Москва',
                'population' => 13149803,
                'is_active' => true,
            ],
            [
                'name' => 'Санкт-Петербург',
                'slug' => 'spb',
                'region' => 'Санкт-Петербург',
                'population' => 5608800,
                'is_active' => true,
            ],
            [
                'name' => 'Новосибирск',
                'slug' => 'novosibirsk',
                'region' => 'Новосибирская область',
                'population' => 1633595,
                'is_active' => true,
            ],
            [
                'name' => 'Екатеринбург',
                'slug' => 'ekaterinburg',
                'region' => 'Свердловская область',
                'population' => 1539371,
                'is_active' => true,
            ],
            [
                'name' => 'Казань',
                'slug' => 'kazan',
                'region' => 'Республика Татарстан',
                'population' => 1314685,
                'is_active' => true,
            ],
            [
                'name' => 'Нижний Новгород',
                'slug' => 'nizhniy-novgorod',
                'region' => 'Нижегородская область',
                'population' => 1222699,
                'is_active' => true,
            ],
            [
                'name' => 'Челябинск',
                'slug' => 'chelyabinsk',
                'region' => 'Челябинская область',
                'population' => 1177130,
                'is_active' => true,
            ],
            [
                'name' => 'Самара',
                'slug' => 'samara',
                'region' => 'Самарская область',
                'population' => 1163399,
                'is_active' => true,
            ],
            [
                'name' => 'Омск',
                'slug' => 'omsk',
                'region' => 'Омская область',
                'population' => 1110836,
                'is_active' => true,
            ],
            [
                'name' => 'Ростов-на-Дону',
                'slug' => 'rostov-na-donu',
                'region' => 'Ростовская область',
                'population' => 1142162,
                'is_active' => true,
            ],
            [
                'name' => 'Уфа',
                'slug' => 'ufa',
                'region' => 'Республика Башкортостан',
                'population' => 1157600,
                'is_active' => true,
            ],
            [
                'name' => 'Красноярск',
                'slug' => 'krasnoyarsk',
                'region' => 'Красноярский край',
                'population' => 1205473,
                'is_active' => true,
            ],
            [
                'name' => 'Пермь',
                'slug' => 'perm',
                'region' => 'Пермский край',
                'population' => 1026477,
                'is_active' => true,
            ],
            [
                'name' => 'Воронеж',
                'slug' => 'voronezh',
                'region' => 'Воронежская область',
                'population' => 1057681,
                'is_active' => true,
            ],
            [
                'name' => 'Волгоград',
                'slug' => 'volgograd',
                'region' => 'Волгоградская область',
                'population' => 1018898,
                'is_active' => true,
            ],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['slug' => $city['slug']],
                $city,
            );
        }
    }
}
