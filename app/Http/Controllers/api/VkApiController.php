<?php

namespace App\Http\Controllers\api;

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
}

