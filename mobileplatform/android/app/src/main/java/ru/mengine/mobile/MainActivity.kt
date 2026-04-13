package ru.mengine.mobile

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import ru.mengine.mobile.di.AppContainer
import ru.mengine.mobile.ui.auth.LoginScreen
import ru.mengine.mobile.ui.auth.LoginViewModel
import ru.mengine.mobile.ui.main.MainShell

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val container = (application as MEngineApplication).container
        setContent {
            MEngineTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    RootNav(container = container)
                }
            }
        }
    }
}

@Composable
private fun MEngineTheme(content: @Composable () -> Unit) {
    MaterialTheme(content = content)
}

@Composable
private fun RootNav(container: AppContainer) {
    val navController = rememberNavController()
    var bootstrapped by remember { mutableStateOf<Boolean?>(null) }

    LaunchedEffect(Unit) {
        bootstrapped = container.authRepository.hasValidSession()
    }

    when (bootstrapped) {
        null -> {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        }
        else -> {
            NavHost(
                navController = navController,
                startDestination = if (bootstrapped == true) "main" else "login",
            ) {
                composable("login") {
                    val vm = viewModel<LoginViewModel>(
                        factory = object : ViewModelProvider.Factory {
                            @Suppress("UNCHECKED_CAST")
                            override fun <T : ViewModel> create(modelClass: Class<T>): T =
                                LoginViewModel(container.authRepository) as T
                        },
                    )
                    LoginScreen(
                        viewModel = vm,
                        onLoggedIn = {
                            navController.navigate("main") {
                                popUpTo("login") { inclusive = true }
                            }
                        },
                    )
                }
                composable("main") {
                    MainShell(
                        container = container,
                        onLoggedOut = {
                            navController.navigate("login") {
                                popUpTo(0) { inclusive = true }
                            }
                        },
                    )
                }
            }
        }
    }
}
