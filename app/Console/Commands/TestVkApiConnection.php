<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\api\VkApiService;

class TestVkApiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vk:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование доступности VK API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Проверка доступности VK API...');
        $this->newLine();

        // Проверяем конфигурацию
        $token = config('services.vk.access_token');
        $apiVersion = config('services.vk.api_version', '5.131');
        $apiUrl = config('services.vk.api_url', 'https://api.vk.com/method');

        $this->line('Конфигурация:');
        $this->line('  API URL: ' . $apiUrl);
        $this->line('  API Version: ' . $apiVersion);
        $this->line('  Token: ' . ($token ? '✓ Настроен (' . substr($token, 0, 10) . '...)' : '✗ Не настроен'));
        $this->newLine();

        if (empty($token)) {
            $this->error('ОШИБКА: VK_ACCESS_TOKEN не настроен в .env файле!');
            $this->line('Установите переменную VK_ACCESS_TOKEN в файле .env');
            return Command::FAILURE;
        }

        // Выполняем тестовый запрос
        $this->info('Выполнение тестового запроса к VK API...');
        
        $result = VkApiService::testConnection();
        $responseData = json_decode($result->getContent(), true);

        if ($responseData['success']) {
            $this->newLine();
            $this->info('✓ VK API доступен и работает корректно!');
            $this->newLine();
            
            $data = $responseData['data'];
            $this->line('Детали подключения:');
            $this->line('  Статус: ' . ($data['connection_status'] ?? 'unknown'));
            $this->line('  Токен настроен: ' . ($data['token_configured'] ? 'Да' : 'Нет'));
            $this->line('  API URL: ' . ($data['api_url'] ?? 'unknown'));
            $this->line('  API Version: ' . ($data['api_version'] ?? 'unknown'));
            
            if (isset($data['test_user'])) {
                $user = $data['test_user'];
                $this->newLine();
                $this->line('Тестовый пользователь (ID: 1):');
                $this->line('  Имя: ' . ($user['first_name'] ?? 'N/A') . ' ' . ($user['last_name'] ?? 'N/A'));
            }
            
            $this->newLine();
            $this->line('Время проверки: ' . ($data['timestamp'] ?? 'N/A'));
            
            return Command::SUCCESS;
        } else {
            $this->newLine();
            $this->error('✗ Ошибка при подключении к VK API');
            $this->newLine();
            $this->line('Код ошибки: ' . ($responseData['codError'] ?? 'unknown'));
            $this->line('Сообщение: ' . ($responseData['message'] ?? 'unknown'));
            
            if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                $this->newLine();
                $this->line('Дополнительная информация:');
                foreach ($responseData['errors'] as $key => $value) {
                    $this->line('  ' . $key . ': ' . (is_array($value) ? json_encode($value) : $value));
                }
            }
            
            return Command::FAILURE;
        }
    }
}
