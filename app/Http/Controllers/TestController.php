<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TestService;
use App\Services\api\VkApiService;

class TestController extends Controller
{
    /**
     * Страница тестов
     */
    public function index(Request $request)
    {
        $results = TestService::runTests($request);
        
        // Получаем текущий URL для VK ID redirect
        // Если указан туннелинг URL в конфигурации, используем его (для HTTPS)
        $tunnelUrl = config('services.vk.tunnel_url');
        $redirectUrl = $tunnelUrl ?: $request->getSchemeAndHttpHost();
        
        return view('test.index', [
            'results' => $results,
            'timestamp' => now()->toDateTimeString(),
            'vkRedirectUrl' => $redirectUrl,
        ]);
    }

    /**
     * Получить группы пользователя ВК
     */
    public function getVkGroups(Request $request)
    {
        // Используем токен пользователя из сессии, если есть
        $userToken = $request->session()->get('vk_user_token');
        if ($userToken) {
            $request->merge(['user_token' => $userToken]);
        }
        
        return VkApiService::getUserGroups($request);
    }

    /**
     * Сохранить токен пользователя ВК
     */
    public function saveVkToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'user_id' => 'sometimes|string',
        ]);

        $request->session()->put('vk_user_token', $request->input('token'));
        if ($request->has('user_id')) {
            $request->session()->put('vk_user_id', $request->input('user_id'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Токен сохранен',
        ]);
    }
}

