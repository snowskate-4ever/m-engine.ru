package ru.mengine.mobile.ui.home

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle

@Composable
fun HomeScreen(
    viewModel: HomeViewModel,
    modifier: Modifier = Modifier,
) {
    val name by viewModel.displayName.collectAsStateWithLifecycle(initialValue = null)

    Column(
        modifier = modifier.padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Text(
            "Здравствуйте, ${name ?: "…"}",
            style = MaterialTheme.typography.headlineSmall,
        )
        Text(
            "Сессия сохранена. Дальше: список чатов (GET /api/messenger/conversations), FCM, WebSocket — см. mobileplatform/README.md",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Button(
            onClick = viewModel::logout,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Выйти")
        }
    }
}
