<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VkSetting;
use App\Services\api\VkApiService;
use Illuminate\Http\Request;

class VkFeedController extends Controller
{
    /**
     * Страница «Лента пользователя» — посты со стены текущего VK-пользователя (wall.get).
     * Токен и vk_user_id берутся из таблицы vk_settings (VkSetting::instance()).
     */
    public function index(Request $request)
    {
        $settings = VkSetting::instance();
        $token = $settings->vk_access_token ?? null;

        if (empty($token)) {
            return redirect()
                ->route('admin.vk')
                ->with('error', 'Сначала получите VK-токен: страница «VK» → «Войти через OAuth».');
        }

        $vkUserId = $settings->vk_user_id;
        if (empty($vkUserId)) {
            return redirect()
                ->route('admin.vk')
                ->with('error', 'VK ID пользователя не сохранён. Повторно сохраните токен через OAuth.');
        }

        $userId = (int) $vkUserId;
        $count = min(max(1, (int) $request->input('count', 30)), 100);
        $offset = max(0, (int) $request->input('offset', 0));
        $startFrom = $request->input('start_from');

        $service = new VkApiService();
        $result = $service->getUserWallPosts($token, $userId, $count, $offset, $startFrom ?: null);

        if ($result['error']) {
            return view('vk_feed', [
                'posts' => [],
                'nextFrom' => null,
                'hasVkToken' => true,
                'vkUserId' => $userId,
                'error' => $result['error_msg'] ?? 'Ошибка при загрузке ленты.',
            ]);
        }

        $items = $result['response']['items'] ?? [];
        $nextFrom = $result['response']['next_from'] ?? null;

        return view('vk_feed', [
            'posts' => $items,
            'nextFrom' => $nextFrom,
            'hasVkToken' => true,
            'vkUserId' => $userId,
            'error' => null,
        ]);
    }

    /**
     * Лента новостей (newsfeed.get). По 15 записей, «Загрузить ещё» через start_from.
     * Токен из таблицы vk_settings.
     */
    public function newsfeed(Request $request)
    {
        $settings = VkSetting::instance();
        $token = $settings->vk_access_token ?? null;

        if (empty($token)) {
            return redirect()
                ->route('admin.vk')
                ->with('error', 'Сначала получите VK-токен: страница «VK» → «Войти через OAuth».');
        }

        $count = 15;
        $startFrom = $request->input('start_from');

        $service = new VkApiService();
        $result = $service->getNewsfeed($token, $count, $startFrom ?: null);

        if ($result['error']) {
            $errorMsg = $result['error_msg'] ?? 'Ошибка при загрузке ленты новостей.';
            $isAccessDenied = (stripos($errorMsg, 'access denied') !== false)
                || (stripos($errorMsg, 'current scopes') !== false);
            return view('vk_newsfeed', [
                'items' => [],
                'nextFrom' => null,
                'profiles' => [],
                'groups' => [],
                'error' => $errorMsg,
                'errorIsAccessDenied' => $isAccessDenied,
            ]);
        }

        $response = $result['response'];
        $items = $response['items'] ?? [];
        $nextFrom = $response['next_from'] ?? null;
        $profiles = $response['profiles'] ?? [];
        $groups = $response['groups'] ?? [];

        return view('vk_newsfeed', [
            'items' => $items,
            'nextFrom' => $nextFrom,
            'profiles' => $profiles,
            'groups' => $groups,
            'error' => null,
            'errorIsAccessDenied' => false,
        ]);
    }
}
