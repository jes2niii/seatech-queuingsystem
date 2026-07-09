package com.seatech.kiosk

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.Build
import android.os.Bundle
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.provider.Settings
import android.view.View
import android.view.WindowManager
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.ProgressBar
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var printService: BluetoothPrintService
    private lateinit var config: ServerConfig
    private lateinit var loadingProgress: ProgressBar
    private lateinit var errorOverlay: View
    private lateinit var btnRetry: Button
    private lateinit var btnOpenSettings: Button

    private var pendingTicket: String? = null
    private var pendingPurpose: String? = null

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        // Keep the screen on while the kiosk is running
        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)

        config = ServerConfig(this)
        printService = BluetoothPrintService()

        webView = findViewById(R.id.webView)
        loadingProgress = findViewById(R.id.loadingProgress)
        errorOverlay = findViewById(R.id.errorOverlay)
        btnRetry = findViewById(R.id.btnRetry)
        btnOpenSettings = findViewById(R.id.btnOpenSettings)

        configureWebView()
        wireErrorOverlay()

        // Lock back navigation
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                // Intentionally swallow back press to keep kiosk in foreground
            }
        })

        loadConfiguredUrl()
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun configureWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            // Allow autoplay for the call.mp3 sound
            mediaPlaybackRequiresUserGesture = false
            allowFileAccess = false
            allowContentAccess = false
            cacheMode = android.webkit.WebSettings.LOAD_DEFAULT
            useWideViewPort = true
            loadWithOverviewMode = true
            builtInZoomControls = false
        }

        webView.addJavascriptInterface(
            WebAppInterface(
                onPrint = { ticketNo, purpose -> printTicket(ticketNo, purpose) },
                serverUrlProvider = { config.serverUrl },
                deviceIdProvider = { getKioskDeviceId() },
                vibrate = { ms -> vibrate(ms) },
            ),
            "KioskPrint"
        )

        webView.webViewClient = object : WebViewClient() {
            override fun onPageStarted(view: WebView, url: String?, favicon: android.graphics.Bitmap?) {
                super.onPageStarted(view, url, favicon)
                loadingProgress.visibility = View.VISIBLE
                errorOverlay.visibility = View.GONE
            }

            override fun onPageFinished(view: WebView, url: String?) {
                super.onPageFinished(view, url)
                loadingProgress.visibility = View.GONE
                view.evaluateJavascript("window.__kioskNative = true", null)
            }

            override fun onReceivedError(
                view: WebView,
                errorCode: Int,
                description: String?,
                failingUrl: String?
            ) {
                super.onReceivedError(view, errorCode, description, failingUrl)
                loadingProgress.visibility = View.GONE
                showErrorOverlay()
            }
        }
    }

    private fun wireErrorOverlay() {
        btnRetry.setOnClickListener { loadConfiguredUrl() }
        btnOpenSettings.setOnClickListener {
            startActivity(Intent(this, SettingsActivity::class.java))
        }
    }

    private fun showErrorOverlay() {
        errorOverlay.visibility = View.VISIBLE
    }

    private fun hideErrorOverlay() {
        errorOverlay.visibility = View.GONE
    }

    private fun loadConfiguredUrl() {
        hideErrorOverlay()
        if (!isNetworkAvailable()) {
            showErrorOverlay()
            return
        }
        loadingProgress.visibility = View.VISIBLE
        webView.loadUrl(config.serverUrl)
    }

    private fun isNetworkAvailable(): Boolean {
        val cm = getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager
            ?: return true
        val network = cm.activeNetwork ?: return false
        val caps = cm.getNetworkCapabilities(network) ?: return false
        return caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
            caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
    }

    private fun printTicket(ticketNo: String, purpose: String) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            val needsConnect = ContextCompat.checkSelfPermission(
                this, Manifest.permission.BLUETOOTH_CONNECT
            ) != PackageManager.PERMISSION_GRANTED
            val needsScan = ContextCompat.checkSelfPermission(
                this, Manifest.permission.BLUETOOTH_SCAN
            ) != PackageManager.PERMISSION_GRANTED

            if (needsConnect || needsScan) {
                pendingTicket = ticketNo
                pendingPurpose = purpose
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.BLUETOOTH_CONNECT, Manifest.permission.BLUETOOTH_SCAN),
                    REQ_BLUETOOTH
                )
                return
            }
        }
        doPrint(ticketNo, purpose)
    }

    private fun doPrint(ticketNo: String, purpose: String) {
        printService.print(
            ticketNo = ticketNo,
            purpose = purpose,
            preferredAddress = config.printerAddress,
            preferredNameHint = config.printerNameHint,
        ) { result ->
            runOnUiThread {
                val js = when (result) {
                    is BluetoothPrintService.PrintResult.Success ->
                        "showToast('${jsString(getString(R.string.print_success))}','success');" +
                            "window.KioskPrint && KioskPrint.onPrintSuccess && KioskPrint.onPrintSuccess();"
                    is BluetoothPrintService.PrintResult.Cancelled ->
                        "showToast('${jsString(getString(R.string.print_cancelled))}','info')"
                    is BluetoothPrintService.PrintResult.Error -> {
                        val msg = getString(R.string.print_failed, result.message)
                        "showToast('${jsString(msg)}','error')"
                    }
                }
                webView.evaluateJavascript(js, null)
            }
        }
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == REQ_BLUETOOTH &&
            grantResults.isNotEmpty() &&
            grantResults.all { it == PackageManager.PERMISSION_GRANTED }
        ) {
            val ticket = pendingTicket
            val purpose = pendingPurpose
            pendingTicket = null
            pendingPurpose = null
            if (ticket != null && purpose != null) {
                doPrint(ticket, purpose)
            }
        } else {
            runOnUiThread {
                webView.evaluateJavascript(
                    "showToast('${jsString(getString(R.string.print_permission_denied))}','error')",
                    null
                )
            }
        }
    }

    override fun onResume() {
        super.onResume()
        // Reload in case the user changed the server URL in Settings
        if (webView.url == null || webView.url != config.serverUrl) {
            loadConfiguredUrl()
        }
    }

    override fun onDestroy() {
        printService.cancel()
        super.onDestroy()
    }

    private fun getKioskDeviceId(): String {
        return Settings.Secure.getString(contentResolver, Settings.Secure.ANDROID_ID) ?: "unknown"
    }

    @Suppress("DEPRECATION")
    private fun vibrate(durationMs: Long) {
        try {
            val vibrator: Vibrator? = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                val vm = getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as? VibratorManager
                vm?.defaultVibrator
            } else {
                getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
            }
            if (vibrator == null || !vibrator.hasVibrator()) return
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                vibrator.vibrate(VibrationEffect.createOneShot(durationMs, VibrationEffect.DEFAULT_AMPLITUDE))
            } else {
                vibrator.vibrate(durationMs)
            }
        } catch (_: SecurityException) {
            // VIBRATE permission missing — silently ignore
        } catch (_: Exception) {
            // never crash from a JS-triggered call
        }
    }

    private fun jsString(input: String): String =
        input.replace("\\", "\\\\")
            .replace("'", "\\'")
            .replace("\"", "\\\"")
            .replace("\n", "\\n")
            .replace("\r", "")

    companion object {
        private const val REQ_BLUETOOTH = 1
    }
}
