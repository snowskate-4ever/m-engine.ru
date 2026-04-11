<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Enums\KanbanAccessLevel;
use App\Enums\KanbanCardImportance;
use App\Enums\KanbanVisibilityMode;
use App\Models\KanbanAccessGrant;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanCardAttachment;
use App\Models\KanbanCardComment;
use App\Models\KanbanColumn;
use App\Models\KanbanUserSharedBoardOrder;
use App\Models\Message;
use App\Models\User;
use App\Services\Kanban\KanbanAccessService;
use App\Services\Kanban\KanbanActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class KanbanWorkspace extends Component
{
    use WithFileUploads;

    public ?int $boardId = null;

    public string $newBoardName = '';

    public string $newColumnName = '';

    public string $newCardTitle = '';

    public string $newCardDescription = '';

    public ?int $newCardColumnId = null;

    public ?int $newCardSourceChatMessageId = null;

    /** @var value-of<KanbanCardImportance> */
    public string $newCardImportance = 'normal';

    public bool $showAddWizardModal = false;

    /** @var 'choose'|'board'|'column'|'card' */
    public string $addWizardStep = 'choose';

    public bool $showCardDetailModal = false;

    public ?int $detailCardId = null;

    public string $editCardTitle = '';

    public string $editCardDescription = '';

    public ?int $editCardColumnId = null;

    /** @var value-of<KanbanCardImportance> */
    public string $editCardImportance = 'normal';

    public string $editCardDueAtLocal = '';

    public string $newCardCommentBody = '';

    public bool $showShareModal = false;

    public ?int $shareSelectedUserId = null;

    /** @var value-of<KanbanAccessLevel> */
    public string $shareUserAccessLevel = 'editor';

    public bool $showColumnAccessModal = false;

    public ?int $columnAccessColumnId = null;

    /** @var value-of<KanbanVisibilityMode> */
    public string $columnAccessMode = 'inherit';

    /**
     * @var list<array{grantee_kind: string, grantee_id: int, access_level: string}>
     */
    public array $columnAccessGrantRows = [];

    /** @var value-of<KanbanVisibilityMode> */
    public string $editCardVisibilityMode = 'inherit';

    /**
     * @var list<array{grantee_kind: string, grantee_id: int, access_level: string}>
     */
    public array $editCardGrantRows = [];

    public bool $showCardAttachmentsListModal = false;

    public bool $showCardAttachmentUploadModal = false;

    public ?int $attachmentsContextCardId = null;

    public $attachmentUpload = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! KanbanBoard::query()->forUserAccess($user)->exists()) {
            DB::transaction(function () use ($user): void {
                $board = KanbanBoard::query()->create([
                    'user_id' => $user->id,
                    'name' => 'Моя доска',
                    'position' => 0,
                ]);
                $defaults = ['К выполнению', 'В работе', 'Готово'];
                foreach ($defaults as $index => $name) {
                    KanbanColumn::query()->create([
                        'kanban_board_id' => $board->id,
                        'name' => $name,
                        'position' => $index,
                    ]);
                }
            });
        }
        $this->boardId = $this->firstAccessibleBoardId($user);
        $this->syncNewCardColumnFromBoard();
        $this->applyKanbanPrefillFromSession();
        if (! $this->showAddWizardModal) {
            $this->tryOpenCardFromQuery();
        }
    }

    private function applyKanbanPrefillFromSession(): void
    {
        /** @var mixed $prefill */
        $prefill = session()->pull('kanban_new_card_prefill');
        if (! is_array($prefill)) {
            return;
        }

        $titleRaw = $prefill['title'] ?? '';
        $title = is_string($titleRaw) ? trim($titleRaw) : '';
        if ($title === '') {
            return;
        }

        $descRaw = $prefill['description'] ?? '';
        $description = is_string($descRaw) ? $descRaw : '';

        $this->newCardTitle = mb_substr($title, 0, 500);
        $this->newCardDescription = mb_substr($description, 0, 65535);
        $this->newCardImportance = KanbanCardImportance::Normal->value;
        $this->newCardSourceChatMessageId = null;
        $srcRaw = $prefill['source_chat_message_id'] ?? null;
        if (is_numeric($srcRaw)) {
            $srcId = (int) $srcRaw;
            if ($srcId > 0 && Message::query()->whereKey($srcId)->exists()) {
                $this->newCardSourceChatMessageId = $srcId;
            }
        }

        $boardRaw = $prefill['kanban_board_id'] ?? null;
        if (is_numeric($boardRaw)) {
            $prefillBoardId = (int) $boardRaw;
            if ($prefillBoardId > 0 && KanbanBoard::query()->forUserAccess(auth()->user())->whereKey($prefillBoardId)->exists()) {
                $this->boardId = $prefillBoardId;
            }
        }

        $this->syncNewCardColumnFromBoard();
        $this->addWizardStep = 'card';
        $this->showAddWizardModal = true;
    }

    private function tryOpenCardFromQuery(): void
    {
        $openCardId = (int) request()->query('open_card', 0);
        if ($openCardId <= 0) {
            return;
        }

        $user = auth()->user();
        $card = KanbanCard::query()
            ->whereKey($openCardId)
            ->with('column')
            ->first();
        if ($card === null || $card->column === null) {
            return;
        }

        $boardId = $card->column->kanban_board_id;
        if (! KanbanBoard::query()->forUserAccess($user)->whereKey($boardId)->exists()) {
            return;
        }

        if (! $this->kanbanAccess()->canViewCard($user, $card)) {
            return;
        }

        $this->boardId = $boardId;
        $this->syncNewCardColumnFromBoard();
        $this->openCardDetail($openCardId);
    }

    public function updatedBoardId(): void
    {
        if ($this->boardId === null) {
            return;
        }

        $user = auth()->user();
        if (! KanbanBoard::query()->forUserAccess($user)->whereKey($this->boardId)->exists()) {
            $this->boardId = $this->firstAccessibleBoardId($user);
            $this->syncNewCardColumnFromBoard();

            return;
        }

        $this->syncNewCardColumnFromBoard();
    }

    public function selectBoard(int $id): void
    {
        $user = auth()->user();
        if (! KanbanBoard::query()->forUserAccess($user)->whereKey($id)->exists()) {
            return;
        }
        $this->boardId = $id;
    }

    public function createBoard(): void
    {
        $name = trim($this->newBoardName);
        if ($name === '') {
            return;
        }

        $max = (int) auth()->user()->kanbanBoards()->max('position');
        $board = KanbanBoard::query()->create([
            'user_id' => auth()->id(),
            'name' => $name,
            'position' => $max + 1,
        ]);
        foreach (['К выполнению', 'В работе', 'Готово'] as $index => $colName) {
            KanbanColumn::query()->create([
                'kanban_board_id' => $board->id,
                'name' => $colName,
                'position' => $index,
            ]);
        }

        KanbanActivityLogger::log(auth()->user(), 'board_created', (int) $board->id, [
            'board_name' => $board->name,
        ]);

        $this->boardId = $board->id;
        $this->newBoardName = '';
        $this->closeAddWizardModal();
    }

    public function openAddWizardModal(): void
    {
        $this->addWizardStep = 'choose';
        $this->newBoardName = '';
        $this->newColumnName = '';
        $this->newCardTitle = '';
        $this->newCardDescription = '';
        $this->newCardImportance = KanbanCardImportance::Normal->value;
        $this->newCardSourceChatMessageId = null;
        $this->syncNewCardColumnFromBoard();
        $this->showAddWizardModal = true;
    }

    public function closeAddWizardModal(): void
    {
        $this->showAddWizardModal = false;
        $this->addWizardStep = 'choose';
        $this->newCardSourceChatMessageId = null;
    }

    public function backAddWizardChoose(): void
    {
        $this->addWizardStep = 'choose';
        $this->newBoardName = '';
        $this->newColumnName = '';
        $this->newCardTitle = '';
        $this->newCardDescription = '';
        $this->newCardImportance = KanbanCardImportance::Normal->value;
        $this->newCardSourceChatMessageId = null;
        $this->syncNewCardColumnFromBoard();
    }

    public function chooseAddWizard(string $step): void
    {
        if (! in_array($step, ['board', 'column', 'card'], true)) {
            return;
        }

        $this->addWizardStep = $step;
        $this->newBoardName = '';
        $this->newColumnName = '';
        $this->newCardTitle = '';
        $this->newCardDescription = '';
        $this->newCardImportance = KanbanCardImportance::Normal->value;
        $this->newCardSourceChatMessageId = null;
        $this->syncNewCardColumnFromBoard();
    }

    public function createColumn(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canEditBoard(auth()->user(), $board)) {
            return;
        }

        $name = trim($this->newColumnName);
        if ($name === '') {
            return;
        }

        $max = (int) $board->columns()->max('position');
        $column = KanbanColumn::query()->create([
            'kanban_board_id' => $board->id,
            'name' => $name,
            'position' => $max + 1,
        ]);

        KanbanActivityLogger::log(auth()->user(), 'column_created', (int) $board->id, [
            'column_id' => $column->id,
            'column_name' => $column->name,
        ]);

        $this->newColumnName = '';
        $this->closeAddWizardModal();
    }

    public function createCard(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->newCardColumnId === null) {
            return;
        }

        $title = trim($this->newCardTitle);
        if ($title === '') {
            return;
        }

        $column = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKey($this->newCardColumnId)
            ->with('grants')
            ->first();
        if ($column === null || ! $this->kanbanAccess()->canEditColumn(auth()->user(), $column)) {
            return;
        }

        $this->validate([
            'newCardImportance' => ['required', Rule::enum(KanbanCardImportance::class)],
        ]);

        $importance = KanbanCardImportance::from((string) $this->newCardImportance);

        $max = (int) $column->cards()->max('position');
        $description = trim($this->newCardDescription);

        $payload = [
            'kanban_column_id' => $column->id,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'importance' => $importance,
            'position' => $max + 1,
        ];
        if ($this->newCardSourceChatMessageId !== null) {
            $payload['source_chat_message_id'] = $this->newCardSourceChatMessageId;
        }

        $card = KanbanCard::query()->create($payload);

        $this->newCardSourceChatMessageId = null;

        KanbanActivityLogger::log(auth()->user(), 'card_created', (int) $board->id, [
            'card_id' => $card->id,
            'title' => $card->title,
            'column_id' => $column->id,
            'column_name' => $column->name,
        ]);

        $this->newCardTitle = '';
        $this->newCardDescription = '';
        $this->closeAddWizardModal();
    }

    public function openCardDetail(int $id): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null) {
            return;
        }

        $boardId = $board->id;
        $card = KanbanCard::query()
            ->whereKey($id)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $boardId))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canViewCard(auth()->user(), $card)) {
            return;
        }

        $this->detailCardId = $id;
        $this->editCardTitle = $card->title;
        $this->editCardDescription = (string) ($card->description ?? '');
        $this->editCardColumnId = $card->kanban_column_id;
        $this->editCardImportance = ($card->importance ?? KanbanCardImportance::Normal)->value;
        $this->editCardDueAtLocal = $card->due_at !== null
            ? $card->due_at->timezone('Europe/Moscow')->format('Y-m-d\TH:i')
            : '';
        $this->hydrateCardVisibilityForm($card);
        $this->newCardCommentBody = '';
        $this->resetErrorBag();
        $this->showCardDetailModal = true;
    }

    public function addCardComment(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->detailCardId === null) {
            return;
        }

        $validated = $this->validate([
            'newCardCommentBody' => ['required', 'string', 'max:65535'],
        ]);

        $body = trim($validated['newCardCommentBody']);
        if ($body === '') {
            $this->addError('newCardCommentBody', 'Введите текст комментария.');

            return;
        }

        $boardId = $board->id;
        $card = KanbanCard::query()
            ->whereKey($this->detailCardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $boardId))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        KanbanCardComment::query()->create([
            'kanban_card_id' => $card->id,
            'user_id' => (int) auth()->id(),
            'body' => $body,
        ]);

        KanbanActivityLogger::log(auth()->user(), 'card_comment_added', (int) $board->id, [
            'card_id' => $card->id,
        ]);

        $this->newCardCommentBody = '';
        $this->resetValidation('newCardCommentBody');
    }

    public function updateCard(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->detailCardId === null || $this->editCardColumnId === null) {
            return;
        }

        $validated = $this->validate([
            'editCardTitle' => ['required', 'string', 'max:255'],
            'editCardDescription' => ['nullable', 'string', 'max:65535'],
            'editCardColumnId' => ['required', 'integer', 'exists:kanban_columns,id'],
            'editCardImportance' => ['required', Rule::enum(KanbanCardImportance::class)],
            'editCardDueAtLocal' => ['nullable', 'string', 'max:32'],
        ]);

        $column = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKey($validated['editCardColumnId'])
            ->with('grants')
            ->first();
        if ($column === null) {
            $this->addError('editCardColumnId', 'Выберите колонку на этой доске.');

            return;
        }

        $boardId = $board->id;
        $card = KanbanCard::query()
            ->whereKey($this->detailCardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $boardId))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        $newColumnId = (int) $validated['editCardColumnId'];
        if ((int) $card->kanban_column_id !== $newColumnId
            && ! $this->kanbanAccess()->canEditColumn(auth()->user(), $column)) {
            return;
        }

        $title = trim($validated['editCardTitle']);
        if ($title === '') {
            $this->addError('editCardTitle', 'Укажите название карточки.');

            return;
        }

        $desc = $validated['editCardDescription'];
        $description = ($desc === null || trim((string) $desc) === '') ? null : trim((string) $desc);

        $oldColumnId = (int) $card->kanban_column_id;
        $fromColumnSnapshot = $card->column;

        $impVal = $validated['editCardImportance'];
        $importance = $impVal instanceof KanbanCardImportance
            ? $impVal
            : KanbanCardImportance::from((string) $impVal);

        $dueRaw = trim((string) ($validated['editCardDueAtLocal'] ?? ''));
        $dueAt = null;
        if ($dueRaw !== '') {
            try {
                $dueAt = CarbonImmutable::parse($dueRaw, 'Europe/Moscow')->utc();
            } catch (\Throwable) {
                $this->addError('editCardDueAtLocal', 'Некорректная дата и время срока.');

                return;
            }
        }

        $updates = [
            'title' => $title,
            'description' => $description,
            'importance' => $importance,
            'due_at' => $dueAt,
        ];

        if ($newColumnId !== $oldColumnId) {
            $max = (int) KanbanCard::query()->where('kanban_column_id', $newColumnId)->max('position');
            $updates['kanban_column_id'] = $newColumnId;
            $updates['position'] = $max + 1;
        }

        $card->update($updates);

        $card->refresh();

        if ($newColumnId !== $oldColumnId) {
            KanbanActivityLogger::log(auth()->user(), 'card_column_changed', (int) $board->id, [
                'card_id' => $card->id,
                'from_column_id' => $oldColumnId,
                'to_column_id' => $newColumnId,
                'from_column_name' => $fromColumnSnapshot !== null ? $fromColumnSnapshot->name : null,
                'to_column_name' => $column->name,
            ]);
        }

        KanbanActivityLogger::log(auth()->user(), 'card_updated', (int) $board->id, [
            'card_id' => $card->id,
            'title' => $card->title,
            'column_id' => $card->kanban_column_id,
            'previous_column_id' => $newColumnId !== $oldColumnId ? $oldColumnId : null,
        ]);
        $this->editCardTitle = $card->title;
        $this->editCardDescription = (string) ($card->description ?? '');
        $this->editCardColumnId = $card->kanban_column_id;
        $this->editCardImportance = ($card->importance ?? KanbanCardImportance::Normal)->value;
        $this->editCardDueAtLocal = $card->due_at !== null
            ? $card->due_at->timezone('Europe/Moscow')->format('Y-m-d\TH:i')
            : '';
    }

    public function closeCardDetailModal(): void
    {
        $this->showCardDetailModal = false;
        $this->detailCardId = null;
        $this->resetCardDetailForm();
    }

    public function updatedShowCardDetailModal(bool $value): void
    {
        if (! $value) {
            $this->detailCardId = null;
            $this->resetCardDetailForm();
        }
    }

    private function resetCardDetailForm(): void
    {
        $this->editCardTitle = '';
        $this->editCardDescription = '';
        $this->editCardColumnId = null;
        $this->editCardImportance = KanbanCardImportance::Normal->value;
        $this->editCardDueAtLocal = '';
        $this->editCardVisibilityMode = KanbanVisibilityMode::Inherit->value;
        $this->editCardGrantRows = [];
        $this->newCardCommentBody = '';
    }

    private function hydrateCardVisibilityForm(KanbanCard $card): void
    {
        $mode = $card->visibility_mode instanceof KanbanVisibilityMode
            ? $card->visibility_mode
            : KanbanVisibilityMode::tryFrom((string) $card->visibility_mode) ?? KanbanVisibilityMode::Inherit;
        $this->editCardVisibilityMode = $mode->value;
        $this->editCardGrantRows = [];
        foreach ($card->grants as $grant) {
            if ((string) $grant->grantee_type !== User::class) {
                continue;
            }
            $this->editCardGrantRows[] = [
                'grantee_kind' => 'user',
                'grantee_id' => (int) $grant->grantee_id,
                'access_level' => $grant->access_level instanceof KanbanAccessLevel
                    ? $grant->access_level->value
                    : (string) $grant->access_level,
            ];
        }
    }

    public function addCardGrantRow(): void
    {
        $this->editCardGrantRows[] = [
            'grantee_kind' => 'user',
            'grantee_id' => 0,
            'access_level' => KanbanAccessLevel::Viewer->value,
        ];
    }

    public function removeCardGrantRow(int $index): void
    {
        if (isset($this->editCardGrantRows[$index])) {
            unset($this->editCardGrantRows[$index]);
            $this->editCardGrantRows = array_values($this->editCardGrantRows);
        }
    }

    public function saveCardAccess(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->detailCardId === null) {
            return;
        }

        $card = KanbanCard::query()
            ->whereKey($this->detailCardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $board->id))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        $this->validate([
            'editCardVisibilityMode' => ['required', Rule::enum(KanbanVisibilityMode::class)],
            'editCardGrantRows' => ['array'],
            'editCardGrantRows.*.grantee_kind' => ['required', Rule::in(['user'])],
            'editCardGrantRows.*.grantee_id' => ['required', 'integer', 'min:1'],
            'editCardGrantRows.*.access_level' => ['required', Rule::enum(KanbanAccessLevel::class)],
        ]);

        $mode = KanbanVisibilityMode::from((string) $this->editCardVisibilityMode);
        if ($mode === KanbanVisibilityMode::Custom && $this->editCardGrantRows !== []) {
            $seen = [];
            foreach ($this->editCardGrantRows as $i => $row) {
                $k = $row['grantee_kind'].':'.$row['grantee_id'];
                if (isset($seen[$k])) {
                    $this->addError('editCardGrantRows', 'Дублируется один и тот же получатель.');

                    return;
                }
                $seen[$k] = true;
                if ((int) $row['grantee_id'] < 1) {
                    $this->addError('editCardGrantRows.'.$i.'.grantee_id', 'Выберите пользователя или роль.');

                    return;
                }
                if ($row['grantee_kind'] === 'user' && ! User::query()->whereKey($row['grantee_id'])->exists()) {
                    $this->addError('editCardGrantRows.'.$i.'.grantee_id', 'Пользователь не найден.');

                    return;
                }
            }
        }

        $card->update([
            'visibility_mode' => $mode,
            'visibility_set_by_user_id' => $mode === KanbanVisibilityMode::Custom ? auth()->id() : null,
        ]);

        if ($mode === KanbanVisibilityMode::Inherit) {
            $card->grants()->delete();
        } else {
            $this->replaceSubjectGrants($card, $this->editCardGrantRows);
        }

        $card->refresh();
        $this->hydrateCardVisibilityForm($card);

        KanbanActivityLogger::log(auth()->user(), 'card_visibility_updated', (int) $board->id, [
            'card_id' => $card->id,
            'mode' => $mode->value,
        ]);
    }

    /**
     * @param  list<array{grantee_kind: string, grantee_id: int, access_level: string}>  $rows
     */
    private function replaceSubjectGrants(KanbanColumn|KanbanCard $subject, array $rows): void
    {
        $subject->grants()->delete();
        foreach ($rows as $row) {
            if (($row['grantee_kind'] ?? '') !== 'user') {
                continue;
            }
            KanbanAccessGrant::query()->create([
                'subject_type' => $subject::class,
                'subject_id' => $subject->id,
                'grantee_type' => User::class,
                'grantee_id' => (int) $row['grantee_id'],
                'access_level' => KanbanAccessLevel::from((string) $row['access_level']),
            ]);
        }
    }

    private function kanbanAccess(): KanbanAccessService
    {
        return app(KanbanAccessService::class);
    }

    private function canDragKanbanCards(User $user, ?KanbanBoard $board): bool
    {
        if ($board === null) {
            return false;
        }

        $access = $this->kanbanAccess();
        foreach ($board->columns as $col) {
            foreach ($col->cards as $card) {
                if (! $access->canEditCard($user, $card)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function canReorderKanbanColumns(User $user, ?KanbanBoard $board): bool
    {
        if ($board === null) {
            return false;
        }

        $access = $this->kanbanAccess();
        if (! $access->canEditBoard($user, $board)) {
            return false;
        }

        $total = KanbanColumn::query()->where('kanban_board_id', $board->id)->count();

        return $total === $board->columns->count();
    }

    #[Computed]
    public function detailCard(): ?KanbanCard
    {
        if ($this->detailCardId === null || $this->boardId === null) {
            return null;
        }

        $boardId = $this->boardId;

        $card = KanbanCard::query()
            ->whereKey($this->detailCardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $boardId))
            ->with(['column.board', 'comments.user', 'grants', 'column.grants'])
            ->withCount('attachments')
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canViewCard(auth()->user(), $card)) {
            return null;
        }

        return $card;
    }

    public function deleteCard(int $id): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null) {
            return;
        }

        $boardId = $board->id;
        $card = KanbanCard::query()
            ->whereKey($id)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $boardId))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        KanbanActivityLogger::log(auth()->user(), 'card_deleted', (int) $board->id, [
            'card_id' => $card->id,
            'title' => $card->title,
            'column_id' => $card->kanban_column_id,
        ]);

        $card->delete();
    }

    public function deleteColumn(int $id): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null) {
            return;
        }

        $column = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKey($id)
            ->with('grants')
            ->first();
        if ($column === null || ! $this->kanbanAccess()->canEditColumn(auth()->user(), $column)) {
            return;
        }

        if (KanbanColumn::query()->where('kanban_board_id', $board->id)->count() <= 1) {
            return;
        }

        $target = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKeyNot($id)
            ->orderBy('position')
            ->first();
        if ($target === null) {
            return;
        }

        KanbanActivityLogger::log(auth()->user(), 'column_deleted', (int) $board->id, [
            'column_id' => $column->id,
            'column_name' => $column->name,
            'cards_moved_to_column_id' => $target->id,
            'cards_moved_to_column_name' => $target->name,
        ]);

        DB::transaction(function () use ($column, $target): void {
            $base = (int) $target->cards()->max('position');
            $column->cards()->orderBy('position')->each(function (KanbanCard $card) use ($target, &$base): void {
                $base++;
                $card->update([
                    'kanban_column_id' => $target->id,
                    'position' => $base,
                ]);
            });
            $column->delete();
        });

        $this->syncNewCardColumnFromBoard();
    }

    public function deleteBoard(?int $targetId = null): void
    {
        $idToDelete = $targetId ?? $this->boardId;
        if ($idToDelete === null) {
            return;
        }

        $board = KanbanBoard::query()
            ->where('user_id', auth()->id())
            ->whereKey($idToDelete)
            ->first();
        if ($board === null) {
            return;
        }

        KanbanActivityLogger::log(auth()->user(), 'board_deleted', (int) $board->id, [
            'board_name' => $board->name,
        ]);

        $wasCurrent = (int) $this->boardId === (int) $idToDelete;
        $board->delete();

        if ($wasCurrent) {
            $user = auth()->user();
            $nextId = $this->firstAccessibleBoardId($user);
            $this->boardId = $nextId;
            if ($this->boardId === null) {
                $this->mount();
            }
        }

        $this->syncNewCardColumnFromBoard();
    }

    private function syncNewCardColumnFromBoard(): void
    {
        $this->newCardColumnId = $this->resolveBoard()?->columns->first()?->id;
    }

    private function resolveBoard(): ?KanbanBoard
    {
        if ($this->boardId === null) {
            return null;
        }

        $user = auth()->user();
        $access = $this->kanbanAccess();

        $board = KanbanBoard::query()
            ->forUserAccess($user)
            ->with([
                'columns' => static fn ($q) => $q->orderBy('position')->with([
                    'grants',
                    'cards' => static fn ($q2) => $q2->with(['grants', 'column.grants'])
                        ->withCount('attachments')
                        ->orderBy('position'),
                ]),
            ])
            ->find($this->boardId);

        if ($board === null) {
            return null;
        }

        $visibleColumns = $board->columns
            ->filter(fn (KanbanColumn $col) => $access->canViewColumn($user, $col))
            ->values();

        foreach ($visibleColumns as $col) {
            $visibleCards = $col->cards
                ->filter(fn (KanbanCard $c) => $access->canViewCard($user, $c))
                ->values();
            $col->setRelation('cards', $visibleCards);
        }

        $board->setRelation('columns', $visibleColumns);

        return $board;
    }

    public function openCardAttachmentsList(int $cardId): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null) {
            return;
        }

        $card = KanbanCard::query()
            ->whereKey($cardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $board->id))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canViewCard(auth()->user(), $card)) {
            return;
        }

        $this->attachmentsContextCardId = $cardId;
        $this->resetErrorBag();
        $this->showCardAttachmentsListModal = true;
    }

    public function closeCardAttachmentsListModal(): void
    {
        $this->showCardAttachmentsListModal = false;
        $this->showCardAttachmentUploadModal = false;
        $this->attachmentsContextCardId = null;
    }

    public function updatedShowCardAttachmentsListModal(bool $value): void
    {
        if (! $value) {
            $this->attachmentsContextCardId = null;
            $this->showCardAttachmentUploadModal = false;
        }
    }

    public function openCardAttachmentUploadModal(): void
    {
        if ($this->attachmentsContextCardId === null) {
            return;
        }

        $this->resetValidation('attachmentUpload');
        $this->attachmentUpload = null;
        $this->showCardAttachmentUploadModal = true;
    }

    public function closeCardAttachmentUploadModal(): void
    {
        $this->showCardAttachmentUploadModal = false;
        $this->attachmentUpload = null;
        $this->resetValidation('attachmentUpload');
    }

    public function updatedShowCardAttachmentUploadModal(bool $value): void
    {
        if (! $value) {
            $this->attachmentUpload = null;
            $this->resetValidation('attachmentUpload');
        }
    }

    public function storeCardAttachment(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->attachmentsContextCardId === null) {
            return;
        }

        $validated = $this->validate([
            'attachmentUpload' => ['required', 'file', 'max:20480'],
        ]);

        $card = KanbanCard::query()
            ->whereKey($this->attachmentsContextCardId)
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $board->id))
            ->with(['grants', 'column.grants'])
            ->first();
        if ($card === null || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['attachmentUpload'];
        $path = $file->store('kanban_card_attachments', 'local');
        if ($path === false) {
            $this->addError('attachmentUpload', 'Не удалось сохранить файл.');

            return;
        }

        KanbanCardAttachment::query()->create([
            'kanban_card_id' => $card->id,
            'user_id' => (int) auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'local',
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
        ]);

        $this->attachmentUpload = null;
        $this->resetValidation('attachmentUpload');
        $this->showCardAttachmentUploadModal = false;
    }

    public function deleteCardAttachment(int $attachmentId): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->attachmentsContextCardId === null) {
            return;
        }

        $attachment = KanbanCardAttachment::query()
            ->whereKey($attachmentId)
            ->where('kanban_card_id', $this->attachmentsContextCardId)
            ->first();
        if ($attachment === null) {
            return;
        }

        $attachment->loadMissing(['card.column', 'card.grants', 'card.column.grants']);
        $card = $attachment->card;
        if ($card === null || (int) $card->column->kanban_board_id !== (int) $board->id
            || ! $this->kanbanAccess()->canEditCard(auth()->user(), $card)) {
            return;
        }

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    public function openShareModal(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canViewBoard(auth()->user(), $board)) {
            return;
        }

        $this->shareSelectedUserId = null;
        $canEditBoard = $this->kanbanAccess()->canEditBoard(auth()->user(), $board);
        $this->shareUserAccessLevel = $canEditBoard
            ? KanbanAccessLevel::Editor->value
            : KanbanAccessLevel::Viewer->value;
        $this->resetErrorBag();
        $this->showShareModal = true;
    }

    public function closeShareModal(): void
    {
        $this->showShareModal = false;
        $this->shareSelectedUserId = null;
    }

    public function addBoardShare(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canViewBoard(auth()->user(), $board) || $this->shareSelectedUserId === null) {
            return;
        }

        $uid = (int) $this->shareSelectedUserId;
        if ($uid === (int) auth()->id()) {
            return;
        }

        if (! User::query()->whereKey($uid)->exists()) {
            return;
        }

        if ($board->sharedUsers()->where('users.id', $uid)->exists()) {
            return;
        }

        $canEditBoard = $this->kanbanAccess()->canEditBoard(auth()->user(), $board);
        if ($canEditBoard) {
            $this->validate([
                'shareUserAccessLevel' => ['required', Rule::enum(KanbanAccessLevel::class)],
            ]);
            $level = KanbanAccessLevel::from((string) $this->shareUserAccessLevel);
        } else {
            $level = KanbanAccessLevel::Viewer;
        }

        $board->sharedUsers()->attach($uid, ['access_level' => $level->value]);

        KanbanActivityLogger::log(auth()->user(), 'board_share_added', (int) $board->id, [
            'shared_user_id' => $uid,
            'access_level' => $level->value,
        ]);

        $this->shareSelectedUserId = null;
    }

    public function setBoardUserShareLevel(int $userId, string $levelValue): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canEditBoard(auth()->user(), $board)) {
            return;
        }

        if ($userId === (int) auth()->id()) {
            return;
        }

        $level = KanbanAccessLevel::from($levelValue);
        if (! $board->sharedUsers()->where('users.id', $userId)->exists()) {
            return;
        }

        $board->sharedUsers()->updateExistingPivot($userId, ['access_level' => $level->value]);
    }

    public function removeBoardShare(int $userId): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canEditBoard(auth()->user(), $board)) {
            return;
        }

        if ($userId === (int) auth()->id()) {
            return;
        }

        $board->sharedUsers()->detach($userId);

        KanbanActivityLogger::log(auth()->user(), 'board_share_removed', (int) $board->id, [
            'shared_user_id' => $userId,
        ]);
    }

    public function openColumnAccessModal(int $columnId): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canEditBoard(auth()->user(), $board)) {
            return;
        }

        $column = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKey($columnId)
            ->with('grants')
            ->first();
        if ($column === null) {
            return;
        }

        $this->columnAccessColumnId = $columnId;
        $mode = $column->visibility_mode instanceof KanbanVisibilityMode
            ? $column->visibility_mode
            : KanbanVisibilityMode::tryFrom((string) $column->visibility_mode) ?? KanbanVisibilityMode::Inherit;
        $this->columnAccessMode = $mode->value;
        $this->columnAccessGrantRows = [];
        foreach ($column->grants as $grant) {
            if ((string) $grant->grantee_type !== User::class) {
                continue;
            }
            $this->columnAccessGrantRows[] = [
                'grantee_kind' => 'user',
                'grantee_id' => (int) $grant->grantee_id,
                'access_level' => $grant->access_level instanceof KanbanAccessLevel
                    ? $grant->access_level->value
                    : (string) $grant->access_level,
            ];
        }
        $this->resetErrorBag();
        $this->showColumnAccessModal = true;
    }

    public function closeColumnAccessModal(): void
    {
        $this->showColumnAccessModal = false;
        $this->columnAccessColumnId = null;
        $this->columnAccessGrantRows = [];
        $this->columnAccessMode = KanbanVisibilityMode::Inherit->value;
    }

    public function addColumnAccessGrantRow(): void
    {
        $this->columnAccessGrantRows[] = [
            'grantee_kind' => 'user',
            'grantee_id' => 0,
            'access_level' => KanbanAccessLevel::Viewer->value,
        ];
    }

    public function removeColumnAccessGrantRow(int $index): void
    {
        if (isset($this->columnAccessGrantRows[$index])) {
            unset($this->columnAccessGrantRows[$index]);
            $this->columnAccessGrantRows = array_values($this->columnAccessGrantRows);
        }
    }

    public function saveColumnAccess(): void
    {
        $board = KanbanBoard::query()->forUserAccess(auth()->user())->find($this->boardId);
        if ($board === null || $this->columnAccessColumnId === null
            || ! $this->kanbanAccess()->canEditBoard(auth()->user(), $board)) {
            return;
        }

        $column = KanbanColumn::query()
            ->where('kanban_board_id', $board->id)
            ->whereKey($this->columnAccessColumnId)
            ->first();
        if ($column === null) {
            return;
        }

        $this->validate([
            'columnAccessMode' => ['required', Rule::enum(KanbanVisibilityMode::class)],
            'columnAccessGrantRows' => ['array'],
            'columnAccessGrantRows.*.grantee_kind' => ['required', Rule::in(['user'])],
            'columnAccessGrantRows.*.grantee_id' => ['required', 'integer', 'min:1'],
            'columnAccessGrantRows.*.access_level' => ['required', Rule::enum(KanbanAccessLevel::class)],
        ]);

        $mode = KanbanVisibilityMode::from((string) $this->columnAccessMode);
        if ($mode === KanbanVisibilityMode::Custom && $this->columnAccessGrantRows !== []) {
            $seen = [];
            foreach ($this->columnAccessGrantRows as $i => $row) {
                $k = $row['grantee_kind'].':'.$row['grantee_id'];
                if (isset($seen[$k])) {
                    $this->addError('columnAccessGrantRows', 'Дублируется один и тот же получатель.');

                    return;
                }
                $seen[$k] = true;
                if ((int) $row['grantee_id'] < 1) {
                    $this->addError('columnAccessGrantRows.'.$i.'.grantee_id', 'Выберите пользователя или роль.');

                    return;
                }
                if ($row['grantee_kind'] === 'user' && ! User::query()->whereKey($row['grantee_id'])->exists()) {
                    $this->addError('columnAccessGrantRows.'.$i.'.grantee_id', 'Пользователь не найден.');

                    return;
                }
            }
        }

        $column->update([
            'visibility_mode' => $mode,
            'visibility_set_by_user_id' => $mode === KanbanVisibilityMode::Custom ? auth()->id() : null,
        ]);

        if ($mode === KanbanVisibilityMode::Inherit) {
            $column->grants()->delete();
        } else {
            $this->replaceSubjectGrants($column, $this->columnAccessGrantRows);
        }

        KanbanActivityLogger::log(auth()->user(), 'column_visibility_updated', (int) $board->id, [
            'column_id' => $column->id,
            'mode' => $mode->value,
        ]);

        $this->closeColumnAccessModal();
    }

    /**
     * @return list<array{id: int, name: string, email: string|null}>
     */
    private function shareableUsersList(): array
    {
        $board = KanbanBoard::query()->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canViewBoard(auth()->user(), $board)) {
            return [];
        }

        $exclude = array_values(array_unique(array_merge(
            [(int) auth()->id()],
            [(int) $board->user_id],
            $board->sharedUsers()->pluck('users.id')->all(),
        )));

        return User::query()
            ->whereNotIn('id', $exclude)
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name !== '' && $u->name !== null ? $u->name : (string) $u->email,
                'email' => $u->email,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, email: string|null, access_level: string}>
     */
    private function boardSharedUsersList(): array
    {
        $board = KanbanBoard::query()
            ->with([
                'sharedUsers' => static fn ($q) => $q->orderBy('name')->orderBy('email'),
            ])
            ->find($this->boardId);
        if ($board === null || ! $this->kanbanAccess()->canViewBoard(auth()->user(), $board)) {
            return [];
        }

        return $board->sharedUsers
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name !== '' && $u->name !== null ? $u->name : (string) $u->email,
                'email' => $u->email,
                'access_level' => (string) ($u->pivot->access_level ?? KanbanAccessLevel::Editor->value),
            ])
            ->values()
            ->all();
    }

    private function firstAccessibleBoardId(User $user): ?int
    {
        $owned = $user->kanbanBoards()->orderBy('position')->orderBy('name')->first();
        if ($owned !== null) {
            return (int) $owned->id;
        }

        $other = $this->orderedSharedBoardsFor($user)->first();

        return $other?->id !== null ? (int) $other->id : null;
    }

    /**
     * @return Collection<int, KanbanBoard>
     */
    private function orderedSharedBoardsFor(User $user): Collection
    {
        $boards = KanbanBoard::query()
            ->forUserAccess($user)
            ->where('user_id', '!=', $user->id)
            ->with('user:id,name,email')
            ->get();

        if ($boards->isEmpty()) {
            return $boards;
        }

        $positions = KanbanUserSharedBoardOrder::query()
            ->where('user_id', $user->id)
            ->whereIn('kanban_board_id', $boards->pluck('id'))
            ->pluck('position', 'kanban_board_id')
            ->map(static fn ($p) => (int) $p);

        return $boards
            ->sortBy(function (KanbanBoard $b) use ($positions): string {
                $pos = $positions[$b->id] ?? PHP_INT_MAX;

                return sprintf('%020d|%s', $pos, $b->name);
            })
            ->values();
    }

    public function render(): View
    {
        $user = auth()->user();
        $boardResolved = $this->resolveBoard();

        $attachmentsContextCard = null;
        if ($this->attachmentsContextCardId !== null && $this->boardId !== null) {
            $attachmentsContextCard = KanbanCard::query()
                ->whereKey($this->attachmentsContextCardId)
                ->whereHas('column', fn ($q) => $q->where('kanban_board_id', $this->boardId))
                ->with(['attachments.uploadedBy', 'grants', 'column.grants', 'column.board'])
                ->first();
        }

        return view('livewire.kanban.kanban-workspace', [
            'board' => $boardResolved,
            'canDragKanbanCards' => $this->canDragKanbanCards($user, $boardResolved),
            'canReorderKanbanColumns' => $this->canReorderKanbanColumns($user, $boardResolved),
            'ownedBoards' => $user->kanbanBoards()
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
            'sharedBoards' => $this->orderedSharedBoardsFor($user),
            'shareableUsers' => $this->shareableUsersList(),
            'boardSharedUsers' => $this->boardSharedUsersList(),
            'shareableRoles' => [],
            'boardSharedRoles' => [],
            'kanbanAccess' => $this->kanbanAccess(),
            'allUsersForGrants' => User::query()->orderBy('name')->orderBy('email')->get(['id', 'name', 'email']),
            'allRolesForGrants' => collect(),
            'attachmentsContextCard' => $attachmentsContextCard,
        ]);
    }
}
