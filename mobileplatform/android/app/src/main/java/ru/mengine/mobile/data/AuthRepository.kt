package ru.mengine.mobile.data

import ru.mengine.mobile.data.remote.AuthApi
import ru.mengine.mobile.data.store.TokenStore

class AuthRepository(
    private val api: AuthApi,
    private val tokenStore: TokenStore,
) {
    suspend fun login(email: String, password: String): Result<Unit> {
        val result = api.authenticate(email, password)
        result.fold(
            onSuccess = { (user, token) ->
                tokenStore.saveSession(token = token, displayName = user.name)
            },
            onFailure = { return Result.failure(it) },
        )
        return Result.success(Unit)
    }

    suspend fun logout() {
        tokenStore.clear()
    }

    suspend fun hasValidSession(): Boolean {
        val token = tokenStore.getToken()
        if (token.isNullOrBlank()) {
            return false
        }

        val valid = runCatching { api.hasValidSession(token) }.getOrDefault(false)
        if (!valid) {
            tokenStore.clear()
        }

        return valid
    }
}
