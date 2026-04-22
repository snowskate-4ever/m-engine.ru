<?php

use App\Http\Controllers\api\AiExpansionController;
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
use App\Http\Controllers\api\BlogCommentController;
use App\Http\Controllers\api\BlogPostController;
use App\Http\Controllers\api\BlogSubscriptionController;
use App\Http\Controllers\api\ContractApiController;
use App\Http\Controllers\api\DevicePushTokenController;
use App\Http\Controllers\api\GamificationController;
use App\Http\Controllers\api\Integration\IntegrationAnalyticsController;
use App\Http\Controllers\api\Integration\IntegrationMeController;
use App\Http\Controllers\api\Integration\IntegrationTokenController;
use App\Http\Controllers\api\Integration\IntegrationWebhookController;
use App\Http\Controllers\api\MessengerAttachmentDownloadController;
use App\Http\Controllers\api\MessengerController;
use App\Http\Controllers\api\MessengerConversationSkillController;
use App\Http\Controllers\api\MobileSyncController;
use App\Http\Controllers\api\MusicActorContextController;
use App\Http\Controllers\api\MusicActivityFeedController;
use App\Http\Controllers\api\MusicLegalDocumentController;
use App\Http\Controllers\api\MusicCalendarSyncController;
use App\Http\Controllers\api\MusicProfileMembershipController;
use App\Http\Controllers\api\MusicProfilesController;
use App\Http\Controllers\api\MusicReviewController;
use App\Http\Controllers\api\MusicResourceCatalogController;
use App\Http\Controllers\api\MusicSearchRequestController;
use App\Http\Controllers\api\PlatformPaymentController;
use App\Http\Controllers\api\PublicBlogController;
use App\Http\Controllers\api\PublicLegalDocumentController;
use App\Http\Controllers\api\UserAiConnectionController;
use App\Http\Controllers\api\UserAiPreferenceController;
use App\Http\Controllers\api\UserAiScheduledItemController;
use App\Http\Controllers\api\VkApiController;
use Illuminate\Support\Facades\Route;

Route::get('/features', ApiFeaturesController::class)->name('api.features');

