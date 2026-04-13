package ru.mengine.mobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import ru.mengine.mobile.data.MusicRepository
import ru.mengine.mobile.data.remote.dto.MusicResourceSectionDto

data class ResourcesUiState(
    val loading: Boolean = false,
    val sections: List<MusicResourceSectionDto> = emptyList(),
    val error: String? = null,
)

class ResourcesViewModel(
    private val repository: MusicRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(ResourcesUiState(loading = true))
    val state = _state.asStateFlow()

    fun refresh() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            repository.getResourceCatalog()
                .onSuccess { payload ->
                    _state.update {
                        it.copy(
                            loading = false,
                            sections = payload.data,
                        )
                    }
                }
                .onFailure { error ->
                    _state.update { it.copy(loading = false, error = error.message ?: "Не удалось загрузить ресурсы") }
                }
        }
    }
}
