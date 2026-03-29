package ru.mengine.mobile

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            MEngineTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    HomeScreen()
                }
            }
        }
    }
}

@Composable
private fun HomeScreen() {
    Text(
        text = "M-Engine Mobile\n\nКаркас приложения. API: см. mobileplatform/README.md",
        modifier = Modifier.padding(24.dp),
        style = MaterialTheme.typography.bodyLarge,
    )
}

@Composable
private fun MEngineTheme(content: @Composable () -> Unit) {
    MaterialTheme(content = content)
}

@Preview(showBackground = true)
@Composable
private fun HomeScreenPreview() {
    MEngineTheme { HomeScreen() }
}
