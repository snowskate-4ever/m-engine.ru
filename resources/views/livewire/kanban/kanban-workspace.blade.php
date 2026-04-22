<div class="flex min-h-0 flex-1 flex-col gap-4 p-4">
    @if ($board === null)
        <flux:heading size="xl">Канбан</flux:heading>
        <flux:callout variant="warning">Доска не найдена.</flux:callout>
    @else
        @php
            $canFullyManageBoardShares = $kanbanAccess->canEditBoard(auth()->user(), $board);
        @endphp

        <div class="flex flex-wrap items-center justify-start gap-2">
            <flux:heading size="xl" class="min-w-0">{{ $board->name }}</flux:heading>
            @if ($kanbanAccess->canViewBoard(auth()->user(), $board))
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="users"
                    class="shrink-0"
                    title="Наблюдатели и редакторы доски"
                    wire:click="openShareModal"
                >
                    Доступ
                </flux:button>
            @endif
            <flux:button
                type="button"
                variant="primary"
                size="sm"
                square
                icon="plus"
                class="ms-[3px] shrink-0 !rounded-lg [&>svg]:ml-0 [&_[data-flux-loading-indicator]_svg]:ml-px"
                title="Добавить доску, колонку или карточку"
                wire:click="openAddWizardModal"
            />
        </div>

        <div class="space-y-4">
            <div class="space-y-2">
                <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Мои доски</flux:text>
                @if ($ownedBoards->isEmpty())
                    <flux:text variant="subtle" class="text-sm">У вас пока нет своих досок — создайте через «+».</flux:text>
                @else
                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                        data-kanban-boards-owned
                        data-boards-reorder-url="{{ route('kanban.boards.reorder') }}"
                    >
                        @foreach ($ownedBoards as $b)
                            <div
                                wire:key="kanban-board-tile-owned-{{ $b->id }}"
                                data-kanban-board-tile="{{ $b->id }}"
                                class="group relative flex min-h-[5.5rem] flex-col rounded-xl border bg-gradient-to-br from-white to-zinc-50 shadow-sm transition hover:border-zinc-300 hover:shadow-md dark:from-zinc-800 dark:to-zinc-900/90 dark:hover:border-zinc-600 {{ (int) $boardId === (int) $b->id ? 'border-blue-500 ring-2 ring-blue-500/35 dark:border-blue-400' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex min-h-[5.5rem] flex-1">
                                    <button
                                        type="button"
                                        data-kanban-board-handle
                                        class="flex w-6 shrink-0 cursor-grab items-start justify-center rounded-l-xl border-r border-zinc-200/80 bg-zinc-100/80 py-2.5 text-zinc-400 hover:bg-zinc-200/80 hover:text-zinc-600 active:cursor-grabbing dark:border-zinc-600 dark:bg-zinc-900/50 dark:hover:bg-zinc-700/50 dark:hover:text-zinc-300"
                                        title="Перетащить доску"
                                    >
                                        <span class="select-none text-xs leading-none" aria-hidden="true">⠿</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectBoard({{ $b->id }})"
                                        class="flex min-h-[5.5rem] flex-1 flex-col p-3 pr-10 text-left"
                                    >
                                        <span class="line-clamp-2 font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $b->name }}
                                        </span>
                                    </button>
                                </div>
                                <div
                                    class="pointer-events-none z-10"
                                    style="position: absolute; top: 0.5rem; right: 0.5rem; left: auto; z-index: 10;"
                                >
                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        icon="trash"
                                        :loading="false"
                                        class="pointer-events-auto"
                                        title="Удалить доску"
                                        wire:click.stop="deleteBoard({{ $b->id }})"
                                        wire:confirm="Удалить доску «{{ $b->name }}» и все карточки?"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($sharedBoards->isNotEmpty())
                <div class="space-y-2">
                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        Доступные мне (чужие доски)
                    </flux:text>
                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                        data-kanban-boards-shared
                        data-boards-reorder-url="{{ route('kanban.boards.reorder-shared') }}"
                    >
                        @foreach ($sharedBoards as $b)
                            <div
                                wire:key="kanban-board-tile-shared-{{ $b->id }}"
                                data-kanban-board-tile="{{ $b->id }}"
                                class="group relative flex min-h-[5.5rem] flex-col rounded-xl border bg-gradient-to-br from-white to-zinc-50 shadow-sm transition hover:border-zinc-300 hover:shadow-md dark:from-zinc-800 dark:to-zinc-900/90 dark:hover:border-zinc-600 {{ (int) $boardId === (int) $b->id ? 'border-blue-500 ring-2 ring-blue-500/35 dark:border-blue-400' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex min-h-[5.5rem] flex-1">
                                    <button
                                        type="button"
                                        data-kanban-board-handle
                                        class="flex w-6 shrink-0 cursor-grab items-start justify-center rounded-l-xl border-r border-zinc-200/80 bg-zinc-100/80 py-2.5 text-zinc-400 hover:bg-zinc-200/80 hover:text-zinc-600 active:cursor-grabbing dark:border-zinc-600 dark:bg-zinc-900/50 dark:hover:bg-zinc-700/50 dark:hover:text-zinc-300"
                                        title="Перетащить доску (только у вас в списке)"
                                    >
                                        <span class="select-none text-xs leading-none" aria-hidden="true">⠿</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectBoard({{ $b->id }})"
                                        class="flex min-h-[5.5rem] flex-1 flex-col p-3 text-left"
                                    >
                                        <span class="line-clamp-2 font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $b->name }}
                                        </span>
                                        <span class="mt-1 line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $b->user?->name ?? $b->user?->email ?? 'Владелец' }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <flux:modal wire:model="showShareModal" name="kanban-share" class="w-full max-w-lg">
            <flux:heading size="lg">Доступ к доске</flux:heading>
            @if ($canFullyManageBoardShares)
                <flux:subheading class="mt-1">
                    Наблюдатель видит доску и карточки без права правок. Редактор может менять колонки и карточки (в пределах прав на колонку и карточку). Наблюдатели могут приглашать только новых наблюдателей.
                </flux:subheading>
            @else
                <flux:subheading class="mt-1">
                    Вы на доске как наблюдатель: можете добавить коллег с тем же уровнем (только просмотр). Назначать редакторов, роли и убирать участников могут редакторы доски и владелец.
                </flux:subheading>
            @endif

            <div class="mt-4 max-h-[65vh] space-y-5 overflow-y-auto pe-1">
                <div>
                    <flux:text class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">По пользователям</flux:text>
                    @if (count($boardSharedUsers) > 0)
                        <ul class="max-h-44 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-600">
                            @foreach ($boardSharedUsers as $su)
                                <li class="flex flex-col gap-2 rounded-md bg-zinc-50 px-2 py-2 sm:flex-row sm:items-center sm:justify-between dark:bg-zinc-900/60">
                                    <span class="min-w-0 truncate text-sm">
                                        {{ $su['name'] }}
                                        @if (! empty($su['email']))
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $su['email'] }}</span>
                                        @endif
                                    </span>
                                    @if ($canFullyManageBoardShares)
                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <select
                                                class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800"
                                                wire:change="setBoardUserShareLevel({{ $su['id'] }}, $event.target.value)"
                                            >
                                                @foreach (\App\Enums\KanbanAccessLevel::cases() as $lvl)
                                                    <option value="{{ $lvl->value }}" @selected(($su['access_level'] ?? '') === $lvl->value)>{{ $lvl->label() }}</option>
                                                @endforeach
                                            </select>
                                            <flux:button type="button" size="xs" variant="ghost" wire:click="removeBoardShare({{ $su['id'] }})">
                                                Убрать
                                            </flux:button>
                                        </div>
                                    @else
                                        <span class="shrink-0 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ \App\Enums\KanbanAccessLevel::tryFrom((string) ($su['access_level'] ?? ''))?->label() ?? ($su['access_level'] ?? '') }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text variant="subtle" class="text-sm">Пока никого нет — только вы.</flux:text>
                    @endif

                    @if (count($shareableUsers) > 0)
                        @if ($canFullyManageBoardShares)
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Добавить пользователя</label>
                                    <select
                                        wire:model.live="shareSelectedUserId"
                                        class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                                    >
                                        <option value="">Выберите…</option>
                                        @foreach ($shareableUsers as $u)
                                            <option value="{{ $u['id'] }}">{{ $u['name'] }} @if(!empty($u['email'])) ({{ $u['email'] }}) @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="min-w-0 sm:w-40">
                                    <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Уровень</label>
                                    <select wire:model="shareUserAccessLevel" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                        @foreach (\App\Enums\KanbanAccessLevel::cases() as $lvl)
                                            <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <flux:button type="button" wire:click="addBoardShare" :disabled="! $shareSelectedUserId" variant="primary" square icon="plus" :title="__('ui.add')" class="w-full shrink-0 sm:w-auto" />
                            </div>
                        @else
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Пригласить наблюдателя</label>
                                    <select
                                        wire:model.live="shareSelectedUserId"
                                        class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                                    >
                                        <option value="">Выберите…</option>
                                        @foreach ($shareableUsers as $u)
                                            <option value="{{ $u['id'] }}">{{ $u['name'] }} @if(!empty($u['email'])) ({{ $u['email'] }}) @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <flux:button type="button" wire:click="addBoardShare" :disabled="! $shareSelectedUserId" variant="primary" square icon="plus" :title="__('ui.add')" class="w-full shrink-0 sm:w-auto" />
                            </div>
                        @endif
                    @endif
                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <flux:button type="button" variant="ghost" wire:click="closeShareModal">Закрыть</flux:button>
            </div>
        </flux:modal>

        <flux:modal wire:model="showColumnAccessModal" name="kanban-column-access" class="w-full max-w-lg">
            <flux:heading size="lg">Видимость колонки</flux:heading>
            <flux:subheading class="mt-1">
                По умолчанию — как у доски. Режим «Свой список»: укажите пользователей и уровень (наблюдатель или редактор для карточек в этой колонке). Сначала добавьте человека к доске через «Доступ», иначе правила колонки не сработают. Настроивший список всегда видит колонку; без строк в списке — только владелец доски.
            </flux:subheading>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Режим</label>
                    <select wire:model="columnAccessMode" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        @foreach (\App\Enums\KanbanVisibilityMode::cases() as $vm)
                            <option value="{{ $vm->value }}">{{ $vm->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($columnAccessMode === 'custom')
                    @error('columnAccessGrantRows')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                    <div class="space-y-2">
                        @foreach ($columnAccessGrantRows as $i => $row)
                            <div class="flex flex-wrap items-end gap-2 rounded-lg border border-zinc-200 p-2 dark:border-zinc-600" wire:key="col-grant-{{ $i }}">
                                <div class="min-w-0 flex-[2]">
                                    <label class="mb-0.5 block text-xs text-zinc-600 dark:text-zinc-400">Пользователь</label>
                                    <select wire:model="columnAccessGrantRows.{{ $i }}.grantee_id" class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                        <option value="0">—</option>
                                        @foreach ($allUsersForGrants as $u)
                                            <option value="{{ $u->id }}">{{ $u->name ?: $u->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-full min-w-[6rem] sm:w-28">
                                    <label class="mb-0.5 block text-xs text-zinc-600 dark:text-zinc-400">Уровень</label>
                                    <select wire:model="columnAccessGrantRows.{{ $i }}.access_level" class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                        @foreach (\App\Enums\KanbanAccessLevel::cases() as $lvl)
                                            <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <flux:button type="button" size="xs" variant="ghost" wire:click="removeColumnAccessGrantRow({{ $i }})">Удалить</flux:button>
                            </div>
                        @endforeach
                    </div>
                    <flux:button type="button" size="sm" variant="ghost" class="w-full" wire:click="addColumnAccessGrantRow">+ Правило</flux:button>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeColumnAccessModal" square icon="cancel-play" title="Отмена" />
                <flux:button type="button" variant="primary" square wire:click="saveColumnAccess" :title="__('ui.save')" icon="save-floppy" />
            </div>
        </flux:modal>

        <flux:modal wire:model="showAddWizardModal" name="kanban-add-wizard" class="w-full max-w-md">
            @if ($addWizardStep === 'choose')
                <flux:heading size="lg">Добавить</flux:heading>
                <flux:subheading class="mt-1">Выберите, что создать.</flux:subheading>
                <div class="mt-4 flex flex-col gap-2">
                    <flux:button type="button" wire:click="chooseAddWizard('board')" class="w-full justify-center">
                        Новая доска
                    </flux:button>
                    <flux:button type="button" wire:click="chooseAddWizard('column')" class="w-full justify-center">
                        Колонка
                    </flux:button>
                    <flux:button type="button" wire:click="chooseAddWizard('card')" class="w-full justify-center">
                        Карточка
                    </flux:button>
                </div>
                <div class="mt-4 flex justify-end">
                    <flux:button type="button" variant="ghost" wire:click="closeAddWizardModal">Закрыть</flux:button>
                </div>
            @elseif ($addWizardStep === 'board')
                <div class="flex items-center gap-2">
                    <flux:button type="button" variant="ghost" size="sm" icon="chevron-left" wire:click="backAddWizardChoose" />
                    <flux:heading size="lg" class="!mb-0">Новая доска</flux:heading>
                </div>
                <flux:subheading class="mt-1">Задайте название — появятся колонки «К выполнению», «В работе», «Готово».</flux:subheading>
                <form wire:submit="createBoard" class="mt-4 flex flex-col gap-4">
                    <flux:input wire:model="newBoardName" label="Название" placeholder="Например, Продажи Q2" autofocus />
                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" wire:click="backAddWizardChoose">Назад</flux:button>
                        <flux:button type="submit" variant="primary" square icon="plus" title="Создать" />
                    </div>
                </form>
            @elseif ($addWizardStep === 'column')
                <div class="flex items-center gap-2">
                    <flux:button type="button" variant="ghost" size="sm" icon="chevron-left" wire:click="backAddWizardChoose" />
                    <flux:heading size="lg" class="!mb-0">Новая колонка</flux:heading>
                </div>
                <form wire:submit="createColumn" class="mt-4 flex flex-col gap-4">
                    <flux:input wire:model="newColumnName" label="Название колонки" placeholder="Например, Приёмка" autofocus />
                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" wire:click="backAddWizardChoose">Назад</flux:button>
                        <flux:button type="submit" variant="primary" square icon="plus" :title="__('ui.add')" />
                    </div>
                </form>
            @else
                <div class="flex items-center gap-2">
                    <flux:button type="button" variant="ghost" size="sm" icon="chevron-left" wire:click="backAddWizardChoose" />
                    <flux:heading size="lg" class="!mb-0">Новая карточка</flux:heading>
                </div>
                <form wire:submit="createCard" class="mt-4 flex flex-col gap-4">
                    <flux:input wire:model="newCardTitle" label="Текст карточки" placeholder="Задача или заметка" autofocus />
                    <flux:textarea wire:model="newCardDescription" label="Описание" placeholder="Детали, контекст..." rows="4" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Важность</label>
                        <select
                            wire:model="newCardImportance"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                        >
                            @foreach (\App\Enums\KanbanCardImportance::cases() as $imp)
                                <option value="{{ $imp->value }}">{{ $imp->label() }}</option>
                            @endforeach
                        </select>
                        @error('newCardImportance')
                            <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Колонка</label>
                        <select
                            wire:model="newCardColumnId"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                        >
                            @foreach ($board->columns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" wire:click="backAddWizardChoose">Назад</flux:button>
                        <flux:button type="submit" variant="primary" square icon="plus" :title="__('ui.add')" />
                    </div>
                </form>
            @endif
        </flux:modal>

        <flux:modal wire:model="showCardDetailModal" name="kanban-card-detail" class="w-full max-w-2xl">
            @if ($this->detailCard)
                @php
                    $canEditThisCard = $kanbanAccess->canEditCard(auth()->user(), $this->detailCard);
                @endphp
                <flux:heading size="lg" class="pr-8">Карточка</flux:heading>
                <form wire:submit="updateCard" class="mt-4 flex flex-col gap-4">
                    <flux:input
                        wire:model="editCardTitle"
                        label="Название"
                        placeholder="Заголовок карточки"
                        :disabled="! $canEditThisCard"
                    />
                    <flux:textarea
                        wire:model="editCardDescription"
                        label="Описание"
                        placeholder="Подробности, заметки…"
                        rows="5"
                        :disabled="! $canEditThisCard"
                    />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Важность</label>
                        <select
                            wire:model="editCardImportance"
                            @disabled(! $canEditThisCard)
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm disabled:opacity-60 dark:border-zinc-600 dark:bg-zinc-800"
                        >
                            @foreach (\App\Enums\KanbanCardImportance::cases() as $imp)
                                <option value="{{ $imp->value }}">{{ $imp->label() }}</option>
                            @endforeach
                        </select>
                        @error('editCardImportance')
                            <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <flux:input
                        type="datetime-local"
                        wire:model="editCardDueAtLocal"
                        label="Срок (отображается в календаре канбана)"
                        :disabled="! $canEditThisCard"
                    />
                    @error('editCardDueAtLocal')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Колонка</label>
                        <select
                            wire:model="editCardColumnId"
                            @disabled(! $canEditThisCard)
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm disabled:opacity-60 dark:border-zinc-600 dark:bg-zinc-800"
                        >
                            @foreach ($board->columns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }}</option>
                            @endforeach
                        </select>
                        @error('editCardColumnId')
                            <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    @if ($canEditThisCard)
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 text-zinc-900 dark:border-zinc-600 dark:bg-zinc-950/55 dark:text-zinc-100 dark:[color-scheme:dark]">
                            <div class="mb-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">Кто видит карточку</div>
                            <flux:subheading size="sm" class="mb-2 block font-normal">
                                По умолчанию — как колонка и доска. «Свой список»: для каждой строки задайте наблюдателя или редактора. Участник должен уже иметь доступ к доске (кнопка «Доступ»). Настроивший список всегда видит карточку; без строк в списке — только владелец доски.
                            </flux:subheading>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Режим</label>
                                <select wire:model="editCardVisibilityMode" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    @foreach (\App\Enums\KanbanVisibilityMode::cases() as $vm)
                                        <option value="{{ $vm->value }}">{{ $vm->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($editCardVisibilityMode === 'custom')
                                @error('editCardGrantRows')
                                    <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                                @enderror
                                <div class="mt-3 space-y-2">
                                    @foreach ($editCardGrantRows as $i => $row)
                                        <div class="flex flex-wrap items-end gap-2 rounded-md border border-zinc-200 bg-white/70 p-2 dark:border-zinc-700 dark:bg-zinc-900/50" wire:key="card-grant-{{ $i }}">
                                            <div class="min-w-0 flex-[2]">
                                                <label class="mb-0.5 block text-xs text-zinc-600 dark:text-zinc-400">Пользователь</label>
                                                <select wire:model="editCardGrantRows.{{ $i }}.grantee_id" class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <option value="0">—</option>
                                                    @foreach ($allUsersForGrants as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name ?: $u->email }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="w-full min-w-[6rem] sm:w-28">
                                                <label class="mb-0.5 block text-xs text-zinc-600 dark:text-zinc-400">Уровень</label>
                                                <select wire:model="editCardGrantRows.{{ $i }}.access_level" class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                    @foreach (\App\Enums\KanbanAccessLevel::cases() as $lvl)
                                                        <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <flux:button type="button" size="xs" variant="ghost" wire:click="removeCardGrantRow({{ $i }})">Удалить</flux:button>
                                        </div>
                                    @endforeach
                                </div>
                                <flux:button type="button" size="sm" variant="ghost" class="mt-2 w-full" wire:click="addCardGrantRow">+ Правило</flux:button>
                                <flux:button type="button" size="sm" class="mt-2 w-full" wire:click="saveCardAccess">Применить видимость</flux:button>
                            @else
                                <flux:button type="button" size="sm" class="mt-2" variant="primary" square wire:click="saveCardAccess" title="Сохранить режим" icon="save-floppy" />
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-x-6 gap-y-1 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <span>ID: {{ $this->detailCard->id }}</span>
                        @if ($this->detailCard->created_at)
                            <span>
                                Создана: {{ $this->detailCard->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            </span>
                        @endif
                        @if ($this->detailCard->updated_at)
                            <span>
                                Обновлена: {{ $this->detailCard->updated_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            icon="paper-clip"
                            wire:click="openCardAttachmentsList({{ $this->detailCard->id }})"
                        >
                            Вложения
                            @if (($this->detailCard->attachments_count ?? 0) > 0)
                                <span class="ms-0.5">({{ $this->detailCard->attachments_count }})</span>
                            @endif
                        </flux:button>
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button type="button" variant="ghost" wire:click="closeCardDetailModal">Закрыть</flux:button>
                            @if ($canEditThisCard)
                                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                            @endif
                        </div>
                    </div>
                </form>

                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-3">Комментарии</flux:heading>
                    @if ($this->detailCard->comments->isEmpty())
                        <flux:text variant="subtle" class="text-sm">Пока нет комментариев.</flux:text>
                    @else
                        <ul class="max-h-60 space-y-3 overflow-y-auto pe-1">
                            @foreach ($this->detailCard->comments as $comment)
                                <li class="rounded-lg border border-zinc-200 bg-zinc-100/90 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-950/50">
                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                        <flux:text variant="strong" class="text-sm font-medium">
                                            {{ $comment->user?->name ?? 'Пользователь' }}
                                        </flux:text>
                                        <flux:text inline size="sm" variant="subtle">
                                            <time datetime="{{ $comment->created_at->toIso8601String() }}">
                                                {{ $comment->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                            </time>
                                        </flux:text>
                                    </div>
                                    <flux:text variant="strong" class="mt-2 whitespace-pre-wrap font-normal">
                                        {{ $comment->body }}
                                    </flux:text>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($canEditThisCard)
                        <form wire:submit="addCardComment" class="mt-4 flex flex-col gap-2">
                            <flux:textarea
                                wire:model="newCardCommentBody"
                                label="Новый комментарий"
                                placeholder="Напишите комментарий…"
                                rows="3"
                            />
                            @error('newCardCommentBody')
                                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                            @enderror
                            <div class="flex justify-end">
                                <flux:button type="submit" size="sm" square icon="plus" title="Добавить комментарий" />
                            </div>
                        </form>
                    @else
                        <flux:text variant="subtle" class="mt-2 text-sm">Комментирование недоступно в режиме наблюдателя.</flux:text>
                    @endif
                </div>
            @else
                <flux:callout variant="warning">Карточка не найдена.</flux:callout>
                <div class="mt-4 flex justify-end">
                    <flux:button type="button" variant="ghost" wire:click="closeCardDetailModal">Закрыть</flux:button>
                </div>
            @endif
        </flux:modal>

        <flux:modal wire:model="showCardAttachmentsListModal" name="kanban-card-files" class="w-full max-w-lg">
            @if ($attachmentsContextCard)
                @php
                    $canEditAttachmentsCard = $kanbanAccess->canEditCard(auth()->user(), $attachmentsContextCard);
                @endphp
                <flux:heading size="lg">Вложения</flux:heading>
                <flux:subheading class="mt-1 line-clamp-2">{{ $attachmentsContextCard->title }}</flux:subheading>

                <div class="mt-4 space-y-3">
                    @if ($attachmentsContextCard->attachments->isEmpty())
                        <flux:text variant="subtle" class="text-sm">Пока нет файлов.</flux:text>
                    @else
                        <ul class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-600 dark:bg-zinc-900/40">
                            @foreach ($attachmentsContextCard->attachments as $att)
                                <li class="flex items-center justify-between gap-2 rounded-md bg-zinc-50 px-2 py-2 dark:bg-zinc-900/60" wire:key="kanban-att-{{ $att->id }}">
                                    <div class="min-w-0 flex-1">
                                        <a
                                            href="{{ route('kanban.attachments.download', $att) }}"
                                            class="block truncate text-sm font-medium text-blue-600 underline-offset-2 hover:underline dark:text-blue-400"
                                        >
                                            {{ $att->original_name }}
                                        </a>
                                        <flux:text inline size="sm" variant="subtle" class="mt-0.5 block">
                                            @if ($att->uploadedBy)
                                                {{ $att->uploadedBy->name ?? $att->uploadedBy->email }}
                                                ·
                                            @endif
                                            {{ $att->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                            @if ($att->size > 0)
                                                · {{ \Illuminate\Support\Number::fileSize($att->size) }}
                                            @endif
                                        </flux:text>
                                    </div>
                                    @if ($canEditAttachmentsCard)
                                        <flux:button
                                            type="button"
                                            size="xs"
                                            variant="ghost"
                                            class="shrink-0 text-red-600 dark:text-red-400"
                                            wire:click="deleteCardAttachment({{ $att->id }})"
                                            wire:confirm="Удалить этот файл?"
                                        >
                                            Удалить
                                        </flux:button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($canEditAttachmentsCard)
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button type="button" variant="primary" wire:click="openCardAttachmentUploadModal">
                                Прикрепить файл
                            </flux:button>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <flux:button type="button" variant="ghost" wire:click="closeCardAttachmentsListModal">Закрыть</flux:button>
                </div>
            @else
                <flux:callout variant="warning">Карточка не найдена.</flux:callout>
                <div class="mt-4 flex justify-end">
                    <flux:button type="button" variant="ghost" wire:click="closeCardAttachmentsListModal">Закрыть</flux:button>
                </div>
            @endif
        </flux:modal>

        <flux:modal wire:model="showCardAttachmentUploadModal" name="kanban-card-file-upload" class="w-full max-w-md">
            <flux:heading size="lg">Прикрепить файл</flux:heading>
            <flux:subheading class="mt-1">До 20 МБ. Скачивание — у кого есть доступ к карточке.</flux:subheading>

            <form wire:submit="storeCardAttachment" class="mt-4 space-y-4" x-data="{ pickFile() { $refs.kanbanFileInput.click(); } }">
                <input
                    type="file"
                    wire:model="attachmentUpload"
                    x-ref="kanbanFileInput"
                    class="hidden"
                />
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button type="button" variant="ghost" icon="paper-clip" wire:loading.attr="disabled" wire:target="attachmentUpload" @click="pickFile()">
                        Выбрать файл
                    </flux:button>
                    <span wire:loading wire:target="attachmentUpload" class="text-sm text-zinc-500">Загрузка…</span>
                    @if ($attachmentUpload)
                        <flux:text class="min-w-0 flex-1 truncate text-sm">{{ $attachmentUpload->getClientOriginalName() }}</flux:text>
                    @endif
                </div>
                @error('attachmentUpload')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="closeCardAttachmentUploadModal" square icon="cancel-play" title="Отмена" />
                    <flux:button type="submit" wire:loading.attr="disabled" wire:target="storeCardAttachment,attachmentUpload">
                        Загрузить
                    </flux:button>
                </div>
            </form>
        </flux:modal>

        <div
            class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-100/80 dark:bg-zinc-900/50 p-3 overflow-x-auto h-[638px]"
            data-kanban-root
            data-sync-url="{{ route('kanban.sync', $board) }}"
            data-kanban-can-drag-cards="{{ $canDragKanbanCards ? '1' : '0' }}"
            data-kanban-can-reorder-columns="{{ $canReorderKanbanColumns ? '1' : '0' }}"
        >
            <div class="flex gap-4 min-h-[28rem] items-start" data-kanban-columns>
                @foreach ($board->columns as $column)
                    <section
                        class="flex-shrink-0 w-[275px] flex flex-col max-h-[calc(100vh-14rem)] rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm"
                        data-kanban-column="{{ $column->id }}"
                    >
                        <header class="flex items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-700 ps-1 pe-3 py-1.5">
                            <div
                                class="flex min-w-0 flex-1 items-center gap-1 {{ $canReorderKanbanColumns ? '' : 'cursor-default' }}"
                                @if ($canReorderKanbanColumns)
                                    data-kanban-column-handle
                                    title="Перетащить колонку"
                                @endif
                            >
                                @if ($canReorderKanbanColumns)
                                    <span
                                        class="shrink-0 cursor-grab select-none px-1 text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:text-zinc-500 dark:hover:text-zinc-300"
                                        aria-hidden="true"
                                    >⠿</span>
                                @endif
                                <h3 class="truncate font-semibold text-sm">{{ $column->name }}</h3>
                            </div>
                            <div class="flex shrink-0 items-center gap-0.5">
                                @if ($kanbanAccess->canEditBoard(auth()->user(), $board))
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        icon="eye"
                                        class="!px-1"
                                        title="Видимость колонки"
                                        wire:click="openColumnAccessModal({{ $column->id }})"
                                    ></flux:button>
                                @endif
                                @if ($board->columns->count() > 1 && $kanbanAccess->canEditColumn(auth()->user(), $column))
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        wire:click="deleteColumn({{ $column->id }})"
                                        wire:confirm="Удалить колонку? Карточки будут перенесены в другую колонку."
                                    >
                                        ×
                                    </flux:button>
                                @endif
                            </div>
                        </header>
                        <div
                            class="flex-1 overflow-y-auto p-2 space-y-2 kanban-cards-dropzone"
                            data-kanban-cards
                        >
                            @foreach ($column->cards as $card)
                                @php
                                    $cardImp = $card->importance ?? \App\Enums\KanbanCardImportance::Normal;
                                    $isArchiveBoardForViewer =
                                        $board->source_type === \App\Models\User::class
                                        && (int) $board->source_id === (int) auth()->id()
                                        && $board->name === 'Архив';
                                @endphp
                                <div
                                    data-kanban-card="{{ $card->id }}"
                                    wire:key="kanban-card-{{ $card->id }}"
                                    wire:dblclick="openCardDetail({{ $card->id }})"
                                    class="rounded-md border p-2 shadow-xs {{ $canDragKanbanCards ? 'cursor-grab active:cursor-grabbing' : 'cursor-default' }} {{ $cardImp->boardCardClasses() }}"
                                >
                                    <div class="flex justify-between gap-2 items-start">
                                        <p class="text-sm leading-snug">{{ $card->title }}</p>
                                        <div class="flex shrink-0 items-start gap-0">
                                            @if ($kanbanAccess->canEditCard(auth()->user(), $card) && ! $isArchiveBoardForViewer)
                                                <flux:button
                                                    type="button"
                                                    size="xs"
                                                    variant="ghost"
                                                    class="!px-1 shrink-0"
                                                    title="Отправить в архив"
                                                    wire:click.stop="archiveCard({{ $card->id }})"
                                                >
                                                    Архив
                                                </flux:button>
                                            @endif
                                            <flux:button
                                                type="button"
                                                size="xs"
                                                variant="ghost"
                                                icon="paper-clip"
                                                class="!px-1"
                                                title="Вложения{{ ($card->attachments_count ?? 0) > 0 ? ' ('.$card->attachments_count.')' : '' }}"
                                                wire:click.stop="openCardAttachmentsList({{ $card->id }})"
                                            ></flux:button>
                                            @if ($kanbanAccess->canEditCard(auth()->user(), $card))
                                                <flux:button
                                                    type="button"
                                                    size="xs"
                                                    variant="ghost"
                                                    class="!px-1 shrink-0"
                                                    wire:click.stop="deleteCard({{ $card->id }})"
                                                    wire:confirm="Удалить карточку?"
                                                >
                                                    ×
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-zinc-500">
            Потяните за полосы слева у своих досок или у заголовков колонок, чтобы менять порядок. Карточки перетаскивайте внутри колонки и между колонками — порядок сохраняется автоматически. Наблюдателей и редакторов доски добавляйте через «Доступ»; для колонки — иконка глаза в заголовке; для карточки — блок «Кто видит карточку» в карточке (двойной щелчок).
        </p>
    @endif
</div>
