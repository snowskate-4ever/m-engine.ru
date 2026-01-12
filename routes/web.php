<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Livewire\Account\Profiles;
use App\Livewire\Settings\UpdateProfileInformation;
use MoonShine\Laravel\Http\Middleware\Authenticate as MoonShineAuthenticate;

Route::get('/', function () {
    $menuItems = \App\Services\MenuService::getMenuItems();
    return view('welcome', ['menuItems' => $menuItems]);
})->name('home');

// Маршруты для тестов (только для авторизованных пользователей MoonShine)
Route::middleware(MoonShineAuthenticate::class)->group(function () {
    Route::get('/admin/test', [App\Http\Controllers\TestController::class, 'index'])->name('admin.test');
    Route::post('/admin/test/vk-groups', [App\Http\Controllers\TestController::class, 'getVkGroups'])->name('admin.test.vk-groups');
    Route::post('/admin/test/vk-token', [App\Http\Controllers\TestController::class, 'saveVkToken'])->name('admin.test.vk-token');
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
