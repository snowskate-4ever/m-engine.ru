<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RussiaRegionsSeeder extends Seeder
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

        $regions = [
            // Центральный федеральный округ
            ['name' => 'Москва', 'code' => '77', 'federal_district' => 'Центральный', 'sort_order' => 1],
            ['name' => 'Московская область', 'code' => '50', 'federal_district' => 'Центральный', 'sort_order' => 2],
            ['name' => 'Белгородская область', 'code' => '31', 'federal_district' => 'Центральный', 'sort_order' => 3],
            ['name' => 'Брянская область', 'code' => '32', 'federal_district' => 'Центральный', 'sort_order' => 4],
            ['name' => 'Владимирская область', 'code' => '33', 'federal_district' => 'Центральный', 'sort_order' => 5],
            ['name' => 'Воронежская область', 'code' => '36', 'federal_district' => 'Центральный', 'sort_order' => 6],
            ['name' => 'Ивановская область', 'code' => '37', 'federal_district' => 'Центральный', 'sort_order' => 7],
            ['name' => 'Калужская область', 'code' => '40', 'federal_district' => 'Центральный', 'sort_order' => 8],
            ['name' => 'Костромская область', 'code' => '44', 'federal_district' => 'Центральный', 'sort_order' => 9],
            ['name' => 'Курская область', 'code' => '46', 'federal_district' => 'Центральный', 'sort_order' => 10],
            ['name' => 'Липецкая область', 'code' => '48', 'federal_district' => 'Центральный', 'sort_order' => 11],
            ['name' => 'Орловская область', 'code' => '57', 'federal_district' => 'Центральный', 'sort_order' => 12],
            ['name' => 'Рязанская область', 'code' => '62', 'federal_district' => 'Центральный', 'sort_order' => 13],
            ['name' => 'Смоленская область', 'code' => '67', 'federal_district' => 'Центральный', 'sort_order' => 14],
            ['name' => 'Тамбовская область', 'code' => '68', 'federal_district' => 'Центральный', 'sort_order' => 15],
            ['name' => 'Тверская область', 'code' => '69', 'federal_district' => 'Центральный', 'sort_order' => 16],
            ['name' => 'Тульская область', 'code' => '71', 'federal_district' => 'Центральный', 'sort_order' => 17],
            ['name' => 'Ярославская область', 'code' => '76', 'federal_district' => 'Центральный', 'sort_order' => 18],

            // Северо-Западный федеральный округ
            ['name' => 'Санкт-Петербург', 'code' => '78', 'federal_district' => 'Северо-Западный', 'sort_order' => 20],
            ['name' => 'Ленинградская область', 'code' => '47', 'federal_district' => 'Северо-Западный', 'sort_order' => 21],
            ['name' => 'Архангельская область', 'code' => '29', 'federal_district' => 'Северо-Западный', 'sort_order' => 22],
            ['name' => 'Вологодская область', 'code' => '35', 'federal_district' => 'Северо-Западный', 'sort_order' => 23],
            ['name' => 'Калининградская область', 'code' => '39', 'federal_district' => 'Северо-Западный', 'sort_order' => 24],
            ['name' => 'Республика Карелия', 'code' => '10', 'federal_district' => 'Северо-Западный', 'sort_order' => 25],
            ['name' => 'Республика Коми', 'code' => '11', 'federal_district' => 'Северо-Западный', 'sort_order' => 26],
            ['name' => 'Мурманская область', 'code' => '51', 'federal_district' => 'Северо-Западный', 'sort_order' => 27],
            ['name' => 'Ненецкий автономный округ', 'code' => '83', 'federal_district' => 'Северо-Западный', 'sort_order' => 28],
            ['name' => 'Новгородская область', 'code' => '53', 'federal_district' => 'Северо-Западный', 'sort_order' => 29],
            ['name' => 'Псковская область', 'code' => '60', 'federal_district' => 'Северо-Западный', 'sort_order' => 30],

            // Южный федеральный округ
            ['name' => 'Республика Адыгея', 'code' => '01', 'federal_district' => 'Южный', 'sort_order' => 40],
            ['name' => 'Астраханская область', 'code' => '30', 'federal_district' => 'Южный', 'sort_order' => 41],
            ['name' => 'Волгоградская область', 'code' => '34', 'federal_district' => 'Южный', 'sort_order' => 42],
            ['name' => 'Республика Калмыкия', 'code' => '08', 'federal_district' => 'Южный', 'sort_order' => 43],
            ['name' => 'Краснодарский край', 'code' => '23', 'federal_district' => 'Южный', 'sort_order' => 44],
            ['name' => 'Ростовская область', 'code' => '61', 'federal_district' => 'Южный', 'sort_order' => 45],
            ['name' => 'Республика Крым', 'code' => '91', 'federal_district' => 'Южный', 'sort_order' => 46],
            ['name' => 'Севастополь', 'code' => '92', 'federal_district' => 'Южный', 'sort_order' => 47],

            // Северо-Кавказский федеральный округ
            ['name' => 'Республика Дагестан', 'code' => '05', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 50],
            ['name' => 'Республика Ингушетия', 'code' => '06', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 51],
            ['name' => 'Кабардино-Балкарская Республика', 'code' => '07', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 52],
            ['name' => 'Карачаево-Черкесская Республика', 'code' => '09', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 53],
            ['name' => 'Республика Северная Осетия — Алания', 'code' => '15', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 54],
            ['name' => 'Чеченская Республика', 'code' => '20', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 55],
            ['name' => 'Ставропольский край', 'code' => '26', 'federal_district' => 'Северо-Кавказский', 'sort_order' => 56],

            // Приволжский федеральный округ
            ['name' => 'Республика Башкортостан', 'code' => '02', 'federal_district' => 'Приволжский', 'sort_order' => 60],
            ['name' => 'Республика Марий Эл', 'code' => '12', 'federal_district' => 'Приволжский', 'sort_order' => 61],
            ['name' => 'Республика Мордовия', 'code' => '13', 'federal_district' => 'Приволжский', 'sort_order' => 62],
            ['name' => 'Республика Татарстан', 'code' => '16', 'federal_district' => 'Приволжский', 'sort_order' => 63],
            ['name' => 'Удмуртская Республика', 'code' => '18', 'federal_district' => 'Приволжский', 'sort_order' => 64],
            ['name' => 'Чувашская Республика', 'code' => '21', 'federal_district' => 'Приволжский', 'sort_order' => 65],
            ['name' => 'Кировская область', 'code' => '43', 'federal_district' => 'Приволжский', 'sort_order' => 66],
            ['name' => 'Нижегородская область', 'code' => '52', 'federal_district' => 'Приволжский', 'sort_order' => 67],
            ['name' => 'Оренбургская область', 'code' => '56', 'federal_district' => 'Приволжский', 'sort_order' => 68],
            ['name' => 'Пензенская область', 'code' => '58', 'federal_district' => 'Приволжский', 'sort_order' => 69],
            ['name' => 'Пермский край', 'code' => '59', 'federal_district' => 'Приволжский', 'sort_order' => 70],
            ['name' => 'Самарская область', 'code' => '63', 'federal_district' => 'Приволжский', 'sort_order' => 71],
            ['name' => 'Саратовская область', 'code' => '64', 'federal_district' => 'Приволжский', 'sort_order' => 72],
            ['name' => 'Ульяновская область', 'code' => '73', 'federal_district' => 'Приволжский', 'sort_order' => 73],

            // Уральский федеральный округ
            ['name' => 'Курганская область', 'code' => '45', 'federal_district' => 'Уральский', 'sort_order' => 80],
            ['name' => 'Свердловская область', 'code' => '66', 'federal_district' => 'Уральский', 'sort_order' => 81],
            ['name' => 'Тюменская область', 'code' => '72', 'federal_district' => 'Уральский', 'sort_order' => 82],
            ['name' => 'Челябинская область', 'code' => '74', 'federal_district' => 'Уральский', 'sort_order' => 83],
            ['name' => 'Ханты-Мансийский автономный округ — Югра', 'code' => '86', 'federal_district' => 'Уральский', 'sort_order' => 84],
            ['name' => 'Ямало-Ненецкий автономный округ', 'code' => '89', 'federal_district' => 'Уральский', 'sort_order' => 85],

            // Сибирский федеральный округ
            ['name' => 'Республика Алтай', 'code' => '04', 'federal_district' => 'Сибирский', 'sort_order' => 90],
            ['name' => 'Республика Бурятия', 'code' => '03', 'federal_district' => 'Сибирский', 'sort_order' => 91],
            ['name' => 'Республика Тыва', 'code' => '17', 'federal_district' => 'Сибирский', 'sort_order' => 92],
            ['name' => 'Республика Хакасия', 'code' => '19', 'federal_district' => 'Сибирский', 'sort_order' => 93],
            ['name' => 'Алтайский край', 'code' => '22', 'federal_district' => 'Сибирский', 'sort_order' => 94],
            ['name' => 'Забайкальский край', 'code' => '75', 'federal_district' => 'Сибирский', 'sort_order' => 95],
            ['name' => 'Красноярский край', 'code' => '24', 'federal_district' => 'Сибирский', 'sort_order' => 96],
            ['name' => 'Иркутская область', 'code' => '38', 'federal_district' => 'Сибирский', 'sort_order' => 97],
            ['name' => 'Кемеровская область', 'code' => '42', 'federal_district' => 'Сибирский', 'sort_order' => 98],
            ['name' => 'Новосибирская область', 'code' => '54', 'federal_district' => 'Сибирский', 'sort_order' => 99],
            ['name' => 'Омская область', 'code' => '55', 'federal_district' => 'Сибирский', 'sort_order' => 100],
            ['name' => 'Томская область', 'code' => '70', 'federal_district' => 'Сибирский', 'sort_order' => 101],

            // Дальневосточный федеральный округ
            ['name' => 'Республика Саха (Якутия)', 'code' => '14', 'federal_district' => 'Дальневосточный', 'sort_order' => 110],
            ['name' => 'Камчатский край', 'code' => '41', 'federal_district' => 'Дальневосточный', 'sort_order' => 111],
            ['name' => 'Приморский край', 'code' => '25', 'federal_district' => 'Дальневосточный', 'sort_order' => 112],
            ['name' => 'Хабаровский край', 'code' => '27', 'federal_district' => 'Дальневосточный', 'sort_order' => 113],
            ['name' => 'Амурская область', 'code' => '28', 'federal_district' => 'Дальневосточный', 'sort_order' => 114],
            ['name' => 'Магаданская область', 'code' => '49', 'federal_district' => 'Дальневосточный', 'sort_order' => 115],
            ['name' => 'Сахалинская область', 'code' => '65', 'federal_district' => 'Дальневосточный', 'sort_order' => 116],
            ['name' => 'Еврейская автономная область', 'code' => '79', 'federal_district' => 'Дальневосточный', 'sort_order' => 117],
            ['name' => 'Чукотский автономный округ', 'code' => '87', 'federal_district' => 'Дальневосточный', 'sort_order' => 118],
        ];

        // Get the default region type, create if not exists
        $regionType = \App\Models\Type::firstOrCreate(
            ['resource_type' => 'region'],
            ['name' => 'Region', 'description' => 'Administrative region']
        );

        foreach ($regions as $region) {
            Region::updateOrCreate(
                [
                    'country_id' => $russia->id,
                    'code' => $region['code'],
                ],
                [
                    'type_id' => $regionType->id,
                    'name' => $region['name'],
                    'federal_district' => $region['federal_district'],
                    'sort_order' => $region['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Регионы России успешно добавлены!');
    }
}
