package ru.mengine.mobile.ui.main

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.Apps
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.Handshake
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import ru.mengine.mobile.di.AppContainer
import ru.mengine.mobile.ui.home.HomeViewModel

private data class MainTab(
    val route: String,
    val icon: @Composable () -> Unit,
)

private val tabs = listOf(
    MainTab(
        route = "profiles",
        icon = { Icon(Icons.Filled.AccountCircle, contentDescription = null) },
    ),
    MainTab(
        route = "resources",
        icon = { Icon(Icons.Filled.Apps, contentDescription = null) },
    ),
    MainTab(
        route = "planning",
        icon = { Icon(Icons.Filled.Event, contentDescription = null) },
    ),
    MainTab(
        route = "messenger",
        icon = { Icon(Icons.AutoMirrored.Filled.Chat, contentDescription = null) },
    ),
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainShell(container: AppContainer, onLoggedOut: () -> Unit, modifier: Modifier = Modifier) {
    val navController = rememberNavController()
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route ?: "profiles"
    val snackbarHost = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()

    val sessionViewModel = viewModel<HomeViewModel>(
        factory = object : ViewModelProvider.Factory {
            @Suppress("UNCHECKED_CAST")
            override fun <T : ViewModel> create(modelClass: Class<T>): T =
                HomeViewModel(
                    authRepository = container.authRepository,
                    tokenStore = container.tokenStore,
                    onLoggedOut = onLoggedOut,
                ) as T
        },
    )
    val displayName by sessionViewModel.displayName.collectAsStateWithLifecycle(initialValue = "…")

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            TopAppBar(
                title = { Text("M-Engine: ${displayName ?: "пользователь"}") },
                actions = {
                    IconButton(onClick = sessionViewModel::logout) {
                        Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Выйти")
                    }
                },
            )
        },
        snackbarHost = { SnackbarHost(hostState = snackbarHost) },
        floatingActionButton = {
            if (currentRoute == "planning") {
                FloatingActionButton(onClick = { navController.navigate("matching") }) {
                    Icon(Icons.Filled.Handshake, contentDescription = "Мэтчинг")
                }
            }
        },
        bottomBar = {
            if (tabs.any { it.route == currentRoute }) {
                NavigationBar {
                    tabs.forEach { item ->
                        NavigationBarItem(
                            selected = currentRoute == item.route,
                            onClick = {
                                navController.navigate(item.route) {
                                    popUpTo("profiles")
                                    launchSingleTop = true
                                }
                            },
                            icon = item.icon,
                            label = null,
                            alwaysShowLabel = false,
                        )
                    }
                }
            }
        },
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = "profiles",
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding),
        ) {
            composable("profiles") {
                val vm = viewModel<ProfilesViewModel>(
                    factory = object : ViewModelProvider.Factory {
                        @Suppress("UNCHECKED_CAST")
                        override fun <T : ViewModel> create(modelClass: Class<T>): T =
                            ProfilesViewModel(container.musicRepository) as T
                    },
                )
                ProfilesScreen(viewModel = vm)
            }
            composable("resources") {
                val vm = viewModel<ResourcesViewModel>(
                    factory = object : ViewModelProvider.Factory {
                        @Suppress("UNCHECKED_CAST")
                        override fun <T : ViewModel> create(modelClass: Class<T>): T =
                            ResourcesViewModel(container.musicRepository) as T
                    },
                )
                ResourcesScreen(viewModel = vm)
            }
            composable("planning") {
                PlanningScreen(onOpenMatching = { navController.navigate("matching") })
            }
            composable("messenger") {
                val vm = viewModel<MessengerViewModel>(
                    factory = object : ViewModelProvider.Factory {
                        @Suppress("UNCHECKED_CAST")
                        override fun <T : ViewModel> create(modelClass: Class<T>): T =
                            MessengerViewModel(container.musicRepository) as T
                    },
                )
                MessengerScreen(viewModel = vm)
            }
            composable("matching") {
                val vm = viewModel<MatchingViewModel>(
                    factory = object : ViewModelProvider.Factory {
                        @Suppress("UNCHECKED_CAST")
                        override fun <T : ViewModel> create(modelClass: Class<T>): T =
                            MatchingViewModel(container.musicRepository) as T
                    },
                )
                MatchingScreen(
                    viewModel = vm,
                    onBack = { navController.popBackStack() },
                    onInfo = { message ->
                        scope.launch { snackbarHost.showSnackbar(message) }
                    },
                )
            }
        }
    }
}

