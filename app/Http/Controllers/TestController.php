<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\TestService;
use App\Services\api\ApiService;
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
        $vkOauthRedirectUri = $redirectUrl . '/admin/test/vk-oauth';
        
        return view('test.index', [
            'results' => $results,
            'timestamp' => now()->toDateTimeString(),
            'vkRedirectUrl' => $redirectUrl,
            'vkOauthRedirectUri' => $vkOauthRedirectUri,
            'vkApiTokenSaved' => $request->session()->has('vk_api_token'),
            'vkUserTokenSaved' => $request->session()->has('vk_user_token'),
            'vkApiError' => $request->session()->get('vk_api_error'),
        ]);
    }

    /**
     * Получить группы пользователя ВК
     */
    public function getVkGroups(Request $request)
    {
        // Для получения групп требуется VK API токен с доступом groups
        $userToken = $request->input('user_token')
            ?: $request->session()->get('vk_api_token');
        if (!$userToken) {
            return ApiService::errorResponse(
                'Нужен VK API токен с доступом groups.',
                ApiService::VK_TOKEN_NOT_CONFIGURED,
                [],
                400
            );
        }

        $request->merge(['user_token' => $userToken]);

        $userId = $request->session()->get('vk_api_user_id')
            ?: $request->session()->get('vk_user_id');
        if ($userId && !$request->has('user_id')) {
            $request->merge(['user_id' => $userId]);
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
            'user_id' => 'sometimes|integer',
        ]);

        $request->session()->put('vk_user_token', $request->input('token'));
        if ($request->has('user_id')) {
            $request->session()->put('vk_user_id', (string) $request->input('user_id'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Токен сохранен',
        ]);
    }

    /**
     * OAuth callback для получения VK API токена (authorization code flow)
     */
    public function handleVkOAuth(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error_description') ?? $request->query('error');

        if (!$code) {
            $request->session()->flash('vk_api_error', $error ?: 'Код авторизации не получен.');
            return redirect()->route('admin.test');
        }

        $clientId = config('services.vk.app_id');
        $clientSecret = config('services.vk.client_secret');
        $redirectBase = config('services.vk.tunnel_url') ?: $request->getSchemeAndHttpHost();
        $redirectUri = $redirectBase . '/admin/test/vk-oauth';

        if (!$clientId || !$clientSecret) {
            $request->session()->flash('vk_api_error', 'Не задан VK_APP_ID или VK_CLIENT_SECRET.');
            return redirect()->route('admin.test');
        }

        $response = Http::get('https://oauth.vk.com/access_token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (!$response->ok()) {
            $request->session()->flash('vk_api_error', 'Не удалось получить токен VK API.');
            return redirect()->route('admin.test');
        }

        $data = $response->json();
        if (isset($data['error'])) {
            $request->session()->flash('vk_api_error', $data['error_description'] ?? $data['error']);
            return redirect()->route('admin.test');
        }

        if (empty($data['access_token'])) {
            $request->session()->flash('vk_api_error', 'VK API не вернул access_token.');
            return redirect()->route('admin.test');
        }

        $request->session()->put('vk_api_token', $data['access_token']);
        if (!empty($data['user_id'])) {
            $request->session()->put('vk_api_user_id', (string) $data['user_id']);
        }

        $request->session()->flash('vk_api_token_saved', true);

        return redirect()->route('admin.test');
    }
}

