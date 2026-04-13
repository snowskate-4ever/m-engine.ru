package ru.mengine.mobile.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.request.get
import io.ktor.client.request.header
import io.ktor.client.request.post
import io.ktor.client.request.setBody
import io.ktor.client.statement.bodyAsText
import io.ktor.http.HttpHeaders
import io.ktor.http.HttpStatusCode
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.decodeFromJsonElement
import ru.mengine.mobile.data.remote.dto.AuthRequestDto
import ru.mengine.mobile.data.remote.dto.AuthUserDto
import ru.mengine.mobile.data.remote.dto.LoginDataPayload
import ru.mengine.mobile.data.remote.dto.LoginEnvelope

/**
 * Вход через **POST /api/login** (проверка пароля через Laravel Auth).
 *
 * Не используем **POST /api/auth** с `X-Auth-Channel-Type: api`: на бэкенде для `api` вызывается
 * `processWebAuth`, который для уже существующего пользователя **не сверяет пароль** — это не годится для мобильного логина.
 */
class AuthApi(
    private val client: HttpClient,
) {
    private val json = Json { ignoreUnknownKeys = true }

    suspend fun authenticate(email: String, password: String): Result<Pair<AuthUserDto, String>> {
        val response = client.post("api/login") {
            setBody(AuthRequestDto(email = email.trim(), password = password))
        }

        val text = response.bodyAsText()
        val envelope = runCatching { json.decodeFromString(LoginEnvelope.serializer(), text) }.getOrNull()
            ?: return Result.failure(Exception("Не удалось разобрать ответ сервера"))

        val payload = envelope.data?.let { element ->
            runCatching { json.decodeFromJsonElement(LoginDataPayload.serializer(), element) }.getOrNull()
        }

        if (response.status == HttpStatusCode.OK && envelope.success && payload != null && payload.token.isNotBlank()) {
            val user = AuthUserDto(
                id = 0,
                name = payload.name,
                email = payload.email,
            )
            return Result.success(user to payload.token)
        }

        val msg = envelope.message?.takeIf { it.isNotBlank() }
            ?: if (response.status == HttpStatusCode.Unauthorized) {
                "Неверный email или пароль"
            } else {
                "Ошибка ${response.status.value}"
            }
        return Result.failure(Exception(msg))
    }

    suspend fun hasValidSession(token: String): Boolean {
        if (token.isBlank()) {
            return false
        }

        val response = client.get("api/messenger/conversations") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }

        return response.status == HttpStatusCode.OK
    }
}