@Composable
private fun ProfilesScreen(viewModel: ProfilesViewModel) {
    val state by viewModel.state.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) {
        viewModel.refresh()
    }

    if (state.loading) {
        Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator()
        }
        return
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text("Выбор и редактирование профилей", style = MaterialTheme.typography.titleMedium)
        Text(
            "Текущий профиль: ${state.currentLabel ?: "не выбран"}",
            style = MaterialTheme.typography.bodyMedium,
        )
        Text(
            "Выберите профиль ниже, чтобы сделать его активным.",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        if (state.error != null) {
            Text(state.error ?: "", color = MaterialTheme.colorScheme.error)
        }
        state.items.forEach { actor ->
            Surface(
                tonalElevation = 1.dp,
                shape = MaterialTheme.shapes.medium,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(actor.label, style = MaterialTheme.typography.bodyLarge)
                    Text(actor.type, style = MaterialTheme.typography.labelMedium)
                    TextButton(onClick = { viewModel.selectActor(actor.type, actor.id) }) {
                        Text("Сделать активным")
                    }
                }
            }
        }
        if (state.items.isEmpty()) {
            Text(
                "Доступных профилей пока нет.",
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                "Профили пока создаются в веб-кабинете: откройте m-engine.ru, выберите нужную роль, заполните профиль и сохраните изменения.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                "После сохранения вернитесь в приложение и обновите список.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            TextButton(onClick = viewModel::refresh) {
                Text("Обновить список")
            }
        }
    }
}

@Composable
private fun ResourcesScreen(viewModel: ResourcesViewModel) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    LaunchedEffect(Unit) {
        viewModel.refresh()
    }

    when {
        state.loading -> {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        }

        else -> {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                item {
                    Column(
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp),
                    ) {
                        Text("Ресурсы", style = MaterialTheme.typography.titleMedium)
                        Text(
                            "Все сущности, которые доступны в веб-кабинете.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        if (state.error != null) {
                            Text(state.error ?: "", color = MaterialTheme.colorScheme.error)
                        }
                    }
                }
                items(state.sections, key = { it.key }) { section ->
                    Column(
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp),
                    ) {
                        Text("${section.label} (${section.totalCount})", style = MaterialTheme.typography.titleSmall)
                        section.items.take(3).forEach { item ->
                            Text("• ${item.name}", style = MaterialTheme.typography.bodyMedium)
                        }
                        if (section.totalCount > section.items.size) {
                            Text(
                                "…и еще ${section.totalCount - section.items.size}",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        HorizontalDivider()
                    }
                }
            }
        }
    }
}

@Composable
private fun PlanningScreen(onOpenMatching: () -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text("Планирование", style = MaterialTheme.typography.titleMedium)
        Text(
            "Календарь и бронирования. Для мэтчинга используйте кнопку с рукопожатием.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        TextButton(onClick = onOpenMatching) {
            Text("Открыть мэтчинг")
        }
    }
}

