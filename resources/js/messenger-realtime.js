const RETRY_MAX_ATTEMPTS = 100;
const RETRY_DELAY_MS = 100;

window.__messengerRealtimeChannels ??= {};

function normalizeIds(raw) {
    if (!Array.isArray(raw)) {
        return [];
    }

    return [...new Set(
        raw
            .map((id) => Number(id))
            .filter((id) => Number.isInteger(id) && id > 0),
    )];
}

function refreshMessengerLists() {
    if (!window.Livewire) {
        return;
    }
    window.Livewire.dispatch('messenger-conversations-refresh');
}

function subscribeToChannel(conversationId) {
    if (!window.Echo) {
        return;
    }
    if (window.__messengerRealtimeChannels[conversationId]) {
        return;
    }

    const channel = window.Echo.private(`messenger.conversation.${conversationId}`);
    const onUpdate = () => refreshMessengerLists();

    channel
        .listen('.messenger.message.sent', onUpdate)
        .listen('.messenger.read.updated', onUpdate)
        .listen('.messenger.conversation.updated', onUpdate);

    window.__messengerRealtimeChannels[conversationId] = channel;
}

function unsubscribeMissingChannels(expectedIds) {
    const expectedSet = new Set(expectedIds);
    for (const key of Object.keys(window.__messengerRealtimeChannels)) {
        const id = Number(key);
        if (expectedSet.has(id)) {
            continue;
        }
        try {
            window.Echo?.leave(`messenger.conversation.${id}`);
        } catch {
            // noop
        }
        delete window.__messengerRealtimeChannels[key];
    }
}

window.subscribeToAllMessengerChats = function subscribeToAllMessengerChats(chatIds) {
    const ids = normalizeIds(chatIds);
    if (ids.length === 0) {
        return;
    }
    unsubscribeMissingChannels(ids);
    ids.forEach((id) => subscribeToChannel(id));
};

window.subscribeToAllMessengerChatsWhenReady = function subscribeToAllMessengerChatsWhenReady(chatIds) {
    const ids = normalizeIds(chatIds);
    if (ids.length === 0) {
        return;
    }

    let attempts = 0;
    const trySubscribe = () => {
        attempts++;
        if (window.Echo && window.subscribeToAllMessengerChats) {
            window.subscribeToAllMessengerChats(ids);
            return;
        }
        if (attempts < RETRY_MAX_ATTEMPTS) {
            setTimeout(trySubscribe, RETRY_DELAY_MS);
        }
    };

    trySubscribe();
};

document.addEventListener('messenger-chats-loaded', (event) => {
    const detail = event?.detail;
    const chatIds = detail?.chatIds ?? detail ?? [];
    window.subscribeToAllMessengerChatsWhenReady(chatIds);
});

document.addEventListener('livewire:navigated', () => {
    if (!window.Echo) {
        return;
    }
    // Re-attach after DOM navigation; ids come from fresh messenger-chats-loaded events.
    for (const key of Object.keys(window.__messengerRealtimeChannels)) {
        const id = Number(key);
        try {
            window.Echo.leave(`messenger.conversation.${id}`);
        } catch {
            // noop
        }
        delete window.__messengerRealtimeChannels[key];
    }
});
