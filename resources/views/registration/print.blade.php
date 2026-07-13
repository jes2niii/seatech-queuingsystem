<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration #{{ $registration->id }} - Print</title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
            line-height: 1.3;
        }

        .no-print {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
        }
        .no-print button {
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            border: 1px solid #0E0057;
            background: #0E0057;
            color: #FDCD23;
            cursor: pointer;
        }
        .no-print button.secondary {
            background: #fff;
            color: #0E0057;
        }
        .no-print button:hover { opacity: 0.9; }

        /* ===== Header / Footer images ===== */
        .form-header-image,
        .form-footer-image {
            display: block;
            width: 100%;
            height: auto;
        }
        .form-header-image { margin-bottom: 8px; }
        .form-footer-image { margin-top: 10px; }

        /* ===== Form body ===== */
        .form-page {
            border: 1px solid #0E0057;
            padding: 10px 14px;
        }

        .top-row {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 14px;
            margin-bottom: 6px;
        }

        .italic-note {
            font-style: italic;
            font-weight: 600;
            font-size: 11px;
            margin-bottom: 4px;
            color: #000;
        }

        /* ===== Inline field (label + underline on same row) ===== */
        .inline-field {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 3px;
        }
        .inline-field > .label {
            font-size: 10.5px;
            font-weight: 700;
            color: #000;
            white-space: nowrap;
        }
        .inline-field > .value-line {
            flex-grow: 1;
            border-bottom: 1px solid #000;
            font-size: 12px;
            min-height: 16px;
            padding: 0 3px 1px 3px;
        }
        .inline-field.gender > .value-line {
            border-bottom: none;
        }

        /* ===== Above-line field (label above, underline below) ===== */
        .field {
            margin-bottom: 6px;
        }
        .field > .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #000;
            letter-spacing: 0.5px;
            text-align: center;
            margin-top: 1px;
        }
        .field > .value-line {
            border-bottom: 1px solid #000;
            font-size: 12px;
            min-height: 16px;
            text-align: center;
            padding: 0 3px 4px 3px;
        }

        /* ===== Checkbox row (enrollee type, referral type, gender) ===== */
        .checkbox-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 4px;
        }
        .check {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .check.indented {
            margin-left: 14px;
        }

        /* ===== Multi-column rows ===== */
        .name-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin: 2px 0 6px 0;
        }
        .row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
        .row-srn-app-rank {
            display: grid;
            grid-template-columns: 0.8fr 2fr 0.8fr;
            gap: 10px;
            align-items: baseline;
        }
        .address-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 4px;
        }

        /* ===== REFERRAL TYPE section (unboxed, open layout) ===== */
        .referral-section > .title {
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            color: #000;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }
        .ref-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 4px;
        }
        .ref-row-full {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 4px;
        }
        .ref-row-full > .label-inline {
            font-size: 12px;
            font-weight: 700;
            color: #000;
            white-space: nowrap;
        }
        .ref-row-full > .value-line {
            flex-grow: 1;
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 0 3px 1px 3px;
            font-size: 12px;
        }

        /* ===== Emergency contact row ===== */
        .emergency-row {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 16px;
            margin-bottom: 6px;
        }

        /* ===== Course / Schedule section ===== */
        .course-section {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 45px;
            margin-bottom: 8px;
        }
        .course-section .col-title {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }
        .course-line {
            border-bottom: 1px solid #000;
            height: 22px;
            font-size: 12px;
            padding: 2px 4px;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $checked = "\u{2611}";
        $unchecked = "\u{2610}";
        $isNew    = ($registration->enrollee_type === 'New Enrollee');
        $isOld    = ($registration->enrollee_type === 'Old Enrollee');
        $refType  = $registration->referral_type;
        $refSrc   = $registration->referral_source;
        $isMale   = ($registration->gender === 'Male');
        $isFemale = ($registration->gender === 'Female');
    @endphp

    <div class="no-print">
        <button class="secondary" type="button" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print this page</button>
    </div>

    {{-- Header image --}}
    <img src="{{ asset('img/header_logo.png') }}" class="form-header-image" alt="Registration Form Header">

    <div class="form-page">

        {{-- Top row: Enrollee + Referral side by side --}}
        <div class="top-row">
            {{-- LEFT: Enrollee section --}}
            <div>
                <div class="italic-note">Fill out this form completely and legibly.</div>

                <div class="checkbox-row">
                    <span class="check indented">
                        <span class="box {{ $isNew ? '' : 'unchecked' }}">{{ $isNew ? $checked : $unchecked }}</span>
                        New Enrollee
                    </span>
                    <span class="check indented">
                        <span class="box {{ $isOld ? '' : 'unchecked' }}">{{ $isOld ? $checked : $unchecked }}</span>
                        Old Enrollee
                    </span>
                </div>

                <div class="inline-field">
                    <span class="label">DATE OF ENROLEMENT:</span>
                    <span class="value-line">{{ $registration->enrollment_date?->format('F d, Y') }}</span>
                </div>
            </div>

            {{-- RIGHT: REFERRAL TYPE --}}
            <div class="referral-section">
                <div class="title">REFERRAL TYPE</div>

                <div class="ref-row-2col">
                    <span class="check">
                        <span class="box {{ $refType === 'Onsite/Walk-in' ? '' : 'unchecked' }}">{{ $refType === 'Onsite/Walk-in' ? $checked : $unchecked }}</span>
                        Onsite/Walk-in
                    </span>
                    <span class="check">
                        <span class="box {{ $refType === 'Online Enrollment' ? '' : 'unchecked' }}">{{ $refType === 'Online Enrollment' ? $checked : $unchecked }}</span>
                        Online Enrollment
                    </span>
                </div>

                <div class="ref-row-full">
                    <span class="check" style="margin: 0;">
                        <span class="box {{ $refType === 'Marketing' ? '' : 'unchecked' }}">{{ $refType === 'Marketing' ? $checked : $unchecked }}</span>
                    </span>
                    <span class="label-inline">Marketing:</span>
                    <span class="value-line">{{ $refType === 'Marketing' ? ($refSrc ?? '') : '' }}</span>
                </div>

                <div class="ref-row-full">
                    <span class="check" style="margin: 0;">
                        <span class="box {{ $refType === 'Company' ? '' : 'unchecked' }}">{{ $refType === 'Company' ? $checked : $unchecked }}</span>
                    </span>
                    <span class="label-inline">Company:</span>
                    <span class="value-line">{{ $refType === 'Company' ? ($refSrc ?? '') : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Enrollee section (full width) --}}

        <div style="display: grid; grid-template-columns: auto 1fr 1fr 1fr; gap: 10px; align-items: baseline; margin: 2px 0 6px 0;">
            <span style="font-size: 10.5px; font-weight: 700;">NAME:</span>
            <div class="field">
                <div class="value-line">{{ $registration->first_name }}</div>
                <div class="label">First Name</div>
            </div>
            <div class="field">
                <div class="value-line">{{ $registration->middle_name }}</div>
                <div class="label">Middle Name</div>
            </div>
            <div class="field">
                <div class="value-line">{{ $registration->last_name }}</div>
                <div class="label">Last Name</div>
            </div>
        </div>

        <div class="row-srn-app-rank">
            <div class="inline-field">
                <span class="label">SRN:</span>
                <span class="value-line">{{ $registration->srn }}</span>
            </div>
            <div class="inline-field">
                <span class="label">APPLICATION NO. (For Assessment)</span>
                <span class="value-line">{{ $registration->application_no }}</span>
            </div>
            <div class="inline-field">
                <span class="label">RANK:</span>
                <span class="value-line">{{ $registration->rank }}</span>
            </div>
        </div>

        <div class="row-3" style="margin-top: 4px;">
            <div class="inline-field">
                <span class="label">Email Address:</span>
                <span class="value-line">{{ $registration->email }}</span>
            </div>
            <div class="inline-field">
                <span class="label">Contact No.</span>
                <span class="value-line">{{ $registration->contact_no }}</span>
            </div>
            <div class="inline-field">
                <span class="label">Date of Birth:</span>
                <span class="value-line">{{ $registration->birthdate?->format('F d, Y') }}</span>
            </div>
        </div>

        <div class="row-3" style="margin-top: 4px;">
            <div class="inline-field">
                <span class="label">Place of Birth:</span>
                <span class="value-line">{{ $registration->place_of_birth }}</span>
            </div>
            <div class="inline-field">
                <span class="label">Civil Status:</span>
                <span class="value-line">{{ $registration->civil_status }}</span>
            </div>
            <div class="inline-field gender">
                <span class="label">Gender:</span>
                <span class="value-line" style="display: flex; gap: 12px; align-items: center;">
                    <span class="check" style="margin: 0;">
                        <span class="box {{ $isMale ? '' : 'unchecked' }}">{{ $isMale ? $checked : $unchecked }}</span> Male
                    </span>
                    <span class="check" style="margin: 0;">
                        <span class="box {{ $isFemale ? '' : 'unchecked' }}">{{ $isFemale ? $checked : $unchecked }}</span> Female
                    </span>
                </span>
            </div>
        </div>

        <div class="address-row" style="margin-top: 4px;">
            <div class="inline-field">
                <span class="label">Address:</span>
                <span class="value-line">{{ $registration->address }}</span>
            </div>
        </div>

        {{-- Emergency contact --}}
        <div class="emergency-row">
            <div class="inline-field">
                <span class="label">Emergency Contact Person:</span>
                <span class="value-line">{{ $registration->contact_person }}</span>
            </div>
            <div class="inline-field">
                <span class="label">Contact Number</span>
                <span class="value-line">{{ $registration->contact_mobile }}</span>
            </div>
        </div>

        {{-- Training / Assessment Course & Schedule --}}
        <div class="course-section">
            <div>
                <div class="col-title">TRAINING / ASSESSMENT COURSE</div>
                <div class="course-line">{{ $registration->course }}</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
            </div>
            <div>
                <div class="col-title">SCHEDULE</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
                <div class="course-line">&nbsp;</div>
            </div>
        </div>

    {{-- Footer image --}}
    <img src="{{ asset('img/footer.png') }}" class="form-footer-image" alt="Registration Form Footer">

    </div>

    {{-- <script>
        // Auto-open the browser print dialog when the page is ready.
        // Give the layout a moment to render before triggering the print dialog.
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script> --}}
</body>
</html>