@Composable
private fun MessengerScreen(viewModel: MessengerViewModel) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val listState = rememberLazyListState()
    val localScope = rememberCoroutineScope()
    var autoScrolledConversationId by remember { mutableStateOf<Int?>(null) }

    LaunchedEffect(Unit) {
        viewModel.refreshConversations()
    }
    LaunchedEffect(state.activeConversationId) {
        val activeId = state.activeConversationId ?: return@LaunchedEffect
        autoScrolledConversationId = null
        while (true) {
            viewModel.refreshMessages(activeId)
            delay(3000)
        }
    }
    LaunchedEffect(state.activeConversationId, state.messages.size) {
        if (state.activeConversationId == null || state.messages.isEmpty()) {
            return@LaunchedEffect
        }
        val lastIndex = state.messages.lastIndex
        if (autoScrolledConversationId != state.activeConversationId) {
            listState.scrollToItem(lastIndex)
            autoScrolledConversationId = state.activeConversationId
            return@LaunchedEffect
        }
        val lastVisibleIndex = listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index ?: -1
        val isNearBottom = lastVisibleIndex >= (lastIndex - 2)
        if (isNearBottom) {
            listState.animateScrollToItem(lastIndex)
        }
    }
    LaunchedEffect(state.activeConversationId, listState.firstVisibleItemIndex, state.hasMoreOlder, state.loadingOlder) {
        if (state.activeConversationId == null || !state.hasMoreOlder || state.loadingOlder) {
            return@LaunchedEffect
        }
        if (listState.firstVisibleItemIndex > 1) {
            return@LaunchedEffect
        }
        val anchorIndex = listState.firstVisibleItemIndex
        val anchorOffset = listState.firstVisibleItemScrollOffset
        viewModel.loadOlderMessages { addedCount ->
            if (addedCount > 0) {
                localScope.launch {
                    listState.scrollToItem(anchorIndex + addedCount, anchorOffset)
                }
            }
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        if (state.loading) {
            CircularProgressIndicator()
            return
        }

        if (state.activeConversationId == null) {
            Text("Чаты", style = MaterialTheme.typography.titleMedium)
            if (state.error != null) {
                Text(state.error ?: "", color = MaterialTheme.colorScheme.error)
            }
            LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                items(state.conversations, key = { it.id }) { chat ->
                    Surface(
                        tonalElevation = 1.dp,
                        shape = MaterialTheme.shapes.medium,
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        TextButton(
                            onClick = { viewModel.openConversation(chat.id) },
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Text("${chat.title} (${chat.unreadCount})")
                        }
                    }
                }
            }
        } else {
            Text(state.activeTitle, style = MaterialTheme.typography.titleMedium)
            TextButton(onClick = viewModel::closeConversation) {
                Text("Назад к списку")
            }
            if (state.error != null) {
                Text(state.error ?: "", color = MaterialTheme.colorScheme.error)
            }
            if (state.loadingOlder) {
                Text(
                    "Загружаем историю...",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            LazyColumn(
                modifier = Modifier.weight(1f),
                state = listState,
                verticalArrangement = Arrangement.spacedBy(6.dp),
            ) {
                items(state.messages, key = { it.id }) { message ->
                    val author = message.author?.name ?: if (message.userId == null) "Система" else "Вы"
                    Surface(
                        tonalElevation = 1.dp,
                        shape = MaterialTheme.shapes.small,
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(10.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp),
                        ) {
                            Text(author, style = MaterialTheme.typography.labelMedium)
                            Text(message.body ?: "—", style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                OutlinedTextField(
                    value = state.draft,
                    onValueChange = viewModel::updateDraft,
                    modifier = Modifier.weight(1f),
                    placeholder = { Text("Сообщение...") },
                )
                IconButton(onClick = viewModel::sendMessage) {
                    Icon(
                        Icons.AutoMirrored.Filled.Send,
                        contentDescription = "Отправить",
                    )
                }
            }
        }
    }
}

@Composable
private fun MatchingScreen(
    viewModel: MatchingViewModel,
    onBack: () -> Unit,
    onInfo: (String) -> Unit,
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    LaunchedEffect(Unit) {
        viewModel.refresh()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text("Мэтчинг", style = MaterialTheme.typography.titleMedium)
        TextButton(onClick = onBack) {
            Text("Назад")
        }
        if (state.loading) {
            CircularProgressIndicator()
        } else {
            if (state.error != null) {
                Text(state.error ?: "", color = MaterialTheme.colorScheme.error)
            }
            if (state.requests.isEmpty()) {
                Text("Заявок пока нет.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            state.requests.forEach { request ->
                Surface(
                    tonalElevation = 1.dp,
                    shape = MaterialTheme.shapes.medium,
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp),
                    ) {
                        Text("Цель: ${request.searchGoal}")
                        Text("Статус: ${request.status}")
                        Text("Инициатор: ${request.initiatorLabel}")
                        TextButton(onClick = { onInfo("Детальные действия мэтчинга будут добавлены в следующей версии.") }) {
                            Text("Детали")
                        }
                    }
                }
            }
        }
    }
}
