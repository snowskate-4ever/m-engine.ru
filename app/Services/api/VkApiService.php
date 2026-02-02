<?php

namespace App\Services\api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VkApiService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $apiVersion;

    public function __construct()
    {
        $this->apiUrl = config('services.vk.api_url', 'https://api.vk.com/method');
        $this->accessToken = config('services.vk.access_token');
        $this->apiVersion = config('services.vk.api_version', '5.131');
    }

    /**
     * Выполнить запрос к VK API
     *
     * @param string $method Метод API (например, 'users.get', 'wall.post')
     * @param array $params Параметры запроса
     * @param string|null $userToken Токен пользователя (если передан, используется вместо сервисного ключа)
     * @return array
     */
    protected function makeRequest(string $method, array $params = [], ?string $userToken = null): array
    {
        // Используем токен пользователя, если передан, иначе сервисный ключ
        $token = $userToken ?? $this->accessToken;
        
        if (empty($token)) {
            return [
                'error' => true,
                'error_code' => ApiService::VK_TOKEN_NOT_CONFIGURED,
                'error_msg' => 'VK API токен не настроен. Установите VK_ACCESS_TOKEN в .env файле или авторизуйтесь через VK ID.',
            ];
        }

        $params['access_token'] = $token;
        $params['v'] = $this->apiVersion;

        try {
            $httpClient = Http::timeout(30);
            
            // Отключаем проверку SSL только если это явно указано в конфигурации
            if (!config('services.vk.verify_ssl', true)) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            $response = $httpClient->get("{$this->apiUrl}/{$method}", $params);

            if ($response->failed()) {
                Log::error('VK API request failed', [
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'error' => true,
                    'error_code' => $response->status(),
                    'error_msg' => 'Ошибка при выполнении запроса к VK API',
                ];
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('VK API error', [
                    'method' => $method,
                    'error' => $data['error'],
                ]);

                return [
                    'error' => true,
                    'error_code' => $data['error']['error_code'] ?? ApiService::VK_API_ERROR,
                    'error_msg' => $data['error']['error_msg'] ?? 'Неизвестная ошибка VK API',
                ];
            }

            return [
                'error' => false,
                'response' => $data['response'] ?? $data,
            ];
        } catch (\Exception $e) {
            Log::error('VK API exception', [
                'method' => $method,
                'exception' => $e->getMessage(),
            ]);

            return [
                'error' => true,
                'error_code' => ApiService::VK_API_ERROR,
                'error_msg' => 'Исключение при выполнении запроса: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о пользователях
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|string',
            'fields' => 'sometimes|string',
        ], [
            'user_ids.required' => 'Параметр user_ids обязателен.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $service = new self();
        $params = [
            'user_ids' => $request->input('user_ids'),
        ];

        if ($request->has('fields')) {
            $params['fields'] = $request->input('fields');
        }

        $result = $service->makeRequest('users.get', $params);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [],
                400
            );
        }

        return ApiService::successResponse('Информация о пользователях получена', [
            'users' => $result['response'],
        ]);
    }

    /**
     * Получить информацию о группах
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getGroups(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_ids' => 'sometimes|string',
            'group_id' => 'sometimes|string',
            'fields' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $service = new self();
        $params = [];

        if ($request->has('group_ids')) {
            $params['group_ids'] = $request->input('group_ids');
        } elseif ($request->has('group_id')) {
            $params['group_id'] = $request->input('group_id');
        }

        if ($request->has('fields')) {
            $params['fields'] = $request->input('fields');
        }

        $result = $service->makeRequest('groups.getById', $params);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [],
                400
            );
        }

        return ApiService::successResponse('Информация о группах получена', [
            'groups' => $result['response'],
        ]);
    }

    /**
     * Опубликовать запись на стене
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function wallPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|string',
            'message' => 'required|string|max:4096',
            'attachments' => 'sometimes|string',
            'from_group' => 'sometimes|boolean',
        ], [
            'owner_id.required' => 'Параметр owner_id обязателен.',
            'message.required' => 'Сообщение обязательно для заполнения.',
            'message.max' => 'Сообщение не должно превышать 4096 символов.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $service = new self();
        $params = [
            'owner_id' => $request->input('owner_id'),
            'message' => $request->input('message'),
        ];

        if ($request->has('attachments')) {
            $params['attachments'] = $request->input('attachments');
        }

        if ($request->has('from_group')) {
            $params['from_group'] = $request->input('from_group') ? 1 : 0;
        }

        $result = $service->makeRequest('wall.post', $params);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [],
                400
            );
        }

        return ApiService::successResponse('Запись опубликована', [
            'post_id' => $result['response']['post_id'] ?? null,
        ]);
    }

    /**
     * Тест доступности VK API
     * Проверяет подключение, используя метод users.get с ID пользователя Дурова (1)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function testConnection()
    {
        $service = new self();

        // Проверяем наличие токена
        if (empty($service->accessToken)) {
            return ApiService::errorResponse(
                'VK API токен не настроен. Установите VK_ACCESS_TOKEN в .env файле.',
                ApiService::VK_TOKEN_NOT_CONFIGURED,
                [],
                400
            );
        }

        // Выполняем тестовый запрос - получаем информацию о пользователе с ID 1 (Павел Дуров)
        $result = $service->makeRequest('users.get', [
            'user_ids' => '1',
            'fields' => 'first_name,last_name',
        ]);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [
                    'token_configured' => !empty($service->accessToken),
                    'api_url' => $service->apiUrl,
                    'api_version' => $service->apiVersion,
                ],
                400
            );
        }

        $user = $result['response'][0] ?? null;

        return ApiService::successResponse('VK API доступен и работает корректно', [
            'connection_status' => 'success',
            'token_configured' => true,
            'api_url' => $service->apiUrl,
            'api_version' => $service->apiVersion,
            'test_user' => $user ? [
                'id' => $user['id'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
            ] : null,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Получить группы пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getUserGroups(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|string',
            'extended' => 'sometimes|boolean',
            'fields' => 'sometimes|string',
            'offset' => 'sometimes|integer|min:0',
            'count' => 'sometimes|integer|min:1|max:1000',
            'user_token' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $service = new self();
        $params = [
            'extended' => $request->input('extended', 1), // По умолчанию расширенная информация
        ];

        if ($request->has('user_id')) {
            $params['user_id'] = $request->input('user_id');
        }

        if ($request->has('fields')) {
            $params['fields'] = $request->input('fields');
        }

        if ($request->has('offset')) {
            $params['offset'] = $request->input('offset');
        }

        if ($request->has('count')) {
            $params['count'] = $request->input('count');
        }

        // Используем токен пользователя, если передан
        $userToken = $request->input('user_token');
        $result = $service->makeRequest('groups.get', $params, $userToken);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [],
                400
            );
        }

        return ApiService::successResponse('Группы пользователя получены', [
            'groups' => $result['response']['items'] ?? $result['response'],
            'count' => $result['response']['count'] ?? count($result['response']),
        ]);
    }

    /**
     * Выполнить произвольный метод VK API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function executeMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|string',
            'params' => 'sometimes|array',
        ], [
            'method.required' => 'Параметр method обязателен.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $service = new self();
        $method = $request->input('method');
        $params = $request->input('params', []);

        $result = $service->makeRequest($method, $params);

        if ($result['error']) {
            return ApiService::errorResponse(
                $result['error_msg'],
                $result['error_code'],
                [],
                400
            );
        }

        return ApiService::successResponse('Метод выполнен успешно', [
            'response' => $result['response'],
        ]);
    }

    /**
     * Получить посты со стены сообщества (wall.get)
     *
     * @param int $groupId ID группы VK (положительное число, в API передаётся как -groupId)
     * @param string|null $userToken Токен пользователя с доступом к группе
     * @param int $count Количество постов (макс. 100)
     * @param int $offset Смещение
     * @param string|null $startFrom Значение next_from из предыдущего ответа для пагинации
     * @return array { error: bool, response?: { items: [], next_from?: string }, error_msg?: string }
     */
    public function getWallPosts(
        int $groupId,
        ?string $userToken,
        int $count = 100,
        int $offset = 0,
        ?string $startFrom = null
    ): array {
        $params = [
            'owner_id' => -$groupId,
            'count' => min(max(1, $count), 100),
            'extended' => 1,
        ];
        if ($startFrom !== null && $startFrom !== '') {
            $params['start_from'] = $startFrom;
        } else {
            $params['offset'] = $offset;
        }
        $result = $this->makeRequest('wall.get', $params, $userToken);
        if ($result['error']) {
            return $result;
        }
        $res = $result['response'];
        $items = $res['items'] ?? [];
        $nextFrom = $res['next_from'] ?? null;
        return [
            'error' => false,
            'response' => [
                'items' => $items,
                'next_from' => $nextFrom,
            ],
        ];
    }

    /**
     * Получить посты со стены пользователя (wall.get)
     *
     * @param string|null $userToken Токен пользователя VK
     * @param int $userId VK ID пользователя (положительное число)
     * @param int $count Количество постов (макс. 100)
     * @param int $offset Смещение
     * @param string|null $startFrom Значение next_from для пагинации
     * @return array { error: bool, response?: { items: [], next_from?: string }, error_msg?: string }
     */
    public function getUserWallPosts(
        ?string $userToken,
        int $userId,
        int $count = 50,
        int $offset = 0,
        ?string $startFrom = null
    ): array {
        $params = [
            'owner_id' => $userId,
            'count' => min(max(1, $count), 100),
            'extended' => 1,
        ];
        if ($startFrom !== null && $startFrom !== '') {
            $params['start_from'] = $startFrom;
        } else {
            $params['offset'] = $offset;
        }
        $result = $this->makeRequest('wall.get', $params, $userToken);
        if ($result['error']) {
            return $result;
        }
        $res = $result['response'];
        $items = $res['items'] ?? [];
        $nextFrom = $res['next_from'] ?? null;
        return [
            'error' => false,
            'response' => [
                'items' => $items,
                'next_from' => $nextFrom,
            ],
        ];
    }

    /**
     * Получить ленту новостей пользователя (newsfeed.get).
     * Токен из vk_settings. По 15 записей, пагинация через start_from.
     *
     * @param string|null $userToken Токен пользователя VK
     * @param int $count Количество записей (по умолчанию 15, макс. 100)
     * @param string|null $startFrom Значение next_from для «Загрузить ещё»
     * @return array { error: bool, response?: { items: [], next_from?: string, profiles?: [], groups?: [] }, error_msg?: string }
     */
    public function getNewsfeed(
        ?string $userToken,
        int $count = 15,
        ?string $startFrom = null
    ): array {
        $params = [
            'count' => min(max(1, $count), 100),
            'extended' => 1,
        ];
        if ($startFrom !== null && $startFrom !== '') {
            $params['start_from'] = $startFrom;
        }
        $result = $this->makeRequest('newsfeed.get', $params, $userToken);
        if ($result['error']) {
            return $result;
        }
        $res = $result['response'];
        $items = $res['items'] ?? [];
        $nextFrom = $res['next_from'] ?? null;
        $profiles = $res['profiles'] ?? [];
        $groups = $res['groups'] ?? [];
        return [
            'error' => false,
            'response' => [
                'items' => $items,
                'next_from' => $nextFrom,
                'profiles' => $profiles,
                'groups' => $groups,
            ],
        ];
    }

    /**
     * Получить группы пользователя (groups.get) для страницы «Группы».
     * Токен из vk_settings.
     *
     * @param string|null $userToken Токен пользователя VK
     * @param int $count Количество (макс. 1000)
     * @param int $offset Смещение
     * @return array { error: bool, response?: { items: [], count: int }, error_msg?: string }
     */
    public function getUsersGroupsList(
        ?string $userToken,
        int $count = 100,
        int $offset = 0
    ): array {
        $params = [
            'extended' => 1,
            'count' => min(max(1, $count), 1000),
            'offset' => max(0, $offset),
        ];
        $result = $this->makeRequest('groups.get', $params, $userToken);
        if ($result['error']) {
            return $result;
        }
        $res = $result['response'];
        $items = $res['items'] ?? [];
        $totalCount = $res['count'] ?? count($items);
        return [
            'error' => false,
            'response' => [
                'items' => $items,
                'count' => (int) $totalCount,
            ],
        ];
    }
}

