<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Livewire\Account\Profiles;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::group(['prefix' => '/account'], function () {
        Route::group(['prefix' => '/profiles'], function () {
            Route::get('/', [App\Http\Controllers\ProfileController::class, 'get_profiles'])->name('account.profiles');
        });
    });

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
    // ==========
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
