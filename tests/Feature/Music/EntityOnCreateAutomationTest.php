<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Livewire\Kanban\KanbanWorkspace;
use App\Livewire\Music\PerformerEditPage;
use App\Livewire\Music\VenueEditPage;
use App\Models\CalendarEvent;
use App\Models\ConcertVenue;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use App\Services\Music\MusicProfileMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EntityOnCreateAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_creation_creates_kanban_and_calendar_automation_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(PerformerEditPage::class)
            ->set('name', 'Test Band')
            ->set('description', 'desc')
            ->set('performer_kind', 'band');

        $component->call('save');
        $component->set('description', 'updated')->call('save');

        $performer = Peformer::query()->where('name', 'Test Band')->firstOrFail();

        $this->assertAutomationCreatedForEntity(
            ownerId: $user->id,
            entityType: $performer->getMorphClass(),
            entityId: (int) $performer->id,
            entityBoardName: 'Исполнитель: Test Band',
        );

        $this->assertSame(
            1,
            CalendarEvent::query()
                ->where('user_id', $user->id)
                ->where('source_type', $performer->getMorphClass())
                ->where('source_id', $performer->id)
                ->count()
        );
    }

    public function test_studio_creation_creates_kanban_and_calendar_automation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(VenueEditPage::class, ['kind' => 'studio'])
            ->set('name', 'Studio Alpha')
            ->set('description', 'desc')
            ->call('save');

        $studio = Studio::query()->where('name', 'Studio Alpha')->firstOrFail();

        $this->assertAutomationCreatedForEntity(
            ownerId: $user->id,
            entityType: $studio->getMorphClass(),
            entityId: (int) $studio->id,
            entityBoardName: 'Студия: Studio Alpha',
        );
    }

    public function test_shop_creation_creates_kanban_and_calendar_automation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(VenueEditPage::class, ['kind' => 'shop'])
            ->set('name', 'Shop Beta')
            ->set('description', 'desc')
            ->call('save');

        $shop = Shop::query()->where('name', 'Shop Beta')->firstOrFail();

        $this->assertAutomationCreatedForEntity(
            ownerId: $user->id,
            entityType: $shop->getMorphClass(),
            entityId: (int) $shop->id,
            entityBoardName: 'Магазин: Shop Beta',
        );
    }

    public function test_other_supported_venue_entities_trigger_automation_smoke(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cases = [
            ['kind' => 'rehearsal', 'name' => 'Reh One', 'class' => Rehersal::class, 'label' => 'Репетиционная'],
            ['kind' => 'concert_venue', 'name' => 'Venue One', 'class' => ConcertVenue::class, 'label' => 'Площадка'],
            ['kind' => 'school', 'name' => 'School One', 'class' => School::class, 'label' => 'Школа'],
            ['kind' => 'record_label', 'name' => 'Label One', 'class' => RecordLabel::class, 'label' => 'Лейбл'],
            ['kind' => 'producer_center', 'name' => 'Producer One', 'class' => ProducerCenter::class, 'label' => 'Продюсерский центр'],
        ];

        foreach ($cases as $case) {
            Livewire::test(VenueEditPage::class, ['kind' => $case['kind']])
                ->set('name', $case['name'])
                ->set('description', 'desc')
                ->call('save');

            $entity = $case['class']::query()->where('name', $case['name'])->firstOrFail();

            $this->assertAutomationCreatedForEntity(
                ownerId: $user->id,
                entityType: $entity->getMorphClass(),
                entityId: (int) $entity->id,
                entityBoardName: $case['label'].': '.$case['name'],
            );
        }
    }

    public function test_entity_board_is_linked_and_cannot_be_deleted_from_kanban_workspace(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PerformerEditPage::class)
            ->set('name', 'Delete Guard Band')
            ->set('description', 'desc')
            ->set('performer_kind', 'band')
            ->call('save');

        $performer = Peformer::query()->where('name', 'Delete Guard Band')->firstOrFail();
        $entityBoard = KanbanBoard::query()
            ->where('source_type', $performer->getMorphClass())
            ->where('source_id', $performer->id)
            ->firstOrFail();

        Livewire::test(KanbanWorkspace::class)
            ->set('boardId', $entityBoard->id)
            ->call('deleteBoard', $entityBoard->id);

        $this->assertDatabaseHas('kanban_boards', ['id' => $entityBoard->id]);
    }

    public function test_accepted_membership_grants_entity_board_access_and_revoked_removes_it(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(PerformerEditPage::class)
            ->set('name', 'Shared Band')
            ->set('description', 'desc')
            ->set('performer_kind', 'band')
            ->call('save');

        $performer = Peformer::query()->where('name', 'Shared Band')->firstOrFail();
        $entityBoard = KanbanBoard::query()
            ->where('source_type', $performer->getMorphClass())
            ->where('source_id', $performer->id)
            ->firstOrFail();
        $archiveBoard = KanbanBoard::query()
            ->where('source_type', User::class)
            ->where('source_id', $owner->id)
            ->firstOrFail();

        $service = app(MusicProfileMembershipService::class);
        $membership = $service->invite($owner, $performer, $member, MusicMembershipRole::Manager);
        $service->respond($member, $membership, MusicMembershipStatus::Accepted);

        $this->assertDatabaseHas('kanban_board_user', [
            'kanban_board_id' => $entityBoard->id,
            'user_id' => $member->id,
            'access_level' => 'editor',
        ]);
        $this->assertDatabaseHas('kanban_board_user', [
            'kanban_board_id' => $archiveBoard->id,
            'user_id' => $member->id,
            'access_level' => 'editor',
        ]);

        $service->revoke($owner, $membership->refresh());

        $this->assertDatabaseMissing('kanban_board_user', [
            'kanban_board_id' => $entityBoard->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseMissing('kanban_board_user', [
            'kanban_board_id' => $archiveBoard->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_deleting_entity_removes_linked_board(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(VenueEditPage::class, ['kind' => 'studio'])
            ->set('name', 'Disposable Studio')
            ->set('description', 'desc')
            ->call('save');

        $studio = Studio::query()->where('name', 'Disposable Studio')->firstOrFail();
        $board = KanbanBoard::query()
            ->where('source_type', $studio->getMorphClass())
            ->where('source_id', $studio->id)
            ->firstOrFail();

        $studio->delete();

        $this->assertSoftDeleted('kanban_boards', ['id' => $board->id]);
    }

    private function assertAutomationCreatedForEntity(int $ownerId, string $entityType, int $entityId, string $entityBoardName): void
    {
        $this->assertDatabaseHas('kanban_boards', [
            'user_id' => $ownerId,
            'name' => 'Моя доска',
        ]);
        $this->assertDatabaseHas('kanban_boards', [
            'name' => 'Архив',
            'source_type' => User::class,
            'source_id' => $ownerId,
        ]);
        $this->assertDatabaseHas('kanban_boards', [
            'name' => $entityBoardName,
            'source_type' => $entityType,
            'source_id' => $entityId,
        ]);

        $board = KanbanBoard::query()
            ->where('name', $entityBoardName)
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->firstOrFail();
        $this->assertNotNull($board->user_id);
        $this->assertSame($entityType, $board->source_type);
        $this->assertSame($entityId, (int) $board->source_id);
        $this->assertDatabaseHas('kanban_board_user', [
            'kanban_board_id' => $board->id,
            'user_id' => $ownerId,
            'access_level' => 'editor',
        ]);
        $columns = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->orderBy('position')
            ->pluck('name')
            ->all();
        $this->assertSame(['Входящие', 'К выполнению', 'В работе', 'На паузе', 'Готово'], $columns);

        $todoColumn = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->where('name', 'К выполнению')
            ->firstOrFail();

        $this->assertNotNull(KanbanCard::query()
            ->where('kanban_column_id', $todoColumn->id)
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->first());

        $archiveBoard = KanbanBoard::query()
            ->where('source_type', User::class)
            ->where('source_id', $ownerId)
            ->firstOrFail();
        $archiveColumns = KanbanColumn::query()
            ->where('kanban_board_id', $archiveBoard->id)
            ->orderBy('position')
            ->pluck('name')
            ->all();
        $this->assertSame(['Архив'], $archiveColumns);

        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $ownerId,
            'source_type' => $entityType,
            'source_id' => $entityId,
            'is_public' => false,
            'status' => 'planned',
        ]);
    }
}
