<?php

namespace App\Services;

use App\Models\Registration;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RegistrationExcelService
{
    private string $templatePath;

    public function __construct()
    {
        $this->templatePath = storage_path('app/templates/registration_template.xlsx');
    }

    /**
     * Cell-coordinate mapping for the registration template.
     * Keys are cell addresses (e.g., 'C5'), values are Registration model
     * fields. The cell is written with the field's value (or left as-is
     * in the template if the value is null).
     *
     * Edit this map to match the layout of registration_template.xlsx.
     */
    protected array $cellMapping = [
        'C5'  => 'first_name',
        'L5'  => 'middle_name',
        'S5'  => 'last_name',
        'C7'  => 'srn',
        'M7'  => 'application_no',
        'U7'  => 'rank',
        'E8'  => 'email',
        'M8'  => 'contact_no',
        'T8'  => 'birthdate',
        'E9'  => 'place_of_birth',
        'O9'  => 'civil_status',
        'D10' => 'address',
        'G4'  => 'enrollment_date',
        'H11' => 'contact_person',
        'S11' => 'contact_mobile',
        'C13' => 'course',
    ];

    /**
     * Checkbox mappings: each Registration field's value is matched against
     * an option, and the corresponding cell is written with a Unicode
     * glyph (☑ for checked, ☐ for unchecked) using the Calibri font.
     * This is compatible with MS Office Excel, LibreOffice, Google Sheets,
     * Numbers, and the Dompdf PDF renderer — no Form Control checkboxes
     * are required in the template.
     */
    protected array $checkboxMapping = [
        'enrollee_type' => [
            'New Enrollee' => 'E2',
            'Old Enrollee' => 'E3',
        ],
        'referral_type' => [
            'Onsite/Walk-in'    => 'N2',
            'Online Enrollment' => 'T2',
            'Marketing'         => 'N3',
            'Company'           => 'N4',
        ],
        'gender' => [
            'Male'   => 'U9',
            'Female' => 'W9',
        ],
    ];

    private const CHECKBOX_FONT   = 'Calibri';
    private const CHECKED_GLYPH   = "\u{2611}";   // ☑
    private const UNCHECKED_GLYPH = "\u{2610}";   // ☐

    /**
     * Generate a populated Spreadsheet for the given Registration.
     *
     * @throws \RuntimeException if the template file is missing.
     */
    public function generateForRegistration(Registration $registration): Spreadsheet
    {
        if (! file_exists($this->templatePath)) {
            throw new \RuntimeException(
                'Registration template not found at: ' . $this->templatePath
                . '. Please paste your registration_template.xlsx file there.'
            );
        }

        $registration->loadMissing('ticket');

        $spreadsheet = IOFactory::load($this->templatePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $values = $this->buildValueMap($registration);

        foreach ($this->cellMapping as $cellAddress => $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $value = $values[$field];
            if ($value !== null) {
                $worksheet->setCellValue($cellAddress, $value);
            }
        }

        foreach ($this->checkboxMapping as $field => $options) {
            $value = $values[$field] ?? null;
            foreach ($options as $optionValue => $cellAddress) {
                $glyph = ($value === $optionValue) ? self::CHECKED_GLYPH : self::UNCHECKED_GLYPH;
                $worksheet->setCellValue($cellAddress, $glyph);
                $worksheet->getStyle($cellAddress)
                    ->getFont()
                    ->setName(self::CHECKBOX_FONT);
            }
        }

        return $spreadsheet;
    }

    /**
     * Build a field => value map for the registration, including related
     * ticket fields when available.
     */
    private function buildValueMap(Registration $registration): array
    {
        $birthdate = $registration->birthdate;
        $enrollmentDate = $registration->enrollment_date;

        return [
            'enrollee_type'    => $registration->enrollee_type,
            'first_name'       => $registration->first_name,
            'middle_name'      => $registration->middle_name,
            'last_name'        => $registration->last_name,
            'srn'              => $registration->srn,
            'application_no'   => $registration->application_no,
            'rank'             => $registration->rank,
            'course'           => $registration->course,
            'email'            => $registration->email,
            'contact_no'       => $registration->contact_no,
            'birthdate'        => $birthdate ? $birthdate->format('Y-m-d') : null,
            'place_of_birth'   => $registration->place_of_birth,
            'civil_status'     => $registration->civil_status,
            'gender'           => $registration->gender,
            'address'          => $registration->address,
            'referral_type'    => $registration->referral_type,
            'referral_source'  => $registration->referral_source,
            'enrollment_date'  => $enrollmentDate ? $enrollmentDate->format('Y-m-d') : null,
            'contact_person'   => $registration->contact_person,
            'relationship'     => $registration->relationship,
            'contact_mobile'   => $registration->contact_mobile,
            'ticket_no'        => $registration->ticket?->ticket_no,
            'purpose'          => $registration->ticket?->purpose,
        ];
    }

    /**
     * Stream the populated spreadsheet to the browser as a download.
     */
    public function streamDownload(Registration $registration): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = $this->generateForRegistration($registration);

        $filename = sprintf(
            'registration_%d_%s.xlsx',
            $registration->id,
            preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $registration->last_name) ?: 'export'
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Render the populated spreadsheet as a PDF (using the same cell layout
     * as the Excel file) and return the raw PDF bytes.
     *
     * Requires the `dompdf/dompdf` Composer package.
     *
     * @throws \RuntimeException if the template file is missing or PDF
     *                           generation fails.
     */
    public function generatePdf(Registration $registration): string
    {
        $spreadsheet = $this->generateForRegistration($registration);

        // Set A4 paper size and fit-to-page so the printout is readable.
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.5)->setBottom(0.5)->setLeft(0.5);
        $sheet->setShowGridlines(false);

        $writer = new Dompdf($spreadsheet);
        // The Dompdf writer auto-detects paper size, orientation, and
        // margins from the spreadsheet's page setup, so no extra config
        // is required here.

        $tempFile = tempnam(sys_get_temp_dir(), 'reg_pdf_') . '.pdf';
        try {
            $writer->save($tempFile);
            $contents = file_get_contents($tempFile);
            if ($contents === false) {
                throw new \RuntimeException('Failed to read generated PDF file.');
            }
            return $contents;
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
