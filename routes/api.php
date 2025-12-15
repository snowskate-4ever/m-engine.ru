<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiTaskController;
use App\Http\Controllers\ApiEventController;
use App\Http\Controllers\ApiResourceController;
use App\Http\Controllers\ApiTypeController;

Route::post('/login', [ApiAuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [ApiTaskController::class, 'get_tasks'])->name('api_tasks');
    Route::post('/tasks', [ApiTaskController::class, 'create_tasks'])->name('api_create_tasks');
    Route::get('/tasks/{id}', [ApiTaskController::class, 'get_task'])->name('api_task');
    Route::put('/tasks/{id}', [ApiTaskController::class, 'edit_task'])->name('api_edit_task');
    Route::delete('/tasks/{id}', [ApiTaskController::class, 'delete_task'])->name('api_delete_task');

    Route::get('/events', [ApiEventController::class, 'get_events'])->name('api_events');
    Route::post('/events', [ApiEventController::class, 'create_event'])->name('api_create_event');
    Route::get('/events/{id}', [ApiEventController::class, 'get_event'])->name('api_event');
    Route::put('/events/{id}', [ApiEventController::class, 'edit_event'])->name('api_edit_event_event');
    Route::delete('/events/{id}', [ApiEventController::class, 'delete_event'])->name('api_delete_event');

    Route::get('/resources', [ApiResourceController::class, 'get_resources'])->name('api_resources');
    Route::post('/resources', [ApiResourceController::class, 'create_resource'])->name('api_create_resource');
    Route::get('/resources/{id}', [ApiResourceController::class, 'get_resource'])->name('api_resource');
    Route::put('/resources/{id}', [ApiResourceController::class, 'edit_resource'])->name('api_edit_resource');
    Route::delete('/resources/{id}', [ApiResourceController::class, 'delete_resource'])->name('api_delete_resource');

    Route::get('/types', [ApiTypeController::class, 'get_types'])->name('api_types');
    Route::post('/types', [ApiTypeController::class, 'create_type'])->name('api_create_type');
    Route::get('/types/{id}', [ApiTypeController::class, 'get_type'])->name('api_type');
    Route::put('/types/{id}', [ApiTypeController::class, 'edit_type'])->name('api_edit_type');
    Route::delete('/types/{id}', [ApiTypeController::class, 'delete_type'])->name('api_delete_type');
});
