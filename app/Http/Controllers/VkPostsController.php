<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VkPost;
use App\Models\VkSetting;
use App\Models\VkTracking;
use App\Services\FetchVkGroupPostsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VkPostsController extends Controller
{
    /**
     * Настройки VK (токен из таблицы vk_settings)
     */
    private static function vkSettings(): VkSetting
    {
        return VkSetting::instance();
    }

    /**
     * Страница сбора постов из групп VK
     * Токен берётся из таблицы vk_settings.
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

        $settings = self::vkSettings();

        return view('vk_posts_index', [
            'trackings' => $trackings,
            'postsCount' => $postsCount,
            'hasVkToken' => (bool) ($settings->vk_access_token ?? null),
            'debugLog' => $request->session()->get('debug_log'),
        ]);
    }

    /**
     * Лог загрузки постов: таблица с датой записи, названием группы, номером поста.
     */
    public function log(Request $request)
    {
        $posts = VkPost::query()
            ->with('vkTracking')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('vk_posts_log', [
            'posts' => $posts,
        ]);
    }

    /**
     * Поставить в очередь сбор постов для выбранных групп
     * Токен берётся из таблицы vk_settings.
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'vk_tracking_ids' => 'required|array',
            'vk_tracking_ids.*' => 'integer|exists:vk_trackings,id',
        ]);

        $settings = self::vkSettings();
        if (empty($settings->vk_access_token)) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Сначала получите VK-токен: страница «Токен» (/admin/vk) → «Войти через OAuth».');
        }

        $count = (int) $request->input('count', 100);
        $count = min(max(1, $count), 100);
        $offset = (int) $request->input('offset', 0);
        $offset = max(0, $offset);

        foreach ($request->input('vk_tracking_ids') as $vkTrackingId) {
            $tracking = VkTracking::find($vkTrackingId);
            if (!$tracking || !$tracking->is_active) {
                continue;
            }
            // При offset > 0 используем смещение; при 0 — next_from (продолжение с прошлого раза)
            $startFrom = ($offset > 0) ? null : $tracking->next_from;
            FetchVkGroupPostsJob::dispatch(
                (int) $vkTrackingId,
                0,
                $count,
                $startFrom,
                $offset
            );
        }

        return redirect()
            ->route('admin.vk-posts.index')
            ->with('success', 'Задачи сбора постов поставлены в очередь. Запустите воркер: php artisan queue:work --queue=vk,default');
    }

    /**
     * Показать, куда уходит запрос к VK API и какой приходит ответ (без постановки в очередь).
     */
    public function debugFetch(Request $request)
    {
        $request->validate([
            'vk_tracking_ids' => 'required|array',
            'vk_tracking_ids.*' => 'integer|exists:vk_trackings,id',
            'count' => 'sometimes|integer|min:1|max:100',
        ]);

        $settings = self::vkSettings();
        if (empty($settings->vk_access_token)) {
            return redirect()
                ->route('admin.vk-posts.index')
                ->with('error', 'Сначала получите VK-токен: страница «Токен» (/admin/vk) → «Войти через OAuth».');
        }

        $count = (int) $request->input('count', 10);
        $count = min(max(1, $count), 100);
        $apiUrl = config('services.vk.api_url', 'https://api.vk.com/method');
        $apiVersion = config('services.vk.api_version', '5.131');
        $token = $settings->vk_access_token;

        $debugLog = [];
        foreach ($request->input('vk_tracking_ids') as $vkTrackingId) {
            $tracking = VkTracking::find($vkTrackingId);
            if (!$tracking || !$tracking->group_id) {
                $debugLog[] = [
                    'tracking_name' => $tracking->name ?? 'id=' . $vkTrackingId,
                    'error' => 'Группа не найдена или нет group_id',
                    'request_url' => null,
                    'request_params' => null,
                    'response_status' => null,
                    'response_body' => null,
                ];
                continue;
            }

            $params = [
                'owner_id' => -(int) $tracking->group_id,
                'count' => $count,
                'extended' => 1,
                'access_token' => $token,
                'v' => $apiVersion,
            ];
            $fullUrl = $apiUrl . '/wall.get?' . http_build_query($params);
            $urlForDisplay = $apiUrl . '/wall.get?' . http_build_query(array_merge(
                $params,
                ['access_token' => '(скрыт)']
            ));
            $paramsForDisplay = $params;
            $paramsForDisplay['access_token'] = substr($token, 0, 8) . '…' . substr($token, -4);

            $response = Http::timeout(30)->get($apiUrl . '/wall.get', $params);
            $status = $response->status();
            $body = $response->json();
            if ($body === null) {
                $body = $response->body();
            }

            $debugLog[] = [
                'tracking_name' => $tracking->name . ' (' . $tracking->screen_name . ')',
                'tracking_id' => $tracking->id,
                'group_id' => $tracking->group_id,
                'owner_id' => $params['owner_id'],
                'error' => null,
                'request_url' => $urlForDisplay,
                'request_params' => $paramsForDisplay,
                'response_status' => $status,
                'response_body' => $body,
                'response_items_count' => isset($body['response']['items']) ? count($body['response']['items']) : null,
                'response_error' => $body['error'] ?? null,
            ];
        }

        return redirect()
            ->route('admin.vk-posts.index')
            ->with('debug_log', $debugLog);
    }
}
