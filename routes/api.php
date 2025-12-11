<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiTaskController;

Route::post('/login', [ApiAuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [ApiTaskController::class, 'get_tasks'])->name('tasks');
    Route::post('/tasks', [ApiTaskController::class, 'create_tasks'])->name('create_tasks');
    Route::get('/tasks/{id}', [ApiTaskController::class, 'get_task'])->name('task');
    Route::put('/tasks/{id}', [ApiTaskController::class, 'edit_task'])->name('edit_task');
    Route::delete('/tasks/{id}', [ApiTaskController::class, 'delete_task'])->name('delete_task');
});
