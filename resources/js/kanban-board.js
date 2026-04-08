import Sortable from 'sortablejs';
import axios from 'axios';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

function collectColumnsPayload(root) {
    const columns = {};
    root.querySelectorAll('[data-kanban-column]').forEach((col) => {
        const id = col.getAttribute('data-kanban-column');
        const ids = [...col.querySelectorAll('[data-kanban-card]')].map((el) =>
            parseInt(el.getAttribute('data-kanban-card'), 10),
        );
        columns[id] = ids;
    });

    return columns;
}

function collectColumnOrder(root) {
    return [...root.querySelectorAll('[data-kanban-column]')].map((el) =>
        parseInt(el.getAttribute('data-kanban-column'), 10),
    );
}

async function postBoardSync(root, syncUrl) {
    const columns = collectColumnsPayload(root);
    const canReorderColumns = root.getAttribute('data-kanban-can-reorder-columns') === '1';
    const body = canReorderColumns
        ? { columns, column_order: collectColumnOrder(root) }
        : { columns };
    await axios.post(syncUrl, body, { headers: { Accept: 'application/json' } });
}

function showSyncError(error) {
    const message =
        error?.response?.data?.message
        || (Array.isArray(error?.response?.data?.errors?.columns)
            ? error.response.data.errors.columns[0]
            : null)
        || error?.message
        || 'Ошибка при сохранении порядка';
    window.alert(message);
}

function destroySortables(root) {
    root.querySelectorAll('[data-kanban-cards]').forEach((list) => {
        if (list._kanbanSortable) {
            list._kanbanSortable.destroy();
            list._kanbanSortable = null;
        }
    });

    const colsWrap = root.querySelector('[data-kanban-columns]');
    if (colsWrap?._kanbanColumnsSortable) {
        colsWrap._kanbanColumnsSortable.destroy();
        colsWrap._kanbanColumnsSortable = null;
    }
}

function destroyBoardsSortables() {
    document.querySelectorAll('[data-kanban-boards-owned], [data-kanban-boards-shared]').forEach((el) => {
        if (el._kanbanBoardsSortable) {
            el._kanbanBoardsSortable.destroy();
            el._kanbanBoardsSortable = null;
        }
    });
}

function initKanbanBoardsReorder() {
    document.querySelectorAll('[data-kanban-boards-owned], [data-kanban-boards-shared]').forEach((boardsEl) => {
        const url = boardsEl.getAttribute('data-boards-reorder-url');
        if (! url) {
            return;
        }

        if (boardsEl._kanbanBoardsSortable) {
            boardsEl._kanbanBoardsSortable.destroy();
            boardsEl._kanbanBoardsSortable = null;
        }

        boardsEl._kanbanBoardsSortable = Sortable.create(boardsEl, {
            draggable: '[data-kanban-board-tile]',
            handle: '[data-kanban-board-handle]',
            animation: 150,
            onEnd: async () => {
                const board_ids = [...boardsEl.querySelectorAll('[data-kanban-board-tile]')].map((el) =>
                    parseInt(el.getAttribute('data-kanban-board-tile'), 10),
                );
                try {
                    await axios.post(
                        url,
                        { board_ids },
                        { headers: { Accept: 'application/json' } },
                    );
                } catch (error) {
                    const message =
                        error?.response?.data?.message
                        || error?.message
                        || 'Не удалось сохранить порядок досок';
                    window.alert(message);
                }
            },
        });
    });
}

function initKanbanBoard() {
    destroyBoardsSortables();
    initKanbanBoardsReorder();

    document.querySelectorAll('[data-kanban-root]').forEach((root) => {
        const syncUrl = root.getAttribute('data-sync-url');
        if (! syncUrl) {
            return;
        }

        destroySortables(root);

        const columnsWrap = root.querySelector('[data-kanban-columns]');
        const canReorderColumns = root.getAttribute('data-kanban-can-reorder-columns') === '1';
        if (columnsWrap && canReorderColumns) {
            columnsWrap._kanbanColumnsSortable = Sortable.create(columnsWrap, {
                group: { name: 'kanban-columns', pull: false, put: false },
                draggable: '[data-kanban-column]',
                handle: '[data-kanban-column-handle]',
                animation: 150,
                onEnd: async () => {
                    try {
                        await postBoardSync(root, syncUrl);
                    } catch (error) {
                        showSyncError(error);
                    }
                },
            });
        }

        const canDragCards = root.getAttribute('data-kanban-can-drag-cards') === '1';
        if (canDragCards) {
            root.querySelectorAll('[data-kanban-cards]').forEach((list) => {
                list._kanbanSortable = Sortable.create(list, {
                    group: 'kanban-cards',
                    draggable: '[data-kanban-card]',
                    animation: 150,
                    onEnd: async () => {
                        try {
                            await postBoardSync(root, syncUrl);
                        } catch (error) {
                            showSyncError(error);
                        }
                    },
                });
            });
        }
    });
}

window.initKanbanBoard = initKanbanBoard;

document.addEventListener('DOMContentLoaded', () => initKanbanBoard());
document.addEventListener('livewire:navigated', () => initKanbanBoard());

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(() => initKanbanBoard());
    });
});
