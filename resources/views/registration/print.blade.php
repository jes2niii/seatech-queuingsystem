<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration #{{ $registration->id }} - Print</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a2e;
            margin: 0;
            padding: 20px;
            background: #fff;
        }

        .no-print {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 16px;
        }

        .no-print button {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
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

        .page {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #0E0057;
            border-radius: 6px;
            padding: 24px;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px solid #0E0057;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .page-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .page-header .title h1 {
            margin: 0;
            font-size: 18px;
            color: #0E0057;
            font-weight: 700;
        }

        .page-header .title p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #6c757d;
        }

        .ticket-banner {
            background: #0E0057;
            color: #FDCD23;
            padding: 10px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .ticket-banner .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .ticket-banner .value {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 2px;
            font-variant-numeric: tabular-nums;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #0E0057;
            border-bottom: 2px solid #FDCD23;
            padding-bottom: 4px;
            margin: 18px 0 10px 0;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px 16px;
            font-size: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .field .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            font-weight: 600;
        }

        .field .value {
            color: #1a1a2e;
            font-weight: 500;
            word-break: break-word;
            min-height: 16px;
        }

        .field.span-2 { grid-column: span 2; }
        .field.span-3 { grid-column: span 3; }

        .checkbox-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            margin: 4px 0 8px 0;
        }

        .check {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }

        .check .box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1.5px solid #0E0057;
            border-radius: 2px;
            position: relative;
        }

        .check.checked .box::after {
            content: "✓";
            color: #0E0057;
            font-size: 14px;
            font-weight: 900;
            position: absolute;
            top: -3px;
            left: 1px;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page {
                border: none;
                padding: 0;
                max-width: none;
            }
            .section-title { break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="secondary" type="button" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print this page</button>
    </div>

    <div class="page">

        <div class="page-header">
            <img src="{{ asset('img/seatechLogo.png') }}" alt="Logo">
            <div class="title">
                <h1>SEATECH Maritime Training &amp; Assessment Center Inc.</h1>
                <p>Student Registration Form</p>
            </div>
        </div>

        @if($registration->ticket)
        <div class="ticket-banner">
            <div>
                <div class="label">Ticket Number</div>
                <div class="value">{{ $registration->ticket->ticket_no }}</div>
            </div>
            <div style="text-align: right;">
                <div class="label">Purpose</div>
                <div style="font-weight: 600; font-size: 13px;">{{ $registration->ticket->purpose }}</div>
            </div>
        </div>
        @endif

        <div class="section-title">Enrollment Information</div>

        <div class="checkbox-row">
            @php $isNew = ($registration->enrollee_type === 'New Enrollee'); $isOld = ($registration->enrollee_type === 'Old Enrollee'); @endphp
            <span class="check {{ $isNew ? 'checked' : '' }}"><span class="box"></span> New Enrollee</span>
            <span class="check {{ $isOld ? 'checked' : '' }}"><span class="box"></span> Old Enrollee</span>
        </div>

        @php
            $isMarketing = ($registration->referral_type === 'Marketing');
            $isCompany = ($registration->referral_type === 'Company');
        @endphp
        <div class="checkbox-row">
            <span class="check {{ $registration->referral_type === 'Onsite/Walk-in' ? 'checked' : '' }}"><span class="box"></span> Onsite/Walk-in</span>
            <span class="check {{ $registration->referral_type === 'Online Enrollment' ? 'checked' : '' }}"><span class="box"></span> Online Enrollment</span>
            <span class="check {{ $isMarketing ? 'checked' : '' }}"><span class="box"></span> Marketing: <strong>{{ $isMarketing ? ($registration->referral_source ?? '') : '' }}</strong></span>
            <span class="check {{ $isCompany ? 'checked' : '' }}"><span class="box"></span> Company: <strong>{{ $isCompany ? ($registration->referral_source ?? '') : '' }}</strong></span>
        </div>

        <div class="field-grid" style="grid-template-columns: 1fr 1fr; margin-top: 6px;">
            <div class="field">
                <div class="label">Date of Enrollment</div>
                <div class="value">{{ $registration->enrollment_date?->format('F d, Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="section-title">Personal Information</div>

        <div class="field-grid">
            <div class="field">
                <div class="label">First Name</div>
                <div class="value">{{ $registration->first_name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Middle Name</div>
                <div class="value">{{ $registration->middle_name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Last Name</div>
                <div class="value">{{ $registration->last_name ?? '—' }}</div>
            </div>

            <div class="field">
                <div class="label">SRN</div>
                <div class="value">{{ $registration->srn ?? '—' }}</div>
            </div>
            <div class="field span-2">
                <div class="label">Application No. (For Assessment)</div>
                <div class="value">{{ $registration->application_no ?? '—' }}</div>
            </div>

            <div class="field">
                <div class="label">Rank</div>
                <div class="value">{{ $registration->rank ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Course</div>
                <div class="value">{{ $registration->course ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Email Address</div>
                <div class="value">{{ $registration->email ?? '—' }}</div>
            </div>

            <div class="field">
                <div class="label">Contact No.</div>
                <div class="value">{{ $registration->contact_no ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Date of Birth</div>
                <div class="value">{{ $registration->birthdate?->format('F d, Y') ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Civil Status</div>
                <div class="value">{{ $registration->civil_status ?? '—' }}</div>
            </div>

            <div class="field">
                <div class="label">Place of Birth</div>
                <div class="value">{{ $registration->place_of_birth ?? '—' }}</div>
            </div>
            <div class="field span-2">
                <div class="label">Gender</div>
                <div class="value">{{ $registration->gender ?? '—' }}</div>
            </div>

            <div class="field span-3">
                <div class="label">Address</div>
                <div class="value">{{ $registration->address ?? '—' }}</div>
            </div>
        </div>

        <div class="section-title">Emergency Contact</div>

        <div class="field-grid">
            <div class="field">
                <div class="label">Contact Person</div>
                <div class="value">{{ $registration->contact_person ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Relationship</div>
                <div class="value">{{ $registration->relationship ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Mobile No.</div>
                <div class="value">{{ $registration->contact_mobile ?? '—' }}</div>
            </div>
        </div>

        <div class="footer-note">
            Generated on {{ now()->format('F d, Y h:i A') }} &middot; Registration ID #{{ $registration->id }}
        </div>
    </div>

    <script>
        // Auto-open the browser print dialog when the page is ready.
        // Give the layout a moment to render before triggering the print dialog.
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
