<?php

declare(strict_types=1);

namespace Tests\Feature\Kanban;

use App\Livewire\Calendar\CalendarPage;
use App\Livewire\Kanban\KanbanWorkspace;
use App\Livewire\Music\PerformerEditPage;
use App\Models\KanbanActivityLog;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class KanbanWorkspaceCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_mount_creates_default_board_with_five_columns_for_new_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(KanbanWorkspace::class);

        $this->assertDatabaseHas('kanban_boards', [
            'user_id' => $user->id,
            'name' => 'Моя доска',
        ]);

        $board = KanbanBoard::query()
            ->where('user_id', $user->id)
            ->where('name', 'Моя доска')
            ->firstOrFail();
        $columnNames = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->orderBy('position')
            ->pluck('name')
            ->all();

        $this->assertSame(['Входящие', 'К выполнению', 'В работе', 'На паузе', 'Готово'], $columnNames);

        $this->assertDatabaseHas('kanban_boards', [
            'name' => 'Архив',
            'source_type' => User::class,
            'source_id' => $user->id,
        ]);
    }

    public function test_archive_card_moves_card_to_archive_and_logs_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PerformerEditPage::class)
            ->set('name', 'Archive Tester')
            ->set('description', 'desc')
            ->set('performer_kind', 'band')
            ->call('save');

        $performer = \App\Models\Peformer::query()->where('name', 'Archive Tester')->firstOrFail();

        $entityBoard = KanbanBoard::query()
            ->where('source_type', $performer->getMorphClass())
            ->where('source_id', $performer->id)
            ->firstOrFail();
        $card = KanbanCard::query()
            ->where('source_type', $performer->getMorphClass())
            ->where('source_id', $performer->id)
            ->firstOrFail();

        Livewire::test(KanbanWorkspace::class)
            ->set('boardId', $entityBoard->id)
            ->call('archiveCard', $card->id);

        $archiveBoard = KanbanBoard::query()
            ->where('source_type', User::class)
            ->where('source_id', $user->id)
            ->firstOrFail();
        $archiveColumn = KanbanColumn::query()
            ->where('kanban_board_id', $archiveBoard->id)
            ->where('name', 'Архив '.$entityBoard->name)
            ->firstOrFail();

        $this->assertDatabaseHas('kanban_cards', [
            'id' => $card->id,
            'kanban_column_id' => $archiveColumn->id,
            'is_archived' => true,
        ]);
        $this->assertDatabaseHas('kanban_activity_logs', [
            'action' => 'card_archived',
            'kanban_board_id' => $archiveBoard->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_workspace_does_not_delete_the_last_column(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = KanbanBoard::query()->create([
            'user_id' => $user->id,
            'name' => 'Single column board',
            'position' => 0,
        ]);
        $column = KanbanColumn::query()->create([
            'kanban_board_id' => $board->id,
            'name' => 'Only column',
            'position' => 0,
        ]);

        Livewire::test(KanbanWorkspace::class)
            ->set('boardId', $board->id)
            ->call('deleteColumn', $column->id);

        $this->assertSame(1, KanbanColumn::query()->where('kanban_board_id', $board->id)->count());
        $this->assertDatabaseHas('kanban_columns', [
            'id' => $column->id,
            'kanban_board_id' => $board->id,
            'name' => 'Only column',
        ]);
    }

    public function test_calendar_kanban_scope_shows_column_move_activity_log_projection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$board, $card] = $this->createBoardWithMoveLog($user);

        Livewire::test(CalendarPage::class)
            ->set('scopeMode', 'kanban')
            ->assertSee('Task A')
            ->assertSet('scopeMode', 'kanban');

        $this->assertDatabaseHas('kanban_activity_logs', [
            'action' => 'card_column_changed',
            'kanban_board_id' => $board->id,
        ]);
    }

    public function test_column_move_activity_log_does_not_auto_create_calendar_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createBoardWithMoveLog($user);

        $this->assertDatabaseCount('calendar_events', 0);

        Livewire::test(CalendarPage::class)
            ->set('scopeMode', 'kanban')
            ->assertSee('Task A');

        $this->assertDatabaseCount('calendar_events', 0);
    }

    /**
     * @return array{KanbanBoard, KanbanCard}
     */
    private function createBoardWithMoveLog(User $user): array
    {
        $board = KanbanBoard::query()->create([
            'user_id' => $user->id,
            'name' => 'Board A',
            'position' => 0,
        ]);
        $todo = KanbanColumn::query()->create([
            'kanban_board_id' => $board->id,
            'name' => 'К выполнению',
            'position' => 0,
        ]);
        $done = KanbanColumn::query()->create([
            'kanban_board_id' => $board->id,
            'name' => 'Готово',
            'position' => 1,
        ]);
        $card = KanbanCard::query()->create([
            'kanban_column_id' => $done->id,
            'title' => 'Task A',
            'description' => 'Task description',
            'position' => 0,
        ]);

        KanbanActivityLog::query()->create([
            'user_id' => $user->id,
            'action' => 'card_column_changed',
            'kanban_board_id' => $board->id,
            'payload' => [
                'card_id' => $card->id,
                'from_column_id' => $todo->id,
                'to_column_id' => $done->id,
                'from_column_name' => $todo->name,
                'to_column_name' => $done->name,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$board, $card];
    }
}
