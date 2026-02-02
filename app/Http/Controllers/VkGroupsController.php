<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VkSetting;
use App\Models\VkTracking;
use App\Services\api\VkApiService;
use Illuminate\Http\Request;

class VkGroupsController extends Controller
{
    /**
     * Страница «Группы пользователя» — список групп из VK (groups.get).
     * Токен из vk_settings. У каждой группы кнопка «Добавить в отслеживаемые».
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

        $service = new VkApiService();
        $result = $service->getUsersGroupsList($token, 1000, 0);

        if ($result['error']) {
            return view('vk_groups', [
                'groups' => [],
                'trackedIds' => [],
                'error' => $result['error_msg'] ?? 'Ошибка при загрузке групп.',
            ]);
        }

        $items = $result['response']['items'] ?? [];
        $trackedIds = VkTracking::query()->pluck('group_id')->all();

        return view('vk_groups', [
            'groups' => $items,
            'trackedIds' => $trackedIds,
            'error' => null,
        ]);
    }

    /**
     * Добавить группу в отслеживаемые (vk_trackings).
     */
    public function addToTracking(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'screen_name' => 'required|string|max:255',
        ], [
            'group_id.required' => 'Укажите ID группы.',
            'name.required' => 'Укажите название группы.',
            'screen_name.required' => 'Укажите короткий адрес группы.',
        ]);

        $groupId = (int) $request->input('group_id');
        $name = $request->input('name');
        $screenName = ltrim((string) $request->input('screen_name'), '@');

        $exists = VkTracking::where('group_id', $groupId)->exists();
        if ($exists) {
            return redirect()
                ->route('admin.vk-groups.index')
                ->with('info', "Группа «{$name}» уже в отслеживаемых.");
        }

        VkTracking::create([
            'name' => $name,
            'screen_name' => $screenName,
            'group_id' => $groupId,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.vk-groups.index')
            ->with('success', "Группа «{$name}» добавлена в отслеживаемые.");
    }
}
