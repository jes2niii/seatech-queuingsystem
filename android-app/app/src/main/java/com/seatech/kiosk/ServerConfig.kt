package com.seatech.kiosk

import android.content.Context
import android.content.SharedPreferences

/**
 * Persistent configuration for the kiosk app.
 * Stores the server URL, selected printer address, and related settings
 * in SharedPreferences so the app can be reconfigured without rebuilding.
 */
class ServerConfig(context: Context) {

    private val prefs: SharedPreferences =
        context.applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    var serverUrl: String
        get() = prefs.getString(KEY_SERVER_URL, DEFAULT_SERVER_URL) ?: DEFAULT_SERVER_URL
        set(value) = prefs.edit().putString(KEY_SERVER_URL, value).apply()

    var printerAddress: String?
        get() = prefs.getString(KEY_PRINTER_ADDRESS, null)
        set(value) = prefs.edit().putString(KEY_PRINTER_ADDRESS, value).apply()

    var printerNameHint: String
        get() = prefs.getString(KEY_PRINTER_NAME_HINT, "") ?: ""
        set(value) = prefs.edit().putString(KEY_PRINTER_NAME_HINT, value).apply()

    fun resetToDefaults() {
        prefs.edit().clear().apply()
    }

    companion object {
        private const val PREFS_NAME = "kiosk_prefs"
        private const val KEY_SERVER_URL = "server_url"
        private const val KEY_PRINTER_ADDRESS = "printer_address"
        private const val KEY_PRINTER_NAME_HINT = "printer_name_hint"

        // Default to the local development server. Override via Settings.
        val DEFAULT_SERVER_URL: String =
            "http://192.168.0.231/userPurpose"
    }
}
