package ru.mengine.mobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import ru.mengine.mobile.data.MusicRepository
import ru.mengine.mobile.data.remote.dto.MusicSearchRequestDto

data class MatchingUiState(
    val loading: Boolean = false,
    val requests: List<MusicSearchRequestDto> = emptyList(),
    val error: String? = null,
)

class MatchingViewModel(
    private val repository: MusicRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(MatchingUiState(loading = true))
    val state = _state.asStateFlow()

    fun refresh() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            repository.getMatchingFeed()
                .onSuccess { payload ->
                    _state.update {
                        it.copy(
                            loading = false,
                            requests = payload.data,
                        )
                    }
                }
                .onFailure { error ->
                    _state.update { it.copy(loading = false, error = error.message ?: "Не удалось загрузить мэтчинг") }
                }
        }
    }
}
