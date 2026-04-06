package ru.mengine.mobile.data.store

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.authDataStore: DataStore<Preferences> by preferencesDataStore("auth")

class TokenStore(private val context: Context) {
    private val keyToken = stringPreferencesKey("sanctum_token")
    private val keyUserName = stringPreferencesKey("user_display_name")

    val tokenFlow: Flow<String?> = context.authDataStore.data.map { it[keyToken] }

    suspend fun getToken(): String? =
        context.authDataStore.data.map { it[keyToken] }.first()

    suspend fun saveSession(token: String, displayName: String) {
        context.authDataStore.edit { prefs ->
            prefs[keyToken] = token
            prefs[keyUserName] = displayName
        }
    }

    suspend fun clear() {
        context.authDataStore.edit { prefs ->
            prefs.remove(keyToken)
            prefs.remove(keyUserName)
        }
    }

    fun displayNameFlow(): Flow<String?> =
        context.authDataStore.data.map { it[keyUserName] }
}
