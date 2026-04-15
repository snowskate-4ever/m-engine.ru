<?php

namespace App\Models;

use App\Enums\UserMusicProfile;
use App\Models\Traits\HasAddresses;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasAddresses;

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
        'music_profiles',
        'music_profile_criteria',
        'active_music_actor_type',
        'active_music_actor_id',
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
            'music_profiles' => 'array',
            'music_profile_criteria' => 'array',
        ];
    }

    public function hasMusicProfile(UserMusicProfile|string $profile): bool
    {
        $value = $profile instanceof UserMusicProfile ? $profile->value : (string) $profile;
        $profiles = $this->music_profiles ?? [];

        return in_array($value, $profiles, true);
    }

    public function canActAsEventOrganizer(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::EventOrganizer);
    }

    public function canActAsMusician(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::Musician);
    }

    public function canActAsTeacher(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::Teacher);
    }

    public function canActAsVenueRepresentative(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::VenueRepresentative);
    }

    public function canActAsManager(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::Manager);
    }

    public function canActAsSessionMusician(): bool
    {
        return $this->hasMusicProfile(UserMusicProfile::SessionMusician);
    }

    public function setActiveMusicActor(?string $type, ?int $id): void
    {
        $this->active_music_actor_type = $type;
        $this->active_music_actor_id = $id;
        $this->save();
    }

    public function musicProfileMemberships(): HasMany
    {
        return $this->hasMany(MusicProfileMembership::class, 'member_user_id');
    }

    public function integrationApiTokens(): HasMany
    {
        return $this->hasMany(IntegrationApiToken::class);
    }

    public function xpLedgers(): HasMany
    {
        return $this->hasMany(UserXpLedger::class);
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function invitedMusicProfileMemberships(): HasMany
    {
        return $this->hasMany(MusicProfileMembership::class, 'invited_by_user_id');
    }

    public function hasAcceptedMusicMembershipFor(Model $entity, string $role): bool
    {
        return $this->musicProfileMemberships()
            ->where('entity_type', $entity::class)
            ->where('entity_id', $entity->getKey())
            ->where('role', $role)
            ->where('status', 'accepted')
            ->exists();
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
     * @return HasMany<ConcertVenue, User>
     */
    public function ownedConcertVenues(): HasMany
    {
        return $this->hasMany(ConcertVenue::class, 'owner_user_id');
    }

    public function organizedMusicEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'music_organizer_user_id');
    }

    public function searchRequests(): HasMany
    {
        return $this->hasMany(SearchRequest::class, 'created_by_user_id');
    }

    public function createdRegistrationInvites(): HasMany
    {
        return $this->hasMany(RegistrationInvite::class, 'created_by_user_id');
    }

    public function usedRegistrationInvites(): HasMany
    {
        return $this->hasMany(RegistrationInvite::class, 'used_by_user_id');
    }

    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }

    public function organizerPerformerInvites(): HasMany
    {
        return $this->hasMany(OrganizerPerformerInvite::class, 'organizer_user_id');
    }

    public function organizerVenueInvites(): HasMany
    {
        return $this->hasMany(OrganizerVenueInvite::class, 'organizer_user_id');
    }

    public function organizerStudioInvites(): HasMany
    {
        return $this->hasMany(OrganizerStudioInvite::class, 'organizer_user_id');
    }

    public function organizerRehersalInvites(): HasMany
    {
        return $this->hasMany(OrganizerRehersalInvite::class, 'organizer_user_id');
    }

    public function organizerSchoolInvites(): HasMany
    {
        return $this->hasMany(OrganizerSchoolInvite::class, 'organizer_user_id');
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

    /**
     * Критерии для профиля пользователя (города, опыт и т.п.), хранятся в JSON по ключу профиля.
     *
     * @return array<string, mixed>
     */
    public function musicProfileCriteriaFor(string $profileKey): array
    {
        $all = $this->music_profile_criteria ?? [];
        $row = $all[$profileKey] ?? [];

        return is_array($row) ? $row : [];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function mergeMusicProfileCriteria(string $profileKey, array $criteria): void
    {
        $all = $this->music_profile_criteria ?? [];
        $all[$profileKey] = $criteria;
        $this->music_profile_criteria = $all;
    }
}
