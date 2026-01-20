<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RussiaCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Находим или создаем Россию
        $russia = Country::firstOrCreate(
            ['code' => 'RU'],
            [
                'name' => 'Российская Федерация',
                'phone_code' => '+7',
                'currency_code' => 'RUB',
                'currency_symbol' => '₽',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        
        $this->command->info("Работаем со страной: {$russia->name} (ID: {$russia->id})");

        // Получаем регионы
        $regions = Region::where('country_id', $russia->id)->get()->keyBy('code');

        $cities = [
            // Москва и Московская область
            ['name' => 'Москва', 'name_eng' => 'Moscow', 'region_code' => '77', 'population' => 12678079, 'is_capital' => true, 'timezone' => 'Europe/Moscow', 'latitude' => 55.7558, 'longitude' => 37.6173],
            ['name' => 'Подольск', 'name_eng' => 'Podolsk', 'region_code' => '50', 'population' => 299660, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Химки', 'name_eng' => 'Khimki', 'region_code' => '50', 'population' => 257128, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Балашиха', 'name_eng' => 'Balashikha', 'region_code' => '50', 'population' => 520962, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Королёв', 'name_eng' => 'Korolyov', 'region_code' => '50', 'population' => 221797, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Мытищи', 'name_eng' => 'Mytishchi', 'region_code' => '50', 'population' => 255429, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Люберцы', 'name_eng' => 'Lyubertsy', 'region_code' => '50', 'population' => 207349, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Красногорск', 'name_eng' => 'Krasnogorsk', 'region_code' => '50', 'population' => 175812, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Электросталь', 'name_eng' => 'Elektrostal', 'region_code' => '50', 'population' => 155196, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Одинцово', 'name_eng' => 'Odintsovo', 'region_code' => '50', 'population' => 140537, 'timezone' => 'Europe/Moscow'],

            // Санкт-Петербург и Ленинградская область
            ['name' => 'Санкт-Петербург', 'name_eng' => 'Saint Petersburg', 'region_code' => '78', 'population' => 5398064, 'is_capital' => true, 'timezone' => 'Europe/Moscow', 'latitude' => 59.9343, 'longitude' => 30.3351],
            ['name' => 'Гатчина', 'name_eng' => 'Gatchina', 'region_code' => '47', 'population' => 94377, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Выборг', 'name_eng' => 'Vyborg', 'region_code' => '47', 'population' => 77222, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Сосновый Бор', 'name_eng' => 'Sosnovy Bor', 'region_code' => '47', 'population' => 65316, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Тихвин', 'name_eng' => 'Tikhvin', 'region_code' => '47', 'population' => 58124, 'timezone' => 'Europe/Moscow'],

            // Крупные города других регионов
            ['name' => 'Новосибирск', 'name_eng' => 'Novosibirsk', 'region_code' => '54', 'population' => 1625631, 'timezone' => 'Asia/Novosibirsk', 'latitude' => 55.0084, 'longitude' => 82.9357],
            ['name' => 'Екатеринбург', 'name_eng' => 'Yekaterinburg', 'region_code' => '66', 'population' => 1544376, 'timezone' => 'Asia/Yekaterinburg', 'latitude' => 56.8431, 'longitude' => 60.6454],
            ['name' => 'Казань', 'name_eng' => 'Kazan', 'region_code' => '16', 'population' => 1308660, 'timezone' => 'Europe/Moscow', 'latitude' => 55.8304, 'longitude' => 49.0661],
            ['name' => 'Нижний Новгород', 'name_eng' => 'Nizhny Novgorod', 'region_code' => '52', 'population' => 1244256, 'timezone' => 'Europe/Moscow', 'latitude' => 56.2965, 'longitude' => 43.9361],
            ['name' => 'Челябинск', 'name_eng' => 'Chelyabinsk', 'region_code' => '74', 'population' => 1202371, 'timezone' => 'Asia/Yekaterinburg', 'latitude' => 55.1644, 'longitude' => 61.4368],
            ['name' => 'Самара', 'name_eng' => 'Samara', 'region_code' => '63', 'population' => 1164685, 'timezone' => 'Europe/Samara', 'latitude' => 53.2001, 'longitude' => 50.15],
            ['name' => 'Омск', 'name_eng' => 'Omsk', 'region_code' => '55', 'population' => 1154000, 'timezone' => 'Asia/Omsk', 'latitude' => 54.9885, 'longitude' => 73.3242],
            ['name' => 'Ростов-на-Дону', 'name_eng' => 'Rostov-on-Don', 'region_code' => '61', 'population' => 1142162, 'timezone' => 'Europe/Moscow', 'latitude' => 47.2357, 'longitude' => 39.7015],
            ['name' => 'Уфа', 'name_eng' => 'Ufa', 'region_code' => '02', 'population' => 1144800, 'timezone' => 'Asia/Yekaterinburg', 'latitude' => 54.7431, 'longitude' => 55.9678],
            ['name' => 'Красноярск', 'name_eng' => 'Krasnoyarsk', 'region_code' => '24', 'population' => 1093859, 'timezone' => 'Asia/Krasnoyarsk', 'latitude' => 56.0184, 'longitude' => 92.8672],
            ['name' => 'Воронеж', 'name_eng' => 'Voronezh', 'region_code' => '36', 'population' => 1057681, 'timezone' => 'Europe/Moscow', 'latitude' => 51.6720, 'longitude' => 39.1843],
            ['name' => 'Пермь', 'name_eng' => 'Perm', 'region_code' => '59', 'population' => 1053938, 'timezone' => 'Asia/Yekaterinburg', 'latitude' => 58.0105, 'longitude' => 56.2502],
            ['name' => 'Волгоград', 'name_eng' => 'Volgograd', 'region_code' => '34', 'population' => 1015586, 'timezone' => 'Europe/Volgograd', 'latitude' => 48.7194, 'longitude' => 44.5018],

            // Города с населением 500k-1M
            ['name' => 'Краснодар', 'name_eng' => 'Krasnodar', 'region_code' => '23', 'population' => 932629, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Саратов', 'name_eng' => 'Saratov', 'region_code' => '64', 'population' => 845300, 'timezone' => 'Europe/Saratov'],
            ['name' => 'Тюмень', 'name_eng' => 'Tyumen', 'region_code' => '72', 'population' => 816799, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Тольятти', 'name_eng' => 'Tolyatti', 'region_code' => '63', 'population' => 707408, 'timezone' => 'Europe/Samara'],
            ['name' => 'Ижевск', 'name_eng' => 'Izhevsk', 'region_code' => '18', 'population' => 646277, 'timezone' => 'Europe/Samara'],
            ['name' => 'Барнаул', 'name_eng' => 'Barnaul', 'region_code' => '22', 'population' => 633301, 'timezone' => 'Asia/Barnaul'],
            ['name' => 'Ульяновск', 'name_eng' => 'Ulyanovsk', 'region_code' => '73', 'population' => 624518, 'timezone' => 'Europe/Ulyanovsk'],
            ['name' => 'Иркутск', 'name_eng' => 'Irkutsk', 'region_code' => '38', 'population' => 623736, 'timezone' => 'Asia/Irkutsk'],
            ['name' => 'Хабаровск', 'name_eng' => 'Khabarovsk', 'region_code' => '27', 'population' => 616242, 'timezone' => 'Asia/Vladivostok'],
            ['name' => 'Ярославль', 'name_eng' => 'Yaroslavl', 'region_code' => '76', 'population' => 608353, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Владивосток', 'name_eng' => 'Vladivostok', 'region_code' => '25', 'population' => 606561, 'timezone' => 'Asia/Vladivostok'],
            ['name' => 'Махачкала', 'name_eng' => 'Makhachkala', 'region_code' => '05', 'population' => 603518, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Томск', 'name_eng' => 'Tomsk', 'region_code' => '70', 'population' => 572740, 'timezone' => 'Asia/Tomsk'],
            ['name' => 'Оренбург', 'name_eng' => 'Orenburg', 'region_code' => '56', 'population' => 564443, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Кемерово', 'name_eng' => 'Kemerovo', 'region_code' => '42', 'population' => 558973, 'timezone' => 'Asia/Krasnoyarsk'],
            ['name' => 'Новокузнецк', 'name_eng' => 'Novokuznetsk', 'region_code' => '42', 'population' => 544583, 'timezone' => 'Asia/Krasnoyarsk'],
            ['name' => 'Рязань', 'name_eng' => 'Ryazan', 'region_code' => '62', 'population' => 539789, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Набережные Челны', 'name_eng' => 'Naberezhnye Chelny', 'region_code' => '16', 'population' => 533839, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Астрахань', 'name_eng' => 'Astrakhan', 'region_code' => '30', 'population' => 532504, 'timezone' => 'Europe/Astrakhan'],
            ['name' => 'Пенза', 'name_eng' => 'Penza', 'region_code' => '58', 'population' => 519900, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Липецк', 'name_eng' => 'Lipetsk', 'region_code' => '48', 'population' => 510439, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Киров', 'name_eng' => 'Kirov', 'region_code' => '43', 'population' => 507643, 'timezone' => 'Europe/Kirov'],
            ['name' => 'Чебоксары', 'name_eng' => 'Cheboksary', 'region_code' => '21', 'population' => 497807, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Калининград', 'name_eng' => 'Kaliningrad', 'region_code' => '39', 'population' => 490449, 'timezone' => 'Europe/Kaliningrad'],
            ['name' => 'Тула', 'name_eng' => 'Tula', 'region_code' => '71', 'population' => 473622, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Курск', 'name_eng' => 'Kursk', 'region_code' => '46', 'population' => 452976, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Сочи', 'name_eng' => 'Sochi', 'region_code' => '23', 'population' => 443562, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Ставрополь', 'name_eng' => 'Stavropol', 'region_code' => '26', 'population' => 454488, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Улан-Удэ', 'name_eng' => 'Ulan-Ude', 'region_code' => '03', 'population' => 437565, 'timezone' => 'Asia/Irkutsk'],
            ['name' => 'Тверь', 'name_eng' => 'Tver', 'region_code' => '69', 'population' => 424969, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Магнитогорск', 'name_eng' => 'Magnitogorsk', 'region_code' => '74', 'population' => 410594, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Иваново', 'name_eng' => 'Ivanovo', 'region_code' => '37', 'population' => 401505, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Брянск', 'name_eng' => 'Bryansk', 'region_code' => '32', 'population' => 399579, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Белгород', 'name_eng' => 'Belgorod', 'region_code' => '31', 'population' => 391554, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Сургут', 'name_eng' => 'Surgut', 'region_code' => '86', 'population' => 380632, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Владимир', 'name_eng' => 'Vladimir', 'region_code' => '33', 'population' => 356937, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Нижний Тагил', 'name_eng' => 'Nizhny Tagil', 'region_code' => '66', 'population' => 355693, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Архангельск', 'name_eng' => 'Arkhangelsk', 'region_code' => '29', 'population' => 351488, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Чита', 'name_eng' => 'Chita', 'region_code' => '75', 'population' => 347088, 'timezone' => 'Asia/Chita'],
            ['name' => 'Калуга', 'name_eng' => 'Kaluga', 'region_code' => '40', 'population' => 341892, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Смоленск', 'name_eng' => 'Smolensk', 'region_code' => '67', 'population' => 320170, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Волжский', 'name_eng' => 'Volzhsky', 'region_code' => '34', 'population' => 323906, 'timezone' => 'Europe/Volgograd'],
            ['name' => 'Саранск', 'name_eng' => 'Saransk', 'region_code' => '13', 'population' => 318841, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Череповец', 'name_eng' => 'Cherepovets', 'region_code' => '35', 'population' => 312091, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Курган', 'name_eng' => 'Kurgan', 'region_code' => '45', 'population' => 309285, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Орёл', 'name_eng' => 'Oryol', 'region_code' => '57', 'population' => 303696, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Грозный', 'name_eng' => 'Grozny', 'region_code' => '20', 'population' => 324602, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Владикавказ', 'name_eng' => 'Vladikavkaz', 'region_code' => '15', 'population' => 306978, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Мурманск', 'name_eng' => 'Murmansk', 'region_code' => '51', 'population' => 282851, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Тамбов', 'name_eng' => 'Tambov', 'region_code' => '68', 'population' => 280161, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Петрозаводск', 'name_eng' => 'Petrozavodsk', 'region_code' => '10', 'population' => 280890, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Нижневартовск', 'name_eng' => 'Nizhnevartovsk', 'region_code' => '86', 'population' => 283256, 'timezone' => 'Asia/Yekaterinburg'],
            ['name' => 'Кострома', 'name_eng' => 'Kostroma', 'region_code' => '44', 'population' => 277393, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Новороссийск', 'name_eng' => 'Novorossiysk', 'region_code' => '23', 'population' => 275197, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Йошкар-Ола', 'name_eng' => 'Yoshkar-Ola', 'region_code' => '12', 'population' => 281248, 'timezone' => 'Europe/Moscow'],
            ['name' => 'Стерлитамак', 'name_eng' => 'Sterlitamak', 'region_code' => '02', 'population' => 277410, 'timezone' => 'Asia/Yekaterinburg'],
        ];

        foreach ($cities as $cityData) {
            $region = $regions->get($cityData['region_code']);
            
            if (!$region) {
                $this->command->warn("Регион с кодом {$cityData['region_code']} не найден для города {$cityData['name']}");
                continue;
            }

            $slug = Str::slug($cityData['name']);
            
            City::updateOrCreate(
                [
                    'country_id' => $russia->id,
                    'region_id' => $region->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $cityData['name'],
                    'name_eng' => $cityData['name_eng'] ?? null,
                    'population' => $cityData['population'] ?? null,
                    'latitude' => $cityData['latitude'] ?? null,
                    'longitude' => $cityData['longitude'] ?? null,
                    'country_id' => $russia->id,
                    'region_id' => $region->id,
                    'slug' => $slug,
                    'is_capital' => $cityData['is_capital'] ?? false,
                    'is_active' => true,
                    'timezone' => $cityData['timezone'] ?? 'Europe/Moscow',
                ]
            );
        }

        $this->command->info('Города России успешно добавлены!');
    }
}
