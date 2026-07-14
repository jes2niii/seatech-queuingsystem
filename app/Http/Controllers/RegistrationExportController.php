<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\RegistrationExcelService;
use Illuminate\Http\Request;

class RegistrationExportController extends Controller
{
    public function printExcel(Registration $registration, RegistrationExcelService $service)
    {
        try {
            return $service->streamDownload($registration);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to generate Excel: ' . $e->getMessage());
        }
    }

    /**
     * Render the registration as a print-friendly HTML page that mirrors the
     * Excel template layout. The view auto-triggers `window.print()` on load.
     */
    public function printHtml(Registration $registration)
    {
        $registration->loadMissing('ticket');
        return view('registration.print', compact('registration'));
    }

    /**
     * Render the populated Excel template as a PDF and stream it to the
     * browser. Served inline so the browser's PDF viewer can display it
     * and the user can print it from there.
     */
    public function printPdf(Registration $registration, RegistrationExcelService $service)
    {
        try {
            $pdf = $service->generatePdf($registration);

            $filename = sprintf(
                'registration_%d_%s.pdf',
                $registration->id,
                preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $registration->last_name) ?: 'export'
            );

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length'      => (string) strlen($pdf),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
}
