package com.seatech.kiosk

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.RadioButton
import android.widget.RadioGroup
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat

class SettingsActivity : AppCompatActivity() {

    private lateinit var config: ServerConfig
    private lateinit var printService: BluetoothPrintService

    private lateinit var inputServerUrl: EditText
    private lateinit var printerGroup: RadioGroup
    private lateinit var textNoPrinter: TextView

    @SuppressLint("MissingPermission")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_settings)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)

        config = ServerConfig(this)
        printService = BluetoothPrintService()

        inputServerUrl = findViewById(R.id.inputServerUrl)
        printerGroup = findViewById(R.id.printerGroup)
        textNoPrinter = findViewById(R.id.textNoPrinter)

        inputServerUrl.setText(config.serverUrl)

        findViewById<Button>(R.id.btnSave).setOnClickListener { onSave() }
        findViewById<Button>(R.id.btnReset).setOnClickListener { onReset() }
        findViewById<Button>(R.id.btnTestPrint).setOnClickListener { onTestPrint() }

        populatePrinters()
    }

    @SuppressLint("MissingPermission")
    private fun populatePrinters() {
        printerGroup.removeAllViews()

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_CONNECT)
            != PackageManager.PERMISSION_GRANTED
        ) {
            textNoPrinter.text = getString(R.string.print_permission_denied)
            return
        }

        val adapter = BluetoothAdapter.getDefaultAdapter() ?: run {
            textNoPrinter.text = getString(R.string.print_bluetooth_off)
            return
        }
        if (!adapter.isEnabled) {
            textNoPrinter.text = getString(R.string.print_bluetooth_off)
            return
        }

        val bonded = try {
            adapter.bondedDevices ?: emptySet()
        } catch (e: SecurityException) {
            textNoPrinter.text = getString(R.string.print_permission_denied)
            return
        }

        if (bonded.isEmpty()) {
            textNoPrinter.text = getString(R.string.printer_none)
            return
        }
        textNoPrinter.text = getString(R.string.printer_label)

        // Add "Automatic" option
        val autoRadio = RadioButton(this).apply {
            id = View.generateViewId()
            text = "Automatic (auto-detect)"
            isChecked = config.printerAddress.isNullOrBlank()
        }
        printerGroup.addView(autoRadio)

        bonded.forEach { device ->
            val name = try { device.name } catch (_: SecurityException) { null } ?: "(unknown)"
            val radio = RadioButton(this).apply {
                id = View.generateViewId()
                text = "$name  [${device.address}]"
                tag = device.address
                isChecked = config.printerAddress == device.address
            }
            printerGroup.addView(radio)
        }
    }

    private fun onSave() {
        val url = inputServerUrl.text.toString().trim()
        if (url.isEmpty()) {
            inputServerUrl.error = "Server URL is required"
            return
        }
        if (!url.startsWith("http://") && !url.startsWith("https://")) {
            inputServerUrl.error = "URL must start with http:// or https://"
            return
        }
        config.serverUrl = url

        val selectedId = printerGroup.checkedRadioButtonId
        if (selectedId != -1) {
            val radio = findViewById<RadioButton>(selectedId)
            val addr = radio.tag as? String
            config.printerAddress = addr
        }

        Toast.makeText(this, "Settings saved", Toast.LENGTH_SHORT).show()
        finish()
    }

    private fun onReset() {
        config.resetToDefaults()
        inputServerUrl.setText(ServerConfig.DEFAULT_SERVER_URL)
        populatePrinters()
    }

    private fun onTestPrint() {
        printService.print(
            ticketNo = "TEST",
            purpose = "Settings test print",
            preferredAddress = config.printerAddress,
            preferredNameHint = config.printerNameHint,
        ) { result ->
            runOnUiThread {
                val msg = when (result) {
                    is BluetoothPrintService.PrintResult.Success -> "Test print sent"
                    is BluetoothPrintService.PrintResult.Cancelled -> "Cancelled"
                    is BluetoothPrintService.PrintResult.Error -> "Failed: ${result.message}"
                }
                Toast.makeText(this, msg, Toast.LENGTH_LONG).show()
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        finish()
        return true
    }
}
