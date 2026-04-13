package ru.mengine.mobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import ru.mengine.mobile.data.MusicRepository
import ru.mengine.mobile.data.remote.dto.MessengerConversationDto
import ru.mengine.mobile.data.remote.dto.MessengerMessageDto
import ru.mengine.mobile.data.remote.dto.UserProfileDto

data class MessengerConversationUi(
    val id: Int,
    val type: String,
    val title: String,
    val unreadCount: Int,
)

data class MessengerUiState(
    val loading: Boolean = false,
    val conversations: List<MessengerConversationUi> = emptyList(),
    val activeConversationId: Int? = null,
    val activeTitle: String = "",
    val messages: List<MessengerMessageDto> = emptyList(),
    val hasMoreOlder: Boolean = false,
    val nextBeforeId: Int? = null,
    val loadingOlder: Boolean = false,
    val draft: String = "",
    val error: String? = null,
)

class MessengerViewModel(
    private val repository: MusicRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(MessengerUiState(loading = true))
    val state = _state.asStateFlow()
    private val userProfileCache = mutableMapOf<Int, UserProfileDto>()

    fun refreshConversations() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            repository.getMessengerConversations()
                .onSuccess { rows ->
                    val items = rows.map { row ->
                        MessengerConversationUi(
                            id = row.id,
                            type = row.type,
                            title = resolveConversationTitle(row),
                            unreadCount = row.unreadCount,
                        )
                    }
                    _state.update { current ->
                        current.copy(
                            loading = false,
                            conversations = items,
                        )
                    }
                    val active = _state.value.activeConversationId
                    if (active != null) {
                        refreshMessages(active)
                    }
                }
                .onFailure { error ->
                    _state.update { it.copy(loading = false, error = error.message ?: "Не удалось загрузить чаты") }
                }
        }
    }

    fun openConversation(conversationId: Int) {
        val row = _state.value.conversations.firstOrNull { it.id == conversationId }
        _state.update {
            it.copy(
                activeConversationId = conversationId,
                activeTitle = row?.title ?: "Чат #$conversationId",
                messages = emptyList(),
                hasMoreOlder = false,
                nextBeforeId = null,
                loadingOlder = false,
            )
        }
        loadInitialMessages(conversationId)
    }

    fun closeConversation() {
        _state.update {
            it.copy(
                activeConversationId = null,
                messages = emptyList(),
                hasMoreOlder = false,
                nextBeforeId = null,
                loadingOlder = false,
                draft = "",
                error = null,
            )
        }
    }

    fun updateDraft(text: String) {
        _state.update { it.copy(draft = text) }
    }

    fun sendMessage() {
        val conversationId = _state.value.activeConversationId ?: return
        val text = _state.value.draft.trim()
        if (text.isBlank()) {
            return
        }

        viewModelScope.launch {
            repository.sendMessengerMessage(conversationId, text)
                .onSuccess {
                    _state.update { current -> current.copy(draft = "") }
                    refreshMessages(conversationId)
                    refreshConversations()
                }
                .onFailure { error ->
                    _state.update { it.copy(error = error.message ?: "Не удалось отправить сообщение") }
                }
        }
    }

    fun refreshMessages(conversationId: Int) {
        viewModelScope.launch {
            val latestId = _state.value.messages.lastOrNull()?.id
            if (latestId == null) {
                loadInitialMessages(conversationId)
                return@launch
            }

            repository.getMessengerMessages(
                conversationId = conversationId,
                afterId = latestId,
                perPage = 20,
            ).onSuccess { page ->
                    if (page.data.isNotEmpty()) {
                        val existing = _state.value.messages.associateBy { it.id }
                        val merged = _state.value.messages + page.data.filter { existing[it.id] == null }
                        _state.update { it.copy(messages = merged, error = null) }
                    }
                    val lastId = _state.value.messages.lastOrNull()?.id
                    if (lastId != null) {
                        repository.markConversationRead(conversationId, lastId)
                    }
                }
                .onFailure { error ->
                    _state.update { it.copy(error = error.message ?: "Не удалось загрузить сообщения") }
                }
        }
    }

    fun loadOlderMessages(onLoaded: (Int) -> Unit = {}) {
        val snapshot = _state.value
        val conversationId = snapshot.activeConversationId ?: return
        if (!snapshot.hasMoreOlder || snapshot.loadingOlder || snapshot.nextBeforeId == null) {
            return
        }

        viewModelScope.launch {
            _state.update { it.copy(loadingOlder = true) }
            repository.getMessengerMessages(
                conversationId = conversationId,
                beforeId = snapshot.nextBeforeId,
                perPage = 20,
            ).onSuccess { page ->
                val known = _state.value.messages.associateBy { it.id }
                val prepend = page.data.filter { known[it.id] == null }
                _state.update { current ->
                    current.copy(
                        loadingOlder = false,
                        messages = prepend + current.messages,
                        hasMoreOlder = page.meta.hasMore,
                        nextBeforeId = page.meta.nextBeforeId,
                        error = null,
                    )
                }
                onLoaded(prepend.size)
            }.onFailure { error ->
                _state.update {
                    it.copy(
                        loadingOlder = false,
                        error = error.message ?: "Не удалось догрузить историю",
                    )
                }
                onLoaded(0)
            }
        }
    }

    private fun loadInitialMessages(conversationId: Int) {
        viewModelScope.launch {
            repository.getMessengerMessages(conversationId = conversationId, perPage = 20)
                .onSuccess { page ->
                    _state.update {
                        it.copy(
                            messages = page.data,
                            hasMoreOlder = page.meta.hasMore,
                            nextBeforeId = page.meta.nextBeforeId,
                            loadingOlder = false,
                            error = null,
                        )
                    }
                    val lastId = page.data.lastOrNull()?.id
                    if (lastId != null) {
                        repository.markConversationRead(conversationId, lastId)
                    }
                }
                .onFailure { error ->
                    _state.update {
                        it.copy(
                            messages = emptyList(),
                            hasMoreOlder = false,
                            nextBeforeId = null,
                            loadingOlder = false,
                            error = error.message ?: "Не удалось загрузить сообщения",
                        )
                    }
                }
        }
    }

    private suspend fun resolveConversationTitle(row: MessengerConversationDto): String {
        if (row.type == "group") {
            val groupTitle = normalizeTitle(row.title)
            return groupTitle ?: "Группа #${row.id}"
        }

        if (row.type != "direct") {
            return normalizeTitle(row.title) ?: "Чат #${row.id}"
        }

        val peerId = row.directPeer?.id
        if (peerId == null || peerId < 1) {
            return normalizeDirectParticipantLabel(row.directPeer?.name)
                ?: normalizeDirectParticipantLabel(row.title)
                ?: "Чат #${row.id}"
        }

        val profile = userProfileCache[peerId]
            ?: repository.getUserProfile(peerId)
                .getOrNull()
                ?.also { userProfileCache[peerId] = it }

        val fullName = normalizeTitle(profile?.name)
        if (fullName != null) {
            return fullName
        }

        val peerName = normalizeDirectParticipantLabel(row.directPeer?.name)
        if (peerName != null) {
            return peerName
        }

        val loginFromEmail = extractLogin(profile?.email)
        if (loginFromEmail != null) {
            return loginFromEmail
        }

        return "Собеседник #$peerId"
    }

    private fun extractLogin(email: String?): String? {
        val normalized = email?.trim().orEmpty()
        if (normalized.isBlank() || normalized == "0") {
            return null
        }
        val local = normalized.substringBefore('@').trim()
        if (local.isBlank() || local == "0") {
            return null
        }
        return local
    }

    private fun normalizeTitle(raw: String?): String? {
        val normalized = raw?.trim().orEmpty()
        if (normalized.isBlank() || normalized == "0") {
            return null
        }
        return normalized
    }

    private fun normalizeDirectParticipantLabel(raw: String?): String? {
        val normalized = normalizeTitle(raw) ?: return null
        if (normalized.lowercase() == "чат") {
            return null
        }
        return normalized
    }
}
