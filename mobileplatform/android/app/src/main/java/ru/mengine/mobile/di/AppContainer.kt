package ru.mengine.mobile.di

import android.app.Application
import ru.mengine.mobile.BuildConfig
import ru.mengine.mobile.data.AuthRepository
import ru.mengine.mobile.data.MusicRepository
import ru.mengine.mobile.data.remote.AuthApi
import ru.mengine.mobile.data.remote.HttpClientFactory
import ru.mengine.mobile.data.remote.MusicApi
import ru.mengine.mobile.data.store.TokenStore

class AppContainer(application: Application) {
    private val httpClient = HttpClientFactory.create(
        baseUrl = BuildConfig.API_BASE_URL,
        appName = "M-Engine-Android",
    )

    val tokenStore = TokenStore(application)
    private val authApi = AuthApi(client = httpClient)
    private val musicApi = MusicApi(client = httpClient)
    val authRepository = AuthRepository(authApi, tokenStore)
    val musicRepository = MusicRepository(musicApi, tokenStore)
}
