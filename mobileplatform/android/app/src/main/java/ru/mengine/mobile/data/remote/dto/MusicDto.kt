package ru.mengine.mobile.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ActorContextResponseDto(
    val data: List<ActorContextItemDto> = emptyList(),
    val current: ActorContextCurrentDto? = null,
)

@Serializable
data class UpdateActorContextResponseDto(
    val ok: Boolean = false,
    val current: ActorContextCurrentDto? = null,
)

@Serializable
data class ActorContextItemDto(
    val type: String,
    val id: Int,
    val label: String,
)

@Serializable
data class ActorContextCurrentDto(
    val type: String,
    val id: Int,
)

@Serializable
data class ApiEnvelopeDto<T>(
    val success: Boolean = false,
    val message: String? = null,
    val data: T? = null,
)

@Serializable
data class ResourcesPayloadDto(
    val resources: List<ResourceDto> = emptyList(),
)

@Serializable
data class TypesPayloadDto(
    val types: List<ResourceTypeDto> = emptyList(),
)

@Serializable
data class EventsPayloadDto(
    val events: List<MatchingEventDto> = emptyList(),
)

@Serializable
data class ResourceTypeDto(
    val id: Int,
    val name: String,
    @SerialName("resource_type") val resourceType: String? = null,
)

@Serializable
data class ResourceDto(
    val id: Int,
    @SerialName("type_id") val typeId: Int? = null,
    @SerialName("start_at") val startAt: String? = null,
    @SerialName("end_at") val endAt: String? = null,
)

@Serializable
data class MusicResourceCatalogResponseDto(
    val data: List<MusicResourceSectionDto> = emptyList(),
)

@Serializable
data class MatchingEventDto(
    val id: Int,
    val name: String,
    val status: String? = null,
    @SerialName("matching_space_type") val matchingSpaceType: String? = null,
    @SerialName("matching_space_id") val matchingSpaceId: Int? = null,
    @SerialName("matching_proposed_start_at") val matchingProposedStartAt: String? = null,
    @SerialName("matching_proposed_end_at") val matchingProposedEndAt: String? = null,
    @SerialName("matching_booking_confirmed_at") val matchingBookingConfirmedAt: String? = null,
)

@Serializable
data class MusicResourceSectionDto(
    val key: String,
    val label: String,
    @SerialName("total_count") val totalCount: Int = 0,
    val items: List<MusicResourceItemDto> = emptyList(),
)

@Serializable
data class MusicResourceItemDto(
    val id: Int,
    val name: String,
)

@Serializable
data class MusicMatchingResponseDto(
    val data: List<MusicSearchRequestDto> = emptyList(),
)

@Serializable
data class MusicSearchRequestDto(
    val id: Int,
    @SerialName("search_goal") val searchGoal: String,
    val status: String,
    @SerialName("initiator_label") val initiatorLabel: String,
    @SerialName("created_at") val createdAt: String? = null,
)

@Serializable
data class MessengerConversationsResponseDto(
    val data: List<MessengerConversationDto> = emptyList(),
)

@Serializable
data class MessengerConversationDto(
    val id: Int,
    val type: String,
    val title: String? = null,
    @SerialName("unread_count") val unreadCount: Int = 0,
    @SerialName("direct_peer") val directPeer: MessengerPeerDto? = null,
    @SerialName("last_message") val lastMessage: MessengerMessageDto? = null,
)

@Serializable
data class MessengerMessagesResponseDto(
    val data: List<MessengerMessageDto> = emptyList(),
    val meta: MessengerMessagesMetaDto = MessengerMessagesMetaDto(),
)

@Serializable
data class MessengerMessagesMetaDto(
    @SerialName("has_more") val hasMore: Boolean = false,
    @SerialName("next_before_id") val nextBeforeId: Int? = null,
    @SerialName("next_after_id") val nextAfterId: Int? = null,
)

@Serializable
data class MessengerSendResponseDto(
    val data: MessengerMessageDto,
)

@Serializable
data class MessengerMessageDto(
    val id: Int,
    @SerialName("conversation_id") val conversationId: Int,
    @SerialName("user_id") val userId: Int? = null,
    val body: String? = null,
    val kind: String = "text",
    val author: MessengerPeerDto? = null,
    @SerialName("created_at") val createdAt: String? = null,
)

@Serializable
data class MessengerPeerDto(
    val id: Int,
    val name: String? = null,
)

@Serializable
data class UserProfileDto(
    val id: Int,
    val name: String? = null,
    val email: String? = null,
)
