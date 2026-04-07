package ru.mengine.mobile.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import ru.mengine.mobile.data.AuthRepository
import ru.mengine.mobile.data.store.TokenStore

class HomeViewModel(
    private val authRepository: AuthRepository,
    tokenStore: TokenStore,
    private val onLoggedOut: () -> Unit,
) : ViewModel() {
    val displayName = tokenStore.displayNameFlow()
        .map { it ?: "пользователь" }
        .stateIn(viewModelScope, SharingStarted.Lazily, "…")

    fun logout() {
        viewModelScope.launch {
            authRepository.logout()
            onLoggedOut()
        }
    }
}
