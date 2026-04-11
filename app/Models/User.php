<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'registration_channel',
        'registration_metadata',
        'telegram_id',
        'vk_access_token',
        'vk_refresh_token',
        'vk_token_expires_at',
        'vk_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'vk_access_token',
        'vk_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ai_trial_started_at' => 'datetime',
            'ai_subscription_valid_until' => 'datetime',
            'password' => 'hashed',
            'registration_metadata' => 'array',
            'telegram_id' => 'integer',
            'vk_token_expires_at' => 'datetime',
            'vk_user_id' => 'integer',
            'notification_preferences' => 'array',
        ];
    }

    public function wantsMusicLineupInvitationEmail(): bool
    {
        return (bool) (($this->notification_preferences ?? [])['music_lineup_email'] ?? true);
    }

    public function setMusicLineupInvitationEmail(bool $enabled): void
    {
        $prefs = $this->notification_preferences ?? [];
        $prefs['music_lineup_email'] = $enabled;
        $this->notification_preferences = $prefs;
        $this->save();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function authAttempts()
    {
        return $this->hasMany(AuthAttempt::class);
    }

    public function successfulAuthAttempts()
    {
        return $this->authAttempts()->where('status', 'success');
    }

    public function getRegistrationChannel(): ?string
    {
        return $this->registration_channel;
    }

    public function getRegistrationMetadata(): array
    {
        return $this->registration_metadata ?? [];
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->using(ConversationUser::class)
            ->withPivot([
                'role',
                'last_read_message_id',
                'joined_at',
                'notifications_muted',
                'mute_until',
            ])
            ->withTimestamps();
    }

    public function messengerPreference(): HasOne
    {
        return $this->hasOne(MessengerUserPreference::class);
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function devicePushTokens(): HasMany
    {
        return $this->hasMany(DevicePushToken::class);
    }

    public function aiScheduledItems(): HasMany
    {
        return $this->hasMany(UserAiScheduledItem::class);
    }

    public function aiSubscriptions(): HasMany
    {
        return $this->hasMany(UserAiSubscription::class);
    }

    public function aiPreference(): HasOne
    {
        return $this->hasOne(UserAiPreference::class);
    }

    /**
     * @return HasMany<KanbanBoard, User>
     */
    public function kanbanBoards(): HasMany
    {
        return $this->hasMany(KanbanBoard::class);
    }

    /**
     * @return HasOne<UserKanbanCalendarSetting, User>
     */
    public function kanbanCalendarSetting(): HasOne
    {
        return $this->hasOne(UserKanbanCalendarSetting::class);
    }

    /**
     * @return BelongsToMany<KanbanBoard, User>
     */
    public function sharedKanbanBoards(): BelongsToMany
    {
        return $this->belongsToMany(KanbanBoard::class, 'kanban_board_user')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    /**
     * @return HasOne<Musician, User>
     */
    public function musician(): HasOne
    {
        return $this->hasOne(Musician::class);
    }

    /**
     * @return HasOne<Teacher, User>
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * @return HasMany<Peformer, User>
     */
    public function ownedPeformers(): HasMany
    {
        return $this->hasMany(Peformer::class, 'owner_user_id');
    }

    /**
     * @return BelongsToMany<Peformer, User>
     */
    public function administeredPeformers(): BelongsToMany
    {
        return $this->belongsToMany(Peformer::class, 'peformer_admins', 'user_id', 'peformer_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Studio, User>
     */
    public function ownedStudios(): HasMany
    {
        return $this->hasMany(Studio::class, 'owner_user_id');
    }

    /**
     * @return HasMany<Rehersal, User>
     */
    public function ownedRehearsals(): HasMany
    {
        return $this->hasMany(Rehersal::class, 'owner_user_id');
    }

    /**
     * @return HasMany<School, User>
     */
    public function ownedSchools(): HasMany
    {
        return $this->hasMany(School::class, 'owner_user_id');
    }

    /**
     * @return HasMany<RecordLabel, User>
     */
    public function ownedRecordLabels(): HasMany
    {
        return $this->hasMany(RecordLabel::class, 'owner_user_id');
    }

    /**
     * @return HasMany<ProducerCenter, User>
     */
    public function ownedProducerCenters(): HasMany
    {
        return $this->hasMany(ProducerCenter::class, 'owner_user_id');
    }

    /**
     * @return HasMany<Shop, User>
     */
    public function ownedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_user_id');
    }

    /**
     * @return HasMany<ShopCartItem, User>
     */
    public function shopCartItems(): HasMany
    {
        return $this->hasMany(ShopCartItem::class);
    }

    /**
     * @return HasMany<ShopOrder, User>
     */
    public function shopOrdersAsBuyer(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'buyer_user_id');
    }
}
