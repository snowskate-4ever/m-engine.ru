<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TestService;
use App\Services\api\ApiService;
use App\Services\api\VkApiService;

class TestController extends Controller
{
    /**
     * Страница тестов VK Open API
     */
    public function openApiIndex(Request $request)
    {
        $results = TestService::runTests($request);

        $tunnelUrl = config('services.vk.tunnel_url');
        $redirectUrl = $tunnelUrl ?: $request->getSchemeAndHttpHost();
        $vkTrackings = \App\Models\VkTracking::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('test.openapi', [
            'results' => $results,
            'timestamp' => now()->toDateTimeString(),
            'vkRedirectUrl' => $redirectUrl,
            'vkOpenApiAppId' => config('services.vk.app_id'),
            'vkTrackings' => $vkTrackings,
        ]);
    }

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
        $vkOauthRedirectUri = $redirectUrl . '/vk-oauth';
        
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
        Log::info('VK groups request (admin.test.vk-groups)', [
            'session_id' => $request->session()->getId(),
            'has_vk_api_token' => $request->session()->has('vk_api_token'),
            'has_vk_user_token' => $request->session()->has('vk_user_token'),
            'has_user_id' => $request->session()->has('vk_api_user_id') || $request->session()->has('vk_user_id'),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

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
     * Сохранить и проверить сессию VK Open API
     */
    public function saveVkOpenApiSession(Request $request)
    {
        $request->validate([
            'session' => 'required|array',
        ]);

        $session = $request->input('session');
        $requiredKeys = ['expire', 'mid', 'secret', 'sid', 'sig'];

        foreach ($requiredKeys as $key) {
            if (!isset($session[$key])) {
                return ApiService::errorResponse(
                    'Отсутствует параметр сессии: ' . $key,
                    ApiService::UNPROCESSABLE_CONTENT,
                    [],
                    422
                );
            }
        }

        $protectedKey = config('services.vk.protected_key');
        if (empty($protectedKey)) {
            return ApiService::errorResponse(
                'VK_PROTECTED_KEY не настроен.',
                ApiService::VK_TOKEN_NOT_CONFIGURED,
                [],
                400
            );
        }

        $signSession = [
            'expire' => $session['expire'],
            'mid' => $session['mid'],
            'secret' => $session['secret'],
            'sid' => $session['sid'],
        ];
        ksort($signSession);

        $sign = '';
        foreach ($signSession as $key => $value) {
            if (is_array($value) || is_object($value)) {
                return ApiService::errorResponse(
                    'Некорректное значение сессии VK: ' . $key,
                    ApiService::UNPROCESSABLE_CONTENT,
                    [],
                    422
                );
            }
            $sign .= $key . '=' . (string) $value;
        }
        $sign .= $protectedKey;
        $calcSig = md5($sign);

        if (!hash_equals($calcSig, (string) $session['sig'])) {
            Log::warning('VK Open API session signature mismatch', [
                'calc' => $calcSig,
                'sig' => $session['sig'],
            ]);
            return ApiService::errorResponse(
                'Некорректная подпись сессии VK.',
                ApiService::VK_API_ERROR,
                [],
                403
            );
        }

        if ((int) $session['expire'] < time()) {
            return ApiService::errorResponse(
                'Сессия VK истекла.',
                ApiService::VK_API_ERROR,
                [],
                403
            );
        }

        $request->session()->put('vk_openapi_session', $session);
        $request->session()->put('vk_openapi_user_id', (string) $session['mid']);

        return ApiService::successResponse('Сессия VK сохранена', [
            'user_id' => (string) $session['mid'],
        ]);
    }

    /**
     * OAuth callback для получения VK API токена (authorization code flow)
     */
    public function startVkOAuth(Request $request)
    {
        $clientId = config('services.vk.app_id');
        $redirectBase = config('services.vk.tunnel_url') ?: $request->getSchemeAndHttpHost();
        $redirectUri = $redirectBase . '/vk-oauth';

        if (!$clientId) {
            $request->session()->flash('vk_api_error', 'Не задан VK_APP_ID.');
            return redirect()->route('admin.test');
        }

        Log::info('VK OAuth start', [
            'session_id' => $request->session()->getId(),
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'origin' => $request->getSchemeAndHttpHost(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'groups',
            'response_type' => 'code',
            'v' => config('services.vk.api_version', '5.131'),
            'display' => 'page',
        ]);

        return redirect()->away('https://oauth.vk.com/authorize?' . $query);
    }

    /**
     * OAuth callback для получения VK API токена (authorization code flow)
     */
    public function handleVkOAuth(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error_description') ?? $request->query('error');

        Log::info('VK OAuth callback', [
            'session_id' => $request->session()->getId(),
            'code_present' => !empty($code),
            'error' => $error,
            'redirected_from' => $request->headers->get('referer'),
            'query' => $request->query(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (!$code) {
            $request->session()->flash('vk_api_error', $error ?: 'Код авторизации не получен.');
            return redirect()->route('admin.test');
        }

        $clientId = config('services.vk.app_id');
        $clientSecret = config('services.vk.client_secret');
        $redirectBase = config('services.vk.tunnel_url') ?: $request->getSchemeAndHttpHost();
        $redirectUri = $redirectBase . '/vk-oauth';

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
            Log::warning('VK OAuth token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $request->session()->flash('vk_api_error', 'Не удалось получить токен VK API.');
            return redirect()->route('admin.test');
        }

        $data = $response->json();
        if (isset($data['error'])) {
            Log::warning('VK OAuth token error', [
                'error' => $data['error'],
                'error_description' => $data['error_description'] ?? null,
            ]);
            $request->session()->flash('vk_api_error', $data['error_description'] ?? $data['error']);
            return redirect()->route('admin.test');
        }

        if (empty($data['access_token'])) {
            Log::warning('VK OAuth token missing access_token', [
                'response' => $data,
            ]);
            $request->session()->flash('vk_api_error', 'VK API не вернул access_token.');
            return redirect()->route('admin.test');
        }

        $request->session()->put('vk_api_token', $data['access_token']);
        if (!empty($data['user_id'])) {
            $request->session()->put('vk_api_user_id', (string) $data['user_id']);
        }
        Log::info('VK OAuth token stored in session', [
            'session_id' => $request->session()->getId(),
            'user_id' => $data['user_id'] ?? null,
            'client_ip' => $request->ip(),
        ]);

        $request->session()->flash('vk_api_token_saved', true);

        return redirect()->route('admin.test');
    }
}

