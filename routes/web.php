<?php

use App\Http\Controllers\KanbanCardAttachmentController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\KanbanLogsController;
use App\Livewire\Messenger\MessengerNotificationSettings;
use App\Livewire\Messenger\MessengerWorkspace;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $menuItems = \App\Services\MenuService::getMenuItems();

    return view('welcome', ['menuItems' => $menuItems]);
})->name('home');

Route::prefix('musicians')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'musician'])
        ->name('public.musicians.show');
});
Route::prefix('teachers')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'teacher'])
        ->name('public.teachers.show');
});
Route::prefix('performers')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'performer'])
        ->name('public.performers.show');
});
Route::prefix('studios')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'studio'])
        ->name('public.studios.show');
});
Route::prefix('rehearsals')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'rehearsal'])
        ->name('public.rehearsals.show');
});
Route::prefix('schools')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Public\PublicMusicProfileController::class, 'school'])
        ->name('public.schools.show');
});

// VK: страница /admin/vk (токен, тесты), меню общее с /admin/vk-posts
Route::get('/admin/vk', [App\Http\Controllers\TestController::class, 'openApiIndex'])->name('admin.vk');
Route::get('/admin/token', [App\Http\Controllers\TestController::class, 'openApiIndex'])->name('admin.vk.token');
Route::post('/admin/vktest/session', [App\Http\Controllers\TestController::class, 'saveVkOpenApiSession'])->name('admin.vktest.session');
Route::redirect('/admin/vktest', '/admin/vk');
Route::redirect('/vktest', '/admin/vk');
Route::get('/vk-oauth-start', [App\Http\Controllers\TestController::class, 'startVkOAuth'])->name('admin.test.vk-oauth-start');
Route::post('/vk-groups', [App\Http\Controllers\TestController::class, 'getVkGroups'])->name('admin.test.vk-groups');
Route::post('/vk-chats', [App\Http\Controllers\TestController::class, 'getVkChats'])->name('admin.test.vk-chats');
Route::post('/vk-token', [App\Http\Controllers\TestController::class, 'saveVkToken'])->name('admin.test.vk-token');
Route::get('/vk-oauth', [App\Http\Controllers\TestController::class, 'handleVkOAuth'])
    ->name('admin.test.vk-oauth');

// Сбор постов из групп VK (очереди) и лента пользователя
Route::middleware('auth')->group(function () {
    Route::get('/admin/vk-posts', [App\Http\Controllers\VkPostsController::class, 'index'])->name('admin.vk-posts.index');
    Route::get('/admin/vk-posts/log', [App\Http\Controllers\VkPostsController::class, 'log'])->name('admin.vk-posts.log');
    Route::post('/admin/vk-posts/fetch', [App\Http\Controllers\VkPostsController::class, 'fetch'])->name('admin.vk-posts.fetch');
    Route::post('/admin/vk-posts/debug', [App\Http\Controllers\VkPostsController::class, 'debugFetch'])->name('admin.vk-posts.debug');
    Route::get('/admin/vk-feed', [App\Http\Controllers\VkFeedController::class, 'index'])->name('admin.vk-feed.index');
    Route::get('/admin/vk-newsfeed', [App\Http\Controllers\VkFeedController::class, 'newsfeed'])->name('admin.vk-newsfeed.index');
    Route::get('/admin/vk-groups', [App\Http\Controllers\VkGroupsController::class, 'index'])->name('admin.vk-groups.index');
    Route::post('/admin/vk-groups/add-tracking', [App\Http\Controllers\VkGroupsController::class, 'addToTracking'])->name('admin.vk-groups.add-tracking');
});