Route::get('/public/blog-posts', [PublicBlogController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('api_public_blog_posts_index');
Route::get('/public/blog-posts/detail', [PublicBlogController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('api_public_blog_posts_show');
Route::get('/public/{entityType}/{slug}/legal-documents', [PublicLegalDocumentController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('api_public_legal_documents_index');

Route::prefix('integration/v1')->middleware(['integration.api', 'integration.audit', 'throttle:integration'])->group(function (): void {
    Route::get('/me', IntegrationMeController::class)->name('api_integration_v1_me');
    Route::get('/analytics/bookings/summary', [IntegrationAnalyticsController::class, 'bookingsSummary'])
        ->name('api_integration_v1_analytics_bookings_summary');
    Route::get('/analytics/bookings/by-month', [IntegrationAnalyticsController::class, 'bookingsByMonth'])
        ->name('api_integration_v1_analytics_bookings_by_month');
    Route::get('/analytics/bookings/export.csv', [IntegrationAnalyticsController::class, 'exportBookingsCsv'])
        ->name('api_integration_v1_analytics_bookings_export_csv');
});

Route::prefix('integration/v2')->middleware(['integration.api', 'integration.audit', 'throttle:integration'])->group(function (): void {
    Route::get('/me', IntegrationMeController::class)
        ->middleware('integration.ability:me:read')
        ->name('api_integration_v2_me');
    Route::get('/analytics/bookings/summary', [IntegrationAnalyticsController::class, 'bookingsSummary'])
        ->middleware('integration.ability:analytics:read')
        ->name('api_integration_v2_analytics_bookings_summary');
    Route::get('/analytics/bookings/by-month', [IntegrationAnalyticsController::class, 'bookingsByMonth'])
        ->middleware('integration.ability:analytics:read')
        ->name('api_integration_v2_analytics_bookings_by_month');
    Route::get('/analytics/bookings/export.csv', [IntegrationAnalyticsController::class, 'exportBookingsCsv'])
        ->middleware('integration.ability:analytics:export')
        ->name('api_integration_v2_analytics_bookings_export_csv');
});

Route::post('/integration/v2/webhooks/events', [IntegrationWebhookController::class, 'store'])
    ->middleware('throttle:integration-webhook')
    ->name('api_integration_v2_webhooks_events_store');

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
        Route::get('/profiles', [MusicProfilesController::class, 'index'])->name('api_music_profiles_index');
        Route::patch('/profiles', [MusicProfilesController::class, 'update'])->name('api_music_profiles_update');

        Route::get('/memberships', [MusicProfileMembershipController::class, 'index'])->name('api_music_memberships_index');
        Route::post('/memberships', [MusicProfileMembershipController::class, 'store'])->name('api_music_memberships_store');
        Route::patch('/memberships/{membership}/respond', [MusicProfileMembershipController::class, 'respond'])->name('api_music_memberships_respond');
        Route::patch('/memberships/{membership}/revoke', [MusicProfileMembershipController::class, 'revoke'])->name('api_music_memberships_revoke');

        Route::get('/resources/catalog', [MusicResourceCatalogController::class, 'catalog'])->name('api_music_resources_catalog');
        Route::get('/calendar-sync/feed', [MusicCalendarSyncController::class, 'feed'])->name('api_music_calendar_sync_feed');
        Route::get('/calendar-sync/connectors', [MusicCalendarSyncController::class, 'connectors'])->name('api_music_calendar_sync_connectors');

        Route::get('/search-requests', [MusicSearchRequestController::class, 'index'])->name('api_music_search_requests_index');
        Route::get('/search-requests-feed', [MusicSearchRequestController::class, 'feed'])->name('api_music_search_requests_feed');
        Route::post('/search-requests', [MusicSearchRequestController::class, 'store'])->name('api_music_search_requests_store');
        Route::post('/search-requests/{searchRequest}/cancel', [MusicSearchRequestController::class, 'cancel'])->name('api_music_search_requests_cancel');
        Route::post('/search-requests/{searchRequest}/reopen', [MusicSearchRequestController::class, 'reopen'])->name('api_music_search_requests_reopen');
        Route::post('/search-requests/{searchRequest}/responses', [MusicSearchRequestController::class, 'respond'])->name('api_music_search_requests_respond');
        Route::get('/search-requests/{searchRequest}/responses', [MusicSearchRequestController::class, 'responses'])->name('api_music_search_requests_responses');
        Route::get('/activity-feed', [MusicActivityFeedController::class, 'index'])->name('api_music_activity_feed');
        Route::post('/reviews/verified', [MusicReviewController::class, 'storeVerified'])->name('api_music_reviews_verified_store');
        Route::get('/legal-documents/{legalDocument}', [MusicLegalDocumentController::class, 'show'])->name('api_music_legal_documents_show');
        Route::patch('/legal-documents/{legalDocument}', [MusicLegalDocumentController::class, 'update'])->name('api_music_legal_documents_update');
        Route::post('/legal-documents/{legalDocument}/versions', [MusicLegalDocumentController::class, 'createVersion'])->name('api_music_legal_documents_versions_store');
        Route::post('/legal-documents/{legalDocument}/submit', [MusicLegalDocumentController::class, 'submit'])->name('api_music_legal_documents_submit');
        Route::post('/legal-documents/{legalDocument}/archive', [MusicLegalDocumentController::class, 'archive'])->name('api_music_legal_documents_archive');
        Route::post('/legal-documents/{legalDocument}/approve', [MusicLegalDocumentController::class, 'approve'])->name('api_music_legal_documents_approve');
        Route::post('/legal-documents/{legalDocument}/reject', [MusicLegalDocumentController::class, 'reject'])->name('api_music_legal_documents_reject');
        Route::get('/legal-documents/{ownerType}/{ownerId}', [MusicLegalDocumentController::class, 'index'])
            ->whereNumber('ownerId')
            ->name('api_music_legal_documents_index');
        Route::post('/legal-documents/{ownerType}/{ownerId}', [MusicLegalDocumentController::class, 'store'])
            ->whereNumber('ownerId')
            ->name('api_music_legal_documents_store');
    });

    Route::post('/platform/bookings/{booking}/payments', [PlatformPaymentController::class, 'storeForBooking'])
        ->middleware('throttle:30,1')
        ->name('api_platform_booking_payments_store');
    Route::post('/platform/payments/{platformPayment}/capture-stub', [PlatformPaymentController::class, 'captureStub'])
        ->middleware('throttle:30,1')
        ->name('api_platform_payments_capture_stub');
    Route::post('/platform/payments/{platformPayment}/refund', [PlatformPaymentController::class, 'refund'])
        ->middleware('throttle:30,1')
        ->name('api_platform_payments_refund');

    Route::post('/contracts/generate', [ContractApiController::class, 'generate'])
        ->middleware('throttle:30,1')
        ->name('api_contracts_generate');
    Route::post('/contracts/{contract}/accept', [ContractApiController::class, 'accept'])
        ->middleware('throttle:60,1')
        ->name('api_contracts_accept');

    Route::get('/blog-posts', [BlogPostController::class, 'index'])->name('api_blog_posts_index');
    Route::post('/blog-posts', [BlogPostController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api_blog_posts_store');
    Route::patch('/blog-posts/{blogPost}', [BlogPostController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api_blog_posts_update');

    Route::post('/blog-subscriptions', [BlogSubscriptionController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('api_blog_subscriptions_store');
    Route::delete('/blog-subscriptions', [BlogSubscriptionController::class, 'destroy'])
        ->middleware('throttle:60,1')
        ->name('api_blog_subscriptions_destroy');

    Route::post('/blog-posts/{blogPost}/comments', [BlogCommentController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('api_blog_comments_store');

    Route::get('/integration/tokens', [IntegrationTokenController::class, 'index'])
        ->middleware('throttle:30,1')
        ->name('api_integration_tokens_index');
    Route::post('/integration/tokens', [IntegrationTokenController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('api_integration_tokens_store');
    Route::delete('/integration/tokens/{integrationApiToken}', [IntegrationTokenController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('api_integration_tokens_destroy');
    Route::post('/integration/tokens/{integrationApiToken}/rotate', [IntegrationTokenController::class, 'rotate'])
        ->middleware('throttle:20,1')
        ->name('api_integration_tokens_rotate');

    Route::get('/gamification/xp', [GamificationController::class, 'meXp'])->name('api_gamification_xp');
    Route::get('/gamification/leaderboard', [GamificationController::class, 'leaderboard'])
        ->middleware('throttle:60,1')
        ->name('api_gamification_leaderboard');

    Route::post('/ai/expansion/moderation-score', [AiExpansionController::class, 'moderationScore'])
        ->middleware('throttle:30,1')
        ->name('api_ai_expansion_moderation_score');
    Route::get('/ai/expansion/recommend-partners', [AiExpansionController::class, 'recommendPartners'])
        ->middleware('throttle:30,1')
        ->name('api_ai_expansion_recommend_partners');
    Route::post('/ai/expansion/studio-forecast', [AiExpansionController::class, 'studioForecast'])
        ->middleware('throttle:30,1')
        ->name('api_ai_expansion_studio_forecast');
    Route::post('/ai/expansion/support-chat', [AiExpansionController::class, 'supportChat'])
        ->middleware('throttle:30,1')
        ->name('api_ai_expansion_support_chat');
    Route::post('/ai/expansion/compose-content', [AiExpansionController::class, 'composeContent'])
        ->middleware('throttle:30,1')
        ->name('api_ai_expansion_compose_content');

    Route::get('/mobile/v1/sync/manifest', [MobileSyncController::class, 'manifest'])
        ->middleware('throttle:60,1')
        ->name('api_mobile_sync_manifest');

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
