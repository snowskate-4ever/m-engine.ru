package ru.mengine.mobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import ru.mengine.mobile.data.MusicRepository
import ru.mengine.mobile.data.remote.dto.ActorContextItemDto

data class ProfilesUiState(
    val loading: Boolean = false,
    val currentLabel: String? = null,
    val items: List<ActorContextItemDto> = emptyList(),
    val error: String? = null,
)

class ProfilesViewModel(
    private val repository: MusicRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(ProfilesUiState(loading = true))
    val state = _state.asStateFlow()

    fun refresh() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            repository.getActorContext()
                .onSuccess { payload ->
                    _state.update {
                        val current = payload.current
                        val currentLabel = payload.data.firstOrNull { item ->
                            item.type == current?.type && item.id == current.id
                        }?.label
                        it.copy(
                            loading = false,
                            items = payload.data,
                            currentLabel = currentLabel,
                        )
                    }
                }
                .onFailure { error ->
                    _state.update { it.copy(loading = false, error = error.message ?: "Не удалось загрузить профили") }
                }
        }
    }

    fun selectActor(type: String, id: Int) {
        viewModelScope.launch {
            repository.updateActorContext(type, id)
                .onSuccess {
                    refresh()
                }
                .onFailure { error ->
                    _state.update { it.copy(error = error.message ?: "Не удалось сменить профиль") }
                }
        }
    }
}
