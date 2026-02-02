<?php

namespace App\Http\Controllers\api;

use App\Models\VkPost;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\api\VkApiService;

class VkApiController extends Controller
{
    /**
     * Получить информацию о пользователях
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request)
    {
        return VkApiService::getUsers($request);
    }

    /**
     * Получить информацию о группах
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroups(Request $request)
    {
        return VkApiService::getGroups($request);
    }

    /**
     * Опубликовать запись на стене
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function wallPost(Request $request)
    {
        return VkApiService::wallPost($request);
    }

    /**
     * Тест доступности VK API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection()
    {
        return VkApiService::testConnection();
    }

    /**
     * Получить группы пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserGroups(Request $request)
    {
        return VkApiService::getUserGroups($request);
    }

    /**
     * Выполнить произвольный метод VK API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeMethod(Request $request)
    {
        return VkApiService::executeMethod($request);
    }

    /**
     * Выдать по 100 постов из vk_posts авторизованному пользователю.
     * GET /api/vk/posts?page=1 — по 100 постов на страницу, сортировка по дате публикации (новые первые).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosts(Request $request)
    {
        $posts = VkPost::query()
            ->with(['vkTracking:id,name,screen_name,group_id', 'media:id,vk_post_id,type,vk_url,path,sort_order'])
            ->orderBy('posted_at', 'desc')
            ->paginate(100);

        $items = collect($posts->items())->map(function (VkPost $post) {
            $arr = $post->only(['id', 'vk_tracking_id', 'vk_post_id', 'from_id', 'signer_id', 'text', 'posted_at', 'processed_at']);
            $arr['posted_at'] = $post->posted_at?->toIso8601String();
            $arr['processed_at'] = $post->processed_at?->toIso8601String();
            $arr['vk_tracking'] = $post->vkTracking ? $post->vkTracking->only(['id', 'name', 'screen_name', 'group_id']) : null;
            $arr['media'] = $post->media->map(fn ($m) => $m->only(['id', 'type', 'vk_url', 'path', 'sort_order']))->all();
            return $arr;
        })->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }
}

