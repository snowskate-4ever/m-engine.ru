<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Livewire\Account\Profiles;
use App\Livewire\Settings\UpdateProfileInformation;
;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'dashboard'])
    ->name('dashboard');

    Route::group(['prefix' => '/resources'], function () {
        Route::get('/', [App\Http\Controllers\ResourceController::class, 'get_resources'])->name('resources');
    });
    Route::group(['prefix' => '/events'], function () {
        Route::get('/', [App\Http\Controllers\EventController::class, 'get_events'])->name('events');
        Route::post('/', [App\Http\Controllers\EventController::class, 'create_events'])->name('create_events');
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