// Заглушки для неавторизованных пользователей
Route::get('/resources/type/{type_id}', function ($type_id) {
    // Если пользователь авторизован, редиректим на правильный маршрут
    if (Auth::check()) {
        return redirect()->route('resources.by_type', ['type_id' => $type_id]);
    }
    $type = \App\Models\Type::findOrFail($type_id);

    return view('resources.stub', ['type' => $type]);
})->name('resources.stub');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'dashboard'])
        ->name('dashboard');

    Route::group(['prefix' => '/resources'], function () {
        Route::get('/', [App\Http\Controllers\ResourceController::class, 'get_resources'])->name('resources');
        Route::get('/type/{type_id}', [App\Http\Controllers\ResourceController::class, 'get_resources_by_type'])->name('resources.by_type');
        Route::get('/create/{type}', [App\Http\Controllers\ResourceController::class, 'create_resources'])->name('resources.create');
    });
    Route::group(['prefix' => '/events'], function () {
        Route::get('/', [App\Http\Controllers\EventController::class, 'get_events'])->name('events');
        Route::get('/create', [App\Http\Controllers\EventController::class, 'create_event'])->name('create_event');
        Route::get('/{id}', [App\Http\Controllers\EventController::class, 'get_event'])->name('get_event');
        Route::put('/{id}', [App\Http\Controllers\EventController::class, 'edit_event'])->name('edit_event');
        Route::delete('/{id}', [App\Http\Controllers\EventController::class, 'delete_event'])->name('delete_event');
    });

    Route::prefix('messenger')->group(function () {
        Route::get('/', MessengerWorkspace::class)->name('messenger.index');
        Route::get('/settings/notifications', MessengerNotificationSettings::class)->name('messenger.settings.notifications');
        Route::get('/{conversation}', MessengerWorkspace::class)->name('messenger.show');
    });

    Route::view('calendar', 'calendar')->name('calendar');

    Route::view('music/musician', 'music.musician')->name('music.musician');
    Route::view('music/teacher', 'music.teacher')->name('music.teacher');
    Route::view('music/discover', 'music.discover')->name('music.discover');

    Route::view('music/performers', 'music.performers-index')->name('music.performers.index');
    Route::view('music/performers/create', 'music.performer-edit', ['recordId' => null])->name('music.performers.create');
    Route::get('music/performers/{peformer}/edit', function (Peformer $peformer) {
        Gate::authorize('update', $peformer);

        return view('music.performer-edit', ['recordId' => $peformer->id]);
    })->name('music.performers.edit');

    Route::view('music/studios', 'music.venue-index', ['kind' => 'studio'])->name('music.studios.index');
    Route::view('music/studios/create', 'music.venue-edit', ['kind' => 'studio', 'recordId' => null])->name('music.studios.create');
    Route::get('music/studios/{studio}/edit', function (Studio $studio) {
        Gate::authorize('update', $studio);

        return view('music.venue-edit', ['kind' => 'studio', 'recordId' => $studio->id]);
    })->name('music.studios.edit');

    Route::view('music/rehearsals', 'music.venue-index', ['kind' => 'rehearsal'])->name('music.rehearsals.index');
    Route::view('music/rehearsals/create', 'music.venue-edit', ['kind' => 'rehearsal', 'recordId' => null])->name('music.rehearsals.create');
    Route::get('music/rehearsals/{rehersal}/edit', function (Rehersal $rehersal) {
        Gate::authorize('update', $rehersal);

        return view('music.venue-edit', ['kind' => 'rehearsal', 'recordId' => $rehersal->id]);
    })->name('music.rehearsals.edit');

    Route::view('music/schools', 'music.venue-index', ['kind' => 'school'])->name('music.schools.index');
    Route::view('music/schools/create', 'music.venue-edit', ['kind' => 'school', 'recordId' => null])->name('music.schools.create');
    Route::get('music/schools/{school}/edit', function (School $school) {
        Gate::authorize('update', $school);

        return view('music.venue-edit', ['kind' => 'school', 'recordId' => $school->id]);
    })->name('music.schools.edit');

    Route::get('kanban', [KanbanController::class, 'index'])->name('kanban');
    Route::get('kanban/logs', [KanbanLogsController::class, 'index'])->name('kanban.logs');
    Route::post('kanban/boards/reorder', [KanbanController::class, 'reorderBoards'])->name('kanban.boards.reorder');
    Route::post('kanban/boards/reorder-shared', [KanbanController::class, 'reorderSharedBoards'])->name('kanban.boards.reorder-shared');
    Route::post('kanban/boards/{board}/sync', [KanbanController::class, 'sync'])->name('kanban.sync');
    Route::get('kanban/attachments/{attachment}/download', [KanbanCardAttachmentController::class, 'download'])
        ->name('kanban.attachments.download');

    Route::get('/settings/profile', [App\Http\Controllers\SettingsController::class, 'profile'])->name('settings.profile.edit');
    Route::get('/settings/password', [App\Http\Controllers\SettingsController::class, 'password'])->name('settings.password.edit');
    Route::get('/settings/appearance', [App\Http\Controllers\SettingsController::class, 'appearance'])->name('settings.appearance.edit');
    Route::get('/settings/two-factor', [App\Http\Controllers\SettingsController::class, 'two_factor'])
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('settings.two-factor.show');

    // Volt::route('settings/password', 'settings.password')->name('settings.password.edit');
    // Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance.edit');

    // Volt::route('settings/two-factor', 'settings.two-factor')
    //     ->middleware(
    //         when(
    //             Features::canManageTwoFactorAuthentication()
    //                 && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
    //             ['password.confirm'],
    //             [],
    //         ),
    //     )
    //     ->name('settings.two-factor.show');
});
