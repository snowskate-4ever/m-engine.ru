<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FetchVkGroupPostsJob;
use App\Models\VkPost;
use App\Models\VkTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VkPostsController extends Controller
{
    /**
     * Страница сбора постов из групп VK
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

        return view('vk_posts_index', [
            'trackings' => $trackings,
            'postsCount' => $postsCount,
            'hasVkToken' => (bool) Auth::user()?->vk_access_token,
        ]);
    }

    /**
     * Поставить в очередь сбор постов для выбранных групп
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'vk_tracking_ids' => 'required|array',
            'vk_tracking_ids.*' => 'integer|exists:vk_trackings,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Войдите на сайт под учётной записью, у которой есть VK-токен.');
        }
        if (empty($user->vk_access_token)) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Сначала получите VK-токен: страница «VK Open API тест» → «Войти через OAuth».');
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
