package com.seatech.kiosk

import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothSocket
import android.os.Build
import java.io.IOException
import java.io.OutputStream
import java.util.UUID
import java.util.concurrent.atomic.AtomicBoolean

/**
 * Thread-safe Bluetooth SPP printing service for ESC/POS thermal printers.
 *
 * Improvements over the original:
 *  - Mutex-style guard via [AtomicBoolean] to prevent concurrent prints
 *  - Real connection timeout enforced via a dedicated connect thread
 *  - Limited automatic retries for transient connection failures
 *  - Clearer error categorisation
 *  - Configurable printer address / name match
 *  - Standard socket method tried first; reflection-based fallback only if needed
 */
class BluetoothPrintService(
    private val connectionTimeoutMs: Int = 10_000,
    private val maxRetries: Int = 1
) {

    private var socket: BluetoothSocket? = null
    private val busy = AtomicBoolean(false)
    private val cancelled = AtomicBoolean(false)

    private val sppUuid = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

    /** Print the given ticket using the configured/default printer. */
    fun print(
        ticketNo: String,
        purpose: String,
        preferredAddress: String? = null,
        preferredNameHint: String = "",
        callback: (PrintResult) -> Unit
    ) {
        if (!busy.compareAndSet(false, true)) {
            callback(PrintResult.Error("Printer is busy. Please wait."))
            return
        }
        cancelled.set(false)

        Thread {
            try {
                val result = runWithRetries(ticketNo, purpose, preferredAddress, preferredNameHint)
                callback(result)
            } finally {
                busy.set(false)
            }
        }.start()
    }

    private fun runWithRetries(
        ticketNo: String,
        purpose: String,
        preferredAddress: String?,
        preferredNameHint: String,
    ): PrintResult {
        var attempt = 0
        var lastError: PrintResult.Error? = null
        while (attempt <= maxRetries) {
            if (cancelled.get()) return PrintResult.Cancelled
            val r = tryPrintOnce(ticketNo, purpose, preferredAddress, preferredNameHint)
            if (r is PrintResult.Success) return r
            if (r is PrintResult.Error) lastError = r
            // Don't retry on permission / no-printer / cancelled errors
            if (r is PrintResult.Error && !r.retryable) return r
            attempt++
        }
        return lastError ?: PrintResult.Error("Unknown error", retryable = false)
    }

    private fun tryPrintOnce(
        ticketNo: String,
        purpose: String,
        preferredAddress: String?,
        preferredNameHint: String,
    ): PrintResult {
        val adapter = try {
            BluetoothAdapter.getDefaultAdapter()
        } catch (e: SecurityException) {
            return PrintResult.Error("Bluetooth permission denied", retryable = false)
        } ?: return PrintResult.Error("Bluetooth not available on this device", retryable = false)

        if (!adapter.isEnabled) {
            return PrintResult.Error("Bluetooth is turned off", retryable = false)
        }

        val device = pickPrinter(adapter, preferredAddress, preferredNameHint)
            ?: return PrintResult.Error(
                "Printer not paired. Pair it in Android Bluetooth settings first.",
                retryable = false
            )

        // First try the standard socket; fall back to reflection if it fails.
        var s = createStandardSocket(device)
        if (s == null) {
            s = createReflectedSocket(device)
        }
        if (s == null) {
            return PrintResult.Error("Could not open socket", retryable = true)
        }
        socket = s
        adapter.cancelDiscovery()

        val connectResult = connectWithTimeout(s)
        if (connectResult is ConnectResult.Failed) {
            close()
            return PrintResult.Error(connectResult.message, retryable = connectResult.retryable)
        }
        if (cancelled.get()) {
            close()
            return PrintResult.Cancelled
        }

        val out: OutputStream = try {
            s.outputStream
        } catch (e: IOException) {
            close()
            return PrintResult.Error("No output stream: ${e.message}", retryable = true)
        }

        return try {
            out.write(buildESCData(ticketNo, purpose))
            out.flush()
            close()
            PrintResult.Success
        } catch (e: IOException) {
            close()
            PrintResult.Error("Write failed: ${e.message}", retryable = true)
        }
    }

    private sealed class ConnectResult {
        object Success : ConnectResult()
        data class Failed(val message: String, val retryable: Boolean) : ConnectResult()
    }

    /**
     * Performs the blocking [BluetoothSocket.connect] call but enforces
     * the configured [connectionTimeoutMs] by interrupting the socket from
     * a watchdog thread.
     */
    private fun connectWithTimeout(s: BluetoothSocket): ConnectResult {
        val connectThread = Thread {
            try {
                s.connect()
            } catch (_: Exception) {
                // Errors handled via the result of this method / close()
            }
        }
        connectThread.isDaemon = true
        connectThread.start()

        val deadline = System.currentTimeMillis() + connectionTimeoutMs
        while (connectThread.isAlive) {
            if (cancelled.get()) {
                close()
                return ConnectResult.Failed("Cancelled", retryable = false)
            }
            if (System.currentTimeMillis() > deadline) {
                close()
                return ConnectResult.Failed(
                    "Connection timed out after ${connectionTimeoutMs / 1000}s",
                    retryable = true
                )
            }
            try {
                Thread.sleep(100)
            } catch (_: InterruptedException) {
                return ConnectResult.Failed("Interrupted", retryable = true)
            }
        }
        return try {
            // If the socket is connected we're good. If connect() threw, isConnected is false.
            if (s.isConnected) ConnectResult.Success
            else ConnectResult.Failed("Could not connect to printer", retryable = true)
        } catch (_: Exception) {
            ConnectResult.Failed("Connection error", retryable = true)
        }
    }

    private fun pickPrinter(
        adapter: BluetoothAdapter,
        preferredAddress: String?,
        preferredNameHint: String
    ): BluetoothDevice? {
        val bonded = try {
            adapter.bondedDevices
        } catch (e: SecurityException) {
            return null
        } ?: return null

        // 1. Preferred address (if set)
        if (!preferredAddress.isNullOrBlank()) {
            bonded.find { it.address == preferredAddress }?.let { return it }
        }

        // 2. Name hint from settings
        if (preferredNameHint.isNotBlank()) {
            val hint = preferredNameHint.uppercase()
            bonded.find { it.name?.uppercase()?.contains(hint) == true }?.let { return it }
        }

        // 3. Default heuristic match (common thermal printer names)
        val knownBrands = listOf("OC-58", "POS", "PRINTER", "YICHIP", "RPP", "MTP")
        bonded.find { d ->
            val n = d.name?.uppercase() ?: ""
            knownBrands.any { n.contains(it) }
        }?.let { return it }

        // 4. Any device whose name contains "print"
        bonded.find { d ->
            val n = d.name?.lowercase() ?: ""
            n.contains("print")
        }?.let { return it }

        return null
    }

    private fun createStandardSocket(device: BluetoothDevice): BluetoothSocket? {
        return try {
            device.createRfcommSocketToServiceRecord(sppUuid)
        } catch (_: SecurityException) {
            null
        } catch (_: IOException) {
            null
        } catch (_: Exception) {
            null
        }
    }

    /**
     * Some printers require reflection-based fallback (well-known Android workaround).
     * Only used if the standard method fails to open a usable socket.
     */
    private fun createReflectedSocket(device: BluetoothDevice): BluetoothSocket? {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.GINGERBREAD_MR1) return null
        return try {
            val method = device.javaClass.getMethod("createRfcommSocket", Int::class.javaPrimitiveType)
            method.invoke(device, 1) as? BluetoothSocket
        } catch (_: Exception) {
            null
        }
    }

    fun cancel() {
        cancelled.set(true)
        close()
    }

    private fun close() {
        try { socket?.close() } catch (_: Exception) {}
        socket = null
    }

    private fun buildESCData(ticketNo: String, purpose: String): ByteArray {
        val sb = StringBuilder()
        sb.append("\u001B\u0040")             // ESC @ (initialize)
        sb.append("\u001B\u0061\u0001")         // center align
        sb.append("Purpose: ").append(purpose).append('\n')
        sb.append("\u001B\u0021\u0030")         // double width + height
        sb.append("Ticket No: ").append(ticketNo).append('\n')
        sb.append("\u001B\u0021\u0000")         // normal size
        sb.append('\n')
        sb.append("Thank you for visiting!\n")
        sb.append("\n\n\n")
        sb.append("\u001D\u0056\u0042\u0000")   // partial cut
        return sb.toString().toByteArray(Charsets.UTF_8)
    }

    sealed class PrintResult {
        object Success : PrintResult()
        object Cancelled : PrintResult()
        data class Error(val message: String, val retryable: Boolean = false) : PrintResult()
    }
}
