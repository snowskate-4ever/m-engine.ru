<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\SearchGoal;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\User;

class SearchGoalEligibilityService
{
    /**
     * @return list<class-string>
     */
    public function supportedInitiatorTypes(): array
    {
        return [
            User::class,
            Peformer::class,
            Musician::class,
            ConcertVenue::class,
            Studio::class,
            Rehersal::class,
            School::class,
        ];
    }

    /**
     * @return list<SearchGoal>
     */
    public function allowedGoalsForInitiator(string $initiatorType, ?string $profileKey = null): array
    {
        return match ($initiatorType) {
            Peformer::class => [
                SearchGoal::FindMusicianForPerformer,
                SearchGoal::FindOrganizerForPerformer,
            ],
            Musician::class => [
                SearchGoal::FindPerformerForMusician,
            ],
            ConcertVenue::class => [
                SearchGoal::FindOrganizerForVenue,
            ],
            Studio::class => [
                SearchGoal::FindOrganizerForStudio,
            ],
            Rehersal::class => [
                SearchGoal::FindOrganizerForRehearsal,
            ],
            School::class => [
                SearchGoal::FindOrganizerForSchool,
            ],
            User::class => $this->allowedGoalsForUserProfile($profileKey),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function allowedGoalValuesForInitiator(string $initiatorType, ?string $profileKey = null): array
    {
        return array_map(
            static fn (SearchGoal $goal): string => $goal->value,
            $this->allowedGoalsForInitiator($initiatorType, $profileKey)
        );
    }

    public function isAllowed(string $initiatorType, SearchGoal $goal, ?string $profileKey = null): bool
    {
        foreach ($this->allowedGoalsForInitiator($initiatorType, $profileKey) as $allowedGoal) {
            if ($allowedGoal === $goal) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<SearchGoal>
     */
    private function allowedGoalsForUserProfile(?string $profileKey): array
    {
        return match ($profileKey) {
            'event_organizer',
            'manager',
            'venue_representative',
            null => [
                SearchGoal::FindPerformerForOrganizer,
                SearchGoal::FindVenueForOrganizerEvent,
                SearchGoal::FindStudioForOrganizerEvent,
                SearchGoal::FindRehearsalForOrganizerEvent,
                SearchGoal::FindSchoolForOrganizerEvent,
            ],
            default => [],
        };
    }
}
