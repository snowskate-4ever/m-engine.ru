<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FetchVkGroupPostsJob;
use App\Models\VkPost;
use App\Models\VkTracking;
use Illuminate\Http\Request;

class VkPostsController extends Controller
{
    private const VK_TOKEN_USER_EMAIL = 'mad.md@yandex.ru';

    /**
     * Пользователь, у которого берётся vk_access_token для сбора постов
     */
    private static function userWithVkToken(): ?User
    {
        return User::where('email', self::VK_TOKEN_USER_EMAIL)->first();
    }

    /**
     * Страница сбора постов из групп VK
     * Токен берётся из пользователя mad.md@yandex.ru (vk_access_token), не из сессии.
     */
    public function index(Request $request)
    {
        $trackings = VkTracking::where('is_active', true)
            ->orderBy('name')
            ->get();

        $postsCount = VkPost::query()
            ->selectRaw('vk_tracking_id, count(*) as cnt')
            ->groupBy('vk_tracking_id')
            ->pluck('cnt', 'vk_tracking_id');

        $vkUser = self::userWithVkToken();

        return view('vk_posts_index', [
            'trackings' => $trackings,
            'postsCount' => $postsCount,
            'hasVkToken' => (bool) ($vkUser?->vk_access_token ?? null),
        ]);
    }

    /**
     * Поставить в очередь сбор постов для выбранных групп
     * Токен берётся из пользователя mad.md@yandex.ru (vk_access_token).
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'vk_tracking_ids' => 'required|array',
            'vk_tracking_ids.*' => 'integer|exists:vk_trackings,id',
        ]);

        $user = self::userWithVkToken();
        if (!$user) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Пользователь с почтой ' . self::VK_TOKEN_USER_EMAIL . ' не найден.');
        }
        if (empty($user->vk_access_token)) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Сначала получите VK-токен для этого пользователя: страница «VK Open API тест» → «Войти через OAuth».');
        }

        $count = (int) $request->input('count', 100);
        $count = min(max(1, $count), 100);

        foreach ($request->input('vk_tracking_ids') as $vkTrackingId) {
            $tracking = VkTracking::find($vkTrackingId);
            if (!$tracking || !$tracking->is_active) {
                continue;
            }
            FetchVkGroupPostsJob::dispatch(
                (int) $vkTrackingId,
                $user->id,
                $count,
                $tracking->next_from
            );
        }

        return redirect()
            ->route('admin.vk-posts.index')
            ->with('success', 'Задачи сбора постов поставлены в очередь. Запустите воркер: php artisan queue:work --queue=vk,default');
    }
}
