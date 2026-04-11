<?php

use App\Http\Controllers\api\AiServerModelController;
use App\Http\Controllers\api\AiSubscriptionController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Controllers\api\ApiEventController;
use App\Http\Controllers\api\ApiFeaturesController;
use App\Http\Controllers\api\ApiResourceController;
use App\Http\Controllers\api\ApiTaskController;
use App\Http\Controllers\api\ApiTypeController;
use App\Http\Controllers\api\ApiUserController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\BillingWebhookController;
use App\Http\Controllers\api\DevicePushTokenController;
use App\Http\Controllers\api\MessengerAttachmentDownloadController;
use App\Http\Controllers\api\MessengerController;
use App\Http\Controllers\api\MessengerConversationSkillController;
use App\Http\Controllers\api\MusicActorContextController;
use App\Http\Controllers\api\MusicProfileMembershipController;
use App\Http\Controllers\api\UserAiConnectionController;
use App\Http\Controllers\api\UserAiPreferenceController;
use App\Http\Controllers\api\UserAiScheduledItemController;
use App\Http\Controllers\api\VkApiController;
use Illuminate\Support\Facades\Route;

Route::get('/features', ApiFeaturesController::class)->name('api.features');

// Устаревший маршрут для обратной совместимости
Route::post('/login', [ApiAuthController::class, 'login'])->name('login');

// Новые маршруты мультиканальной авторизации
Route::middleware(['detect.channel'])->group(function () {
    // GET на /auth — явная ошибка (часто из-за редиректа HTTP→HTTPS, когда POST превращается в GET)
    Route::get('/auth', fn () => response()->json([
        'error' => 'Method Not Allowed',
        'message' => 'Используйте POST. Убедитесь, что запрос идёт по HTTPS: https://m-engine.ru/api/auth',
        'allowed_method' => 'POST',
    ], 405)->header('Allow', 'POST'));

    // Основной endpoint для авторизации из любого канала
    Route::post('/auth', [AuthController::class, 'authenticate'])
        ->middleware(['throttle:5,1']) // 5 попыток в минуту
        ->name('auth.authenticate');

    // Проверка статуса попытки авторизации
    Route::get('/auth/status/{attemptId}', [AuthController::class, 'checkStatus'])
        ->middleware(['throttle:30,1']) // 30 проверок в минуту
        ->name('auth.status');
});

// Webhook для N8N (с верификацией подписи)
Route::post('/webhooks/n8n/auth', [AuthController::class, 'n8nWebhook'])
    ->middleware(['throttle:60,1']) // 60 попыток в минуту для N8N
    ->name('webhooks.n8n.auth');

Route::post('/webhooks/billing/stub', [BillingWebhookController::class, 'stub'])
    ->middleware(['throttle:30,1'])
    ->name('webhooks.billing.stub');

Route::post('/webhooks/billing/yookassa', [BillingWebhookController::class, 'yookassa'])
    ->middleware(['throttle:60,1'])
    ->name('webhooks.billing.yookassa');

