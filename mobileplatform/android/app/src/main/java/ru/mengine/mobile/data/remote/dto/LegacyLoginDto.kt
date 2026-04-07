package ru.mengine.mobile.data.remote.dto

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

/**
 * Ответ [POST /api/login][ApiAuthController] — обёртка ApiService::successResponse / errorResponse.
 * Поле [data] в ошибках часто приходит как пустой объект `{}`, поэтому тип — [JsonElement], не вложенный DTO.
 */
@Serializable
data class LoginEnvelope(
    val success: Boolean = false,
    val message: String? = null,
    val data: JsonElement? = null,
)

@Serializable
data class LoginDataPayload(
    val name: String,
    val email: String,
    val token: String,
)
