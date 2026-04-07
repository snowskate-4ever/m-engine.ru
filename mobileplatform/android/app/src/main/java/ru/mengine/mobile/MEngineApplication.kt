package ru.mengine.mobile

import android.app.Application
import ru.mengine.mobile.di.AppContainer

class MEngineApplication : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
    }
}