Route::get('/messenger/attachments/{attachment}/download', MessengerAttachmentDownloadController::class)
    ->middleware(['signed', 'throttle:120,1'])
    ->name('api.messenger.attachment.download');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/devices/push-token', [DevicePushTokenController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('api_devices_push_token');

    Route::get('/users', [ApiUserController::class, 'get_users'])->name('api_users');
    Route::get('/users/{id}', [ApiUserController::class, 'get_user'])->name('api_user');

    Route::get('/tasks', [ApiTaskController::class, 'get_tasks'])->name('api_tasks');
    Route::post('/tasks', [ApiTaskController::class, 'create_tasks'])->name('api_create_tasks');
    Route::get('/tasks/{id}', [ApiTaskController::class, 'get_task'])->name('api_task');
    Route::put('/tasks/{id}', [ApiTaskController::class, 'edit_task'])->name('api_edit_task');
    Route::delete('/tasks/{id}', [ApiTaskController::class, 'delete_task'])->name('api_delete_task');

    Route::get('/events', [ApiEventController::class, 'get_events'])->name('api_events');
    Route::post('/events', [ApiEventController::class, 'create_event'])->name('api_create_event');
    Route::get('/events/{id}', [ApiEventController::class, 'get_event'])->name('api_event');
    Route::put('/events/{id}', [ApiEventController::class, 'edit_event'])->name('api_edit_event_event');
    Route::post('/events/{id}/confirm-matching-booking', [ApiEventController::class, 'confirm_matching_booking'])
        ->name('api_events_confirm_matching_booking');
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

    Route::prefix('messenger')->group(function () {
        Route::get('/preferences', [MessengerController::class, 'preferencesShow'])->name('api_messenger_preferences_show');
        Route::patch('/preferences', [MessengerController::class, 'preferencesUpdate'])->name('api_messenger_preferences_update');

        Route::get('/conversations', [MessengerController::class, 'index'])->name('api_messenger_conversations_index');
        Route::post('/conversations', [MessengerController::class, 'store'])->name('api_messenger_conversations_store');
        Route::get('/conversations/{conversation}', [MessengerController::class, 'show'])->name('api_messenger_conversations_show');
        Route::patch('/conversations/{conversation}', [MessengerController::class, 'update'])->name('api_messenger_conversations_update');
        Route::get('/conversations/{conversation}/messages', [MessengerController::class, 'messagesIndex'])->name('api_messenger_messages_index');
        Route::post('/conversations/{conversation}/messages', [MessengerController::class, 'messagesStore'])->name('api_messenger_messages_store');
        Route::post('/conversations/{conversation}/read', [MessengerController::class, 'read'])->name('api_messenger_read');
        Route::post('/conversations/{conversation}/presence', [MessengerController::class, 'presence'])->name('api_messenger_presence');
        Route::patch('/conversations/{conversation}/notifications', [MessengerController::class, 'updateNotifications'])->name('api_messenger_notifications');

        Route::middleware('ai.enabled')->group(function () {
            Route::get('/conversations/{conversation}/skills', [MessengerConversationSkillController::class, 'index'])->name('api_messenger_skills_index');
            Route::post('/conversations/{conversation}/skills', [MessengerConversationSkillController::class, 'store'])->name('api_messenger_skills_store');
            Route::patch('/conversations/{conversation}/skills/{skill}', [MessengerConversationSkillController::class, 'update'])->name('api_messenger_skills_update');
            Route::delete('/conversations/{conversation}/skills/{skill}', [MessengerConversationSkillController::class, 'destroy'])->name('api_messenger_skills_destroy');
        });
    });

    Route::prefix('music')->group(function () {
        Route::get('/actor-context', [MusicActorContextController::class, 'index'])->name('api_music_actor_context_index');
        Route::patch('/actor-context', [MusicActorContextController::class, 'update'])->name('api_music_actor_context_update');

        Route::get('/memberships', [MusicProfileMembershipController::class, 'index'])->name('api_music_memberships_index');
        Route::post('/memberships', [MusicProfileMembershipController::class, 'store'])->name('api_music_memberships_store');
        Route::patch('/memberships/{membership}/respond', [MusicProfileMembershipController::class, 'respond'])->name('api_music_memberships_respond');
        Route::patch('/memberships/{membership}/revoke', [MusicProfileMembershipController::class, 'revoke'])->name('api_music_memberships_revoke');
    });

    Route::middleware('ai.enabled')->prefix('ai')->group(function () {
        Route::get('/subscription', [AiSubscriptionController::class, 'show'])->name('api_ai_subscription_show');
        Route::patch('/preferences', [UserAiPreferenceController::class, 'update'])->name('api_ai_preferences_update');
        Route::get('/server-models', [AiServerModelController::class, 'index'])->name('api_ai_server_models_index');
        Route::get('/connections', [UserAiConnectionController::class, 'index'])->name('api_ai_connections_index');
        Route::post('/connections', [UserAiConnectionController::class, 'store'])->name('api_ai_connections_store');
        Route::patch('/connections/{connection}', [UserAiConnectionController::class, 'update'])->name('api_ai_connections_update');
        Route::delete('/connections/{connection}', [UserAiConnectionController::class, 'destroy'])->name('api_ai_connections_destroy');

        Route::get('/scheduled-items', [UserAiScheduledItemController::class, 'index'])->name('api_ai_scheduled_items_index');
        Route::delete('/scheduled-items/{item}', [UserAiScheduledItemController::class, 'destroy'])->name('api_ai_scheduled_items_destroy');
    });

    // VK API routes
    Route::prefix('vk')->group(function () {
        Route::get('/test', [VkApiController::class, 'testConnection'])->name('api_vk_test');
        Route::get('/users', [VkApiController::class, 'getUsers'])->name('api_vk_users');
        Route::get('/groups', [VkApiController::class, 'getGroups'])->name('api_vk_groups');
        Route::get('/user-groups', [VkApiController::class, 'getUserGroups'])->name('api_vk_user_groups');
        Route::get('/posts', [VkApiController::class, 'getPosts'])->name('api_vk_posts');
        Route::post('/wall/post', [VkApiController::class, 'wallPost'])->name('api_vk_wall_post');
        Route::post('/execute', [VkApiController::class, 'executeMethod'])->name('api_vk_execute');
    });
});
