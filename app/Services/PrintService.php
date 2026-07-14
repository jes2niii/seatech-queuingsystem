<?php
namespace App\Services;

class PrintService
{
    private string $type;

    public function __construct()
    {
        $this->type = env('PRINTER_TYPE', 'file');
    }

    public function printTicket(string $ticketNo, string $purpose): bool
    {
        $data = $this->buildESCData($ticketNo, $purpose);

        return match ($this->type) {
            'com'  => $this->printCom($data),
            'tcp'  => $this->printTcp($data),
            'win'  => $this->printWin($data),
            default => $this->printFile($data),
        };
    }

    private function buildESCData(string $ticketNo, string $purpose): string
    {
        $lines = [
            "\x1B\x40",
            "\x1B\x61\x01",
            "Purpose: {$purpose}\n",
            "\x1B\x61\x01",
            "\x1B\x21\x30",
            "Ticket No: {$ticketNo}\n",
            "\x1B\x21\x00",
            "\nThank you for visiting!\n",
            "\n\n\n\n",
            "\x1D\x56\x42\x00",
        ];

        return implode('', $lines);
    }

    private function printCom(string $data): bool
    {
        $port = env('PRINTER_COM_PORT', 'COM3');

        $handle = @fopen($port, 'wb');

        if (! $handle) {
            $error = error_get_last();
            throw new \RuntimeException(
                'Cannot open ' . $port . '.' . ($error ? ' ' . $error['message'] : '')
            );
        }

        stream_set_timeout($handle, 5);
        fwrite($handle, $data);
        fclose($handle);

        return true;
    }

    private function printTcp(string $data): bool
    {
        $host = env('PRINTER_TCP_HOST', '192.168.0.100');
        $port = (int) env('PRINTER_TCP_PORT', 9100);

        $fp = @fsockopen($host, $port, $errno, $errstr, 5);

        if (! $fp) {
            throw new \RuntimeException("Cannot connect to {$host}:{$port} ({$errno}: {$errstr}).");
        }

        stream_set_timeout($fp, 5);
        fwrite($fp, $data);
        fclose($fp);

        return true;
    }

    private function printWin(string $data): bool
    {
        $printerName = env('PRINTER_WIN_NAME', 'POS58');
        $dir = storage_path('temp');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $tempFile = $dir . DIRECTORY_SEPARATOR . 'print_' . uniqid() . '.bin';
        file_put_contents($tempFile, $data);

        $share = '\\\\localhost\\' . $printerName;
        exec('copy /B "' . $tempFile . '" "' . $share . '" 2>&1', $output, $code);

        @unlink($tempFile);

        if ($code !== 0) {
            throw new \RuntimeException(
                'Print failed: ' . implode(' ', $output) . ' (code ' . $code . ').'
            );
        }

        return true;
    }

    private function printFile(string $data): bool
    {
        $path = env('PRINTER_FILE_PATH', storage_path('logs/print_output.txt'));
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $data);

        return true;
    }
}
