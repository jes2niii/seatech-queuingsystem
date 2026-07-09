package com.seatech.kiosk

import android.webkit.JavascriptInterface

/**
 * Bridge between the WebView's JavaScript and the native Android side.
 *
 * The web side can call:
 *   - window.KioskPrint.print(ticketNo, purpose)  → triggers native print
 *   - window.KioskPrint.getServerUrl()             → returns configured server URL
 *   - window.KioskPrint.getDeviceId()              → returns a stable device id
 *   - window.KioskPrint.vibrate(ms)               → haptic feedback
 *   - window.KioskPrint.isReady()                 → whether native is ready
 */
class WebAppInterface(
    private val onPrint: (String, String) -> Unit,
    private val serverUrlProvider: () -> String,
    private val deviceIdProvider: () -> String,
    private val vibrate: (Long) -> Unit,
) {

    @JavascriptInterface
    fun print(ticketNo: String, purpose: String) {
        onPrint(ticketNo, purpose)
    }

    @JavascriptInterface
    fun getServerUrl(): String = serverUrlProvider()

    @JavascriptInterface
    fun getDeviceId(): String = deviceIdProvider()

    @JavascriptInterface
    fun vibrate(durationMs: Long) {
        try {
            vibrate(durationMs)
        } catch (_: Exception) {
            // never let a JS call crash the host
        }
    }

    @JavascriptInterface
    fun isReady(): Boolean = true
}
