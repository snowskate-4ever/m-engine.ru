package ru.mengine.mobile.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.header
import io.ktor.client.request.patch
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.request.get
import io.ktor.client.request.parameter
import io.ktor.client.statement.bodyAsText
import io.ktor.http.HttpHeaders
import io.ktor.http.HttpStatusCode
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.decodeFromJsonElement
import ru.mengine.mobile.data.remote.dto.ActorContextResponseDto
import ru.mengine.mobile.data.remote.dto.ApiEnvelopeDto
import ru.mengine.mobile.data.remote.dto.EventsPayloadDto
import ru.mengine.mobile.data.remote.dto.MessengerConversationDto
import ru.mengine.mobile.data.remote.dto.MessengerConversationsResponseDto
import ru.mengine.mobile.data.remote.dto.MessengerMessageDto
import ru.mengine.mobile.data.remote.dto.MessengerMessagesResponseDto
import ru.mengine.mobile.data.remote.dto.MessengerSendResponseDto
import ru.mengine.mobile.data.remote.dto.MatchingEventDto
import ru.mengine.mobile.data.remote.dto.MusicResourceCatalogResponseDto
import ru.mengine.mobile.data.remote.dto.MusicResourceItemDto
import ru.mengine.mobile.data.remote.dto.MusicResourceSectionDto
import ru.mengine.mobile.data.remote.dto.MusicSearchRequestDto
import ru.mengine.mobile.data.remote.dto.MusicMatchingResponseDto
import ru.mengine.mobile.data.remote.dto.ResourceDto
import ru.mengine.mobile.data.remote.dto.ResourceTypeDto
import ru.mengine.mobile.data.remote.dto.ResourcesPayloadDto
import ru.mengine.mobile.data.remote.dto.TypesPayloadDto
import ru.mengine.mobile.data.remote.dto.UpdateActorContextResponseDto
import ru.mengine.mobile.data.remote.dto.UserProfileDto

class MusicApi(
    private val client: HttpClient,
) {
    private val json = Json { ignoreUnknownKeys = true }

    suspend fun getActorContext(token: String): Result<ActorContextResponseDto> =
        runCatching {
            val response = client.get("api/music/actor-context") {
                bearer(token)
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            response.body()
        }

    suspend fun updateActorContext(token: String, type: String?, id: Int?): Result<UpdateActorContextResponseDto> =
        runCatching {
            val response = client.patch("api/music/actor-context") {
                bearer(token)
                setBody(
                    mapOf(
                        "type" to type,
                        "id" to id,
                    ),
                )
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            response.body()
        }

    suspend fun getResourceCatalog(token: String): Result<MusicResourceCatalogResponseDto> =
        runCatching {
            val typesResponse = client.get("api/types") {
                bearer(token)
            }
            if (typesResponse.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${typesResponse.status.value}")
            }
            val resourcesResponse = client.get("api/resources") {
                bearer(token)
            }
            if (resourcesResponse.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${resourcesResponse.status.value}")
            }

            val typesEnvelope = json.decodeFromString<ApiEnvelopeDto<TypesPayloadDto>>(typesResponse.bodyAsText())
            val resourcesEnvelope = json.decodeFromString<ApiEnvelopeDto<ResourcesPayloadDto>>(resourcesResponse.bodyAsText())

            val types = typesEnvelope.data?.types.orEmpty()
            val resources = resourcesEnvelope.data?.resources.orEmpty()

            mapResources(types, resources)
        }

    suspend fun getMatchingFeed(token: String): Result<MusicMatchingResponseDto> =
        runCatching {
            val response = client.get("api/events?date_from=2020-01-01&date_to=2035-01-01") {
                bearer(token)
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            val envelope = json.decodeFromString<ApiEnvelopeDto<EventsPayloadDto>>(response.bodyAsText())
            val events = envelope.data?.events.orEmpty()

            val matchingRows = events
                .filter {
                    it.matchingSpaceId != null ||
                        !it.matchingProposedStartAt.isNullOrBlank() ||
                        !it.matchingBookingConfirmedAt.isNullOrBlank()
                }
                .map {
                    MusicSearchRequestDto(
                        id = it.id,
                        searchGoal = it.matchingSpaceType ?: "matching",
                        status = when {
                            !it.matchingBookingConfirmedAt.isNullOrBlank() -> "confirmed"
                            !it.matchingProposedStartAt.isNullOrBlank() -> "proposed"
                            else -> (it.status ?: "pending")
                        },
                        initiatorLabel = it.name,
                        createdAt = it.matchingProposedStartAt,
                    )
                }

            MusicMatchingResponseDto(data = matchingRows)
        }

    suspend fun getMessengerConversations(token: String): Result<List<MessengerConversationDto>> =
        runCatching {
            val response = client.get("api/messenger/conversations") {
                bearer(token)
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            response.body<MessengerConversationsResponseDto>().data
        }

    suspend fun getMessengerMessages(
        token: String,
        conversationId: Int,
        beforeId: Int? = null,
        afterId: Int? = null,
        perPage: Int = 20,
    ): Result<MessengerMessagesResponseDto> =
        runCatching {
            val response = client.get("api/messenger/conversations/$conversationId/messages") {
                bearer(token)
                parameter("per_page", perPage)
                if (beforeId != null) {
                    parameter("before_id", beforeId)
                }
                if (afterId != null) {
                    parameter("after_id", afterId)
                }
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            response.body<MessengerMessagesResponseDto>()
        }

    suspend fun sendMessengerMessage(token: String, conversationId: Int, body: String): Result<MessengerMessageDto> =
        runCatching {
            val response = client.post("api/messenger/conversations/$conversationId/messages") {
                bearer(token)
                setBody(mapOf("body" to body))
            }
            if (response.status != HttpStatusCode.Created && response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            response.body<MessengerSendResponseDto>().data
        }

    suspend fun markConversationRead(token: String, conversationId: Int, messageId: Int): Result<Unit> =
        runCatching {
            val response = client.post("api/messenger/conversations/$conversationId/read") {
                bearer(token)
                setBody(mapOf("message_id" to messageId))
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            Unit
        }

    suspend fun getUserProfile(token: String, userId: Int): Result<UserProfileDto> =
        runCatching {
            val response = client.get("api/users/$userId") {
                bearer(token)
            }
            if (response.status != HttpStatusCode.OK) {
                throw Exception("Ошибка ${response.status.value}")
            }
            val envelope = json.decodeFromString<ApiEnvelopeDto<UserProfileDto>>(response.bodyAsText())
            envelope.data ?: throw Exception("Пустой ответ профиля пользователя")
        }

    private fun io.ktor.client.request.HttpRequestBuilder.bearer(token: String) {
        header(HttpHeaders.Authorization, "Bearer $token")
    }

    private fun mapResources(
        types: List<ResourceTypeDto>,
        resources: List<ResourceDto>,
    ): MusicResourceCatalogResponseDto {
        val grouped = resources.groupBy { it.typeId }
        val sections = types.map { type ->
            val items = grouped[type.id].orEmpty()
            MusicResourceSectionDto(
                key = "type_${type.id}",
                label = type.name,
                totalCount = items.size,
                items = items
                    .take(10)
                    .map { resource ->
                        MusicResourceItemDto(
                            id = resource.id,
                            name = buildResourceName(type.name, resource),
                        )
                    },
            )
        }

        return MusicResourceCatalogResponseDto(data = sections)
    }

    private fun buildResourceName(typeLabel: String, row: ResourceDto): String {
        val start = row.startAt?.take(10) ?: "без даты"
        return "$typeLabel #${row.id} ($start)"
    }
}
