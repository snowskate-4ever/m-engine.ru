package ru.mengine.mobile.data

import ru.mengine.mobile.data.remote.MusicApi
import ru.mengine.mobile.data.remote.dto.ActorContextResponseDto
import ru.mengine.mobile.data.remote.dto.MessengerConversationDto
import ru.mengine.mobile.data.remote.dto.MessengerMessagesResponseDto
import ru.mengine.mobile.data.remote.dto.MessengerMessageDto
import ru.mengine.mobile.data.remote.dto.MusicResourceCatalogResponseDto
import ru.mengine.mobile.data.remote.dto.MusicMatchingResponseDto
import ru.mengine.mobile.data.remote.dto.UpdateActorContextResponseDto
import ru.mengine.mobile.data.remote.dto.UserProfileDto
import ru.mengine.mobile.data.store.TokenStore

class MusicRepository(
    private val api: MusicApi,
    private val tokenStore: TokenStore,
) {
    suspend fun getActorContext(): Result<ActorContextResponseDto> =
        withToken { token -> api.getActorContext(token) }

    suspend fun updateActorContext(type: String?, id: Int?): Result<UpdateActorContextResponseDto> =
        withToken { token -> api.updateActorContext(token, type, id) }

    suspend fun getResourceCatalog(): Result<MusicResourceCatalogResponseDto> =
        withToken { token -> api.getResourceCatalog(token) }

    suspend fun getMatchingFeed(): Result<MusicMatchingResponseDto> =
        withToken { token -> api.getMatchingFeed(token) }

    suspend fun getMessengerConversations(): Result<List<MessengerConversationDto>> =
        withToken { token -> api.getMessengerConversations(token) }

    suspend fun getMessengerMessages(
        conversationId: Int,
        beforeId: Int? = null,
        afterId: Int? = null,
        perPage: Int = 20,
    ): Result<MessengerMessagesResponseDto> =
        withToken { token -> api.getMessengerMessages(token, conversationId, beforeId, afterId, perPage) }

    suspend fun sendMessengerMessage(conversationId: Int, body: String): Result<MessengerMessageDto> =
        withToken { token -> api.sendMessengerMessage(token, conversationId, body) }

    suspend fun markConversationRead(conversationId: Int, messageId: Int): Result<Unit> =
        withToken { token -> api.markConversationRead(token, conversationId, messageId) }

    suspend fun getUserProfile(userId: Int): Result<UserProfileDto> =
        withToken { token -> api.getUserProfile(token, userId) }

    private suspend fun <T> withToken(action: suspend (String) -> Result<T>): Result<T> {
        val token = tokenStore.getToken()
        if (token.isNullOrBlank()) {
            return Result.failure(Exception("Сессия истекла"))
        }
        return action(token)
    }
}
