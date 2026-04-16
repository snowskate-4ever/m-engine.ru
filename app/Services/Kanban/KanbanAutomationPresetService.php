<?php

declare(strict_types=1);

namespace App\Services\Kanban;

use App\Enums\AutomationPresetType;
use App\Models\AutomationPresetSetting;
use App\Models\AutomationRuleExecution;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\SearchRequest;
use App\Models\SearchRequestResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class KanbanAutomationPresetService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(AutomationPresetType $preset, ?Model $subject, array $payload = []): void
    {
        $settings = AutomationPresetSetting::query()
            ->where('preset_type', $preset->value)
            ->where('is_enabled', true)
            ->get();

        // No explicit settings yet -> run sensible default workflow.
        if ($settings->isEmpty()) {
            $this->executeDefault($preset, $subject, $payload);

            return;
        }

        foreach ($settings as $setting) {
            if (! $this->matchesScope($setting, $subject, $payload)) {
                continue;
            }

            $this->executeSingle($preset, $setting, $subject, $payload);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function matchesScope(AutomationPresetSetting $setting, ?Model $subject, array $payload): bool
    {
        $subjectUserId = $this->resolveSubjectUserId($subject, $payload);

        if ($setting->user_id !== null && (int) $setting->user_id !== $subjectUserId) {
            return false;
        }

        if ($setting->owner_type === null || $setting->owner_id === null) {
            return true;
        }

        return collect($this->resolveSubjectOwners($subject))
            ->contains(fn (array $owner): bool => $owner['type'] === (string) $setting->owner_type
                && $owner['id'] === (int) $setting->owner_id);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function resolveSubjectUserId(?Model $subject, array $payload): int
    {
        if ($subject instanceof SearchRequest) {
            return (int) $subject->created_by_user_id;
        }

        if ($subject instanceof SearchRequestResponse) {
            return (int) $subject->searchRequest?->created_by_user_id;
        }

        return (int) Arr::get($payload, 'user_id', 0);
    }

    /**
     * @return array<int,array{type:string,id:int}>
     */
    private function resolveSubjectOwners(?Model $subject): array
    {
        if ($subject instanceof SearchRequest) {
            return array_values(array_filter([
                [
                    'type' => User::class,
                    'id' => (int) $subject->created_by_user_id,
                ],
                $subject->initiator_type !== null && $subject->initiator_id !== null
                    ? [
                        'type' => (string) $subject->initiator_type,
                        'id' => (int) $subject->initiator_id,
                    ]
                    : null,
            ]));
        }

        if ($subject instanceof SearchRequestResponse) {
            $searchRequest = $subject->searchRequest;
            if ($searchRequest === null) {
                return [];
            }

            return $this->resolveSubjectOwners($searchRequest);
        }

        return [];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function executeDefault(AutomationPresetType $preset, ?Model $subject, array $payload): void
    {
        $this->executeSingle($preset, null, $subject, $payload);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function executeSingle(AutomationPresetType $preset, ?AutomationPresetSetting $setting, ?Model $subject, array $payload): void
    {
        DB::beginTransaction();
        try {
            $resultPayload = match ($preset) {
                AutomationPresetType::MyAdsBoard => $this->handleMyAdsBoard($setting, $subject, $payload),
                AutomationPresetType::AdResponseToCard => $this->handleAdResponseToCard($setting, $subject, $payload),
                default => $payload,
            };

            AutomationRuleExecution::query()->create([
                'automation_preset_setting_id' => $setting?->id,
                'trigger_event' => $preset->value,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'is_success' => true,
                'payload' => $resultPayload,
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            AutomationRuleExecution::query()->create([
                'automation_preset_setting_id' => $setting?->id,
                'trigger_event' => $preset->value,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'is_success' => false,
                'payload' => $payload,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function handleMyAdsBoard(?AutomationPresetSetting $setting, ?Model $subject, array $payload): array
    {
        $searchRequest = $subject instanceof SearchRequest ? $subject : null;
        $ownerId = (int) ($searchRequest?->created_by_user_id ?? Arr::get($payload, 'user_id', 0));
        $owner = User::query()->find($ownerId);
        if ($owner === null || $searchRequest === null) {
            return $payload;
        }

        $boardName = (string) (Arr::get($setting?->settings ?? [], 'board_name') ?: 'Объявления');
        $columnName = (string) (Arr::get($setting?->settings ?? [], 'column_name') ?: 'Активные');
        $board = $this->getOrCreateBoard($owner, $boardName);
        $column = $this->getOrCreateColumn($board, $columnName);

        $card = $this->findOrCreateCard(
            $column,
            SearchRequest::class,
            (int) $searchRequest->id,
            (string) ($searchRequest->description ?: 'Объявление #'.$searchRequest->id),
            'Объявление: '.$searchRequest->id,
        );

        return array_merge($payload, [
            'kanban_board_id' => $board->id,
            'kanban_column_id' => $column->id,
            'kanban_card_id' => $card->id,
        ]);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function handleAdResponseToCard(?AutomationPresetSetting $setting, ?Model $subject, array $payload): array
    {
        $response = $subject instanceof SearchRequestResponse ? $subject : null;
        $searchRequest = $response?->searchRequest;
        $ownerId = (int) ($searchRequest?->created_by_user_id ?? 0);
        $owner = User::query()->find($ownerId);
        if ($owner === null || $response === null || $searchRequest === null) {
            return $payload;
        }

        $boardName = (string) (Arr::get($setting?->settings ?? [], 'board_name') ?: 'Объявления');
        $columnName = (string) (Arr::get($setting?->settings ?? [], 'column_name') ?: 'Новые отклики');
        $board = $this->getOrCreateBoard($owner, $boardName);
        $column = $this->getOrCreateColumn($board, $columnName);

        $card = $this->findOrCreateCard(
            $column,
            SearchRequestResponse::class,
            (int) $response->id,
            'Отклик на объявление #'.$searchRequest->id,
            (string) ($response->message ?: 'Новый отклик'),
        );

        return array_merge($payload, [
            'kanban_board_id' => $board->id,
            'kanban_column_id' => $column->id,
            'kanban_card_id' => $card->id,
        ]);
    }

    private function getOrCreateBoard(User $owner, string $name): KanbanBoard
    {
        return KanbanBoard::query()->firstOrCreate(
            ['user_id' => $owner->id, 'name' => $name],
            ['position' => (int) KanbanBoard::query()->where('user_id', $owner->id)->count()]
        );
    }

    private function getOrCreateColumn(KanbanBoard $board, string $name): KanbanColumn
    {
        return KanbanColumn::query()->firstOrCreate(
            ['kanban_board_id' => $board->id, 'name' => $name],
            ['position' => (int) $board->columns()->count()]
        );
    }

    private function findOrCreateCard(
        KanbanColumn $column,
        string $sourceType,
        int $sourceId,
        string $title,
        string $description
    ): KanbanCard {
        $existing = KanbanCard::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return KanbanCard::query()->create([
            'kanban_column_id' => $column->id,
            'title' => $title,
            'description' => $description,
            'position' => (int) $column->cards()->count(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }
}
