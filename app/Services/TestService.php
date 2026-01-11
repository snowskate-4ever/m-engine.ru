<?php

namespace App\Services;

use Illuminate\Http\Request;

class TestService
{
    /**
     * Запустить все тесты
     */
    public static function runTests(Request $request)
    {
        $results = [];
        
        // Тест 1: Проверка подключения к базе данных
        $results['database_connection'] = self::testDatabaseConnection();
        
        // Тест 2: Проверка моделей
        $results['models'] = self::testModels();
        
        // Тест 3: Проверка сервисов
        $results['services'] = self::testServices();
        
        // Тест 4: Проверка конфигурации
        $results['configuration'] = self::testConfiguration();
        
        return $results;
    }
    
    /**
     * Тест подключения к базе данных
     */
    protected static function testDatabaseConnection()
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'success',
                'message' => 'Подключение к базе данных успешно',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка подключения к базе данных: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Тест моделей
     */
    protected static function testModels()
    {
        $results = [];
        
        // Проверяем наличие основных моделей
        $models = [
            'User' => \App\Models\User::class,
            'Resource' => \App\Models\Resource::class,
            'Event' => \App\Models\Event::class,
        ];
        
        foreach ($models as $name => $class) {
            try {
                $count = $class::count();
                $results[$name] = [
                    'status' => 'success',
                    'message' => "Модель {$name} работает корректно",
                    'count' => $count,
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'message' => "Ошибка в модели {$name}: " . $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Тест сервисов
     */
    protected static function testServices()
    {
        $results = [];
        
        // Проверяем наличие основных сервисов
        $services = [
            'DashboardService' => \App\Services\DashboardService::class,
            'ResourceService' => \App\Services\ResourceService::class,
            'EventService' => \App\Services\EventService::class,
        ];
        
        foreach ($services as $name => $class) {
            try {
                if (class_exists($class)) {
                    $results[$name] = [
                        'status' => 'success',
                        'message' => "Сервис {$name} найден",
                    ];
                } else {
                    $results[$name] = [
                        'status' => 'error',
                        'message' => "Сервис {$name} не найден",
                    ];
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'message' => "Ошибка в сервисе {$name}: " . $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Тест конфигурации
     */
    protected static function testConfiguration()
    {
        $results = [];
        
        // Проверяем основные настройки
        $configs = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'db_connection' => config('database.default'),
        ];
        
        foreach ($configs as $key => $value) {
            $results[$key] = [
                'status' => 'success',
                'value' => $value,
            ];
        }
        
        return $results;
    }
}

