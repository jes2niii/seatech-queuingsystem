<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{ asset('css/userPurpose.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <title>SEATECH Queueing System</title>
</head>
<body>
    <div class="kiosk-body">
        <header class="kiosk-header">
            <img src="/img/seatechLogo.png" alt="SEATECH Logo">
            <div class="kiosk-header-text">
                <h1>SEATECH Maritime Training and Assessment Center Inc.</h1>
                <p>Legazpi &middot; Choose your purpose to get a queue number</p>
            </div>
        </header>

        <main class="kiosk-purposes">
            <button class="purpose-card" onclick="openRegistrationForm()">
                <div class="purpose-icon"><i class="bi bi-person-plus-fill"></i></div>
                <h3 class="purpose-title">REGISTRATION</h3>
                <p class="purpose-subtitle">ENROLLMENT</p>
            </button>

            <button class="purpose-card" onclick="showPopup('CERTIFICATE (RELEASING)')">
                <div class="purpose-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                <h3 class="purpose-title">CERTIFICATE</h3>
                <p class="purpose-subtitle">RELEASING</p>
            </button>

            <button class="purpose-card" onclick="showPopup('REGISTRATION (INQUIRY)')">
                <div class="purpose-icon"><i class="bi bi-question-circle-fill"></i></div>
                <h3 class="purpose-title">REGISTRATION</h3>
                <p class="purpose-subtitle">INQUIRY</p>
            </button>

            <button class="purpose-card" onclick="showPopup('CASHIER (PAYMENT)')">
                <div class="purpose-icon"><i class="bi bi-cash-coin"></i></div>
                <h3 class="purpose-title">CASHIER</h3>
                <p class="purpose-subtitle">PAYMENT</p>
            </button>
        </main>
    </div>

    <!-- TICKET MODAL -->
    <div id="popupModal" class="ticket-popup-overlay">
        <div class="ticket-popup">
            <h2 id="popupTitle"></h2>
            <p class="ticket-label">Your Queue Number</p>
            <div class="ticket-display" id="ticketNumber">----</div>
            <div class="ticket-actions">
                <button class="btn-popup btn-popup-cancel" onclick="closePopup()">Cancel</button>
                <button class="btn-popup btn-popup-confirm" onclick="confirmTicket()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- REGISTRATION MODAL -->
    <div id="registrationModal" class="registration-overlay">
        <div class="registration-modal">
            <div class="reg-modal-header">
                <div>
                    <h2>Student Registration Form</h2>
                    <div class="reg-header-sub">Fill out this form completely and legibly</div>
                </div>
                <button class="reg-close-btn" onclick="closeRegistrationForm()" aria-label="Close">&times;</button>
            </div>
            <form id="registrationForm" novalidate>
                <div class="reg-form-body">

                    <h4 class="reg-section-title">Enrollment Information</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Enrollee Type</label>
                            <div class="reg-radio-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enrolleeNew" value="New Enrollee">
                                    <label class="form-check-label" for="enrolleeNew">New Enrollee</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enrolleeOld" value="Old Enrollee">
                                    <label class="form-check-label" for="enrolleeOld">Old Enrollee</label>
                                </div>
                            </div>
                            <input type="hidden" name="enrollee_type" id="enrolleeTypeHidden">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date of Enrollment</label>
                            <input type="date" name="enrollment_date" id="enrollmentDate" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Referral Type</label>
                            <div class="reg-referral-row">
                                <label class="reg-referral-pill">
                                    <input class="form-check-input" type="radio" name="referral_type" id="refOnsite" value="Onsite/Walk-in">
                                    <span class="form-check-label">Onsite / Walk-in</span>
                                </label>
                                <label class="reg-referral-pill">
                                    <input class="form-check-input" type="radio" name="referral_type" id="refOnline" value="Online Enrollment">
                                    <span class="form-check-label">Online Enrollment</span>
                                </label>
                                <label class="reg-referral-pill">
                                    <input class="form-check-input" type="radio" name="referral_type" id="refMarketing" value="Marketing">
                                    <span class="form-check-label">Marketing</span>
                                    <input type="text" name="referral_source_marketing" id="refMarketingText" class="form-control reg-referral-text" placeholder="Specify" disabled>
                                </label>
                                <label class="reg-referral-pill">
                                    <input class="form-check-input" type="radio" name="referral_type" id="refCompany" value="Company">
                                    <span class="form-check-label">Company</span>
                                    <input type="text" name="referral_source_company" id="refCompanyText" class="form-control reg-referral-text" placeholder="Specify" disabled>
                                </label>
                            </div>
                            <input type="hidden" name="referral_source" id="referralSourceHidden">
                        </div>
                    </div>

                    <h4 class="reg-section-title">Personal Information</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="req">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">SRN</label>
                            <input type="text" name="srn" class="form-control" placeholder="Student Reference Number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Application No. <span style="color:var(--color-text-muted); font-weight:400;">(For Assessment)</span></label>
                            <input type="text" name="application_no" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Rank</label>
                            <input type="text" name="rank" class="form-control" placeholder="e.g. Deck Cadet, Third Officer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact No. <span class="req">*</span></label>
                            <input type="text" name="contact_no" class="form-control" placeholder="0912-345-6789" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="birthdate" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Place of Birth</label>
                            <input type="text" name="place_of_birth" class="form-control" placeholder="City, Province">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Civil Status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">Select…</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Gender</label>
                            <div class="reg-radio-group">
                                <label class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male">
                                    <span class="form-check-label">Male</span>
                                </label>
                                <label class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female">
                                    <span class="form-check-label">Female</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Street, City, Province">
                        </div>
                    </div>

                    <h4 class="reg-section-title">Emergency Contact</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_mobile" class="form-control" placeholder="0912-345-6789">
                        </div>
                    </div>

                </div>
                <div class="reg-modal-footer">
                    <button type="button" class="reg-btn reg-btn-cancel" onclick="closeRegistrationForm()">Cancel</button>
                    <button type="submit" class="reg-btn reg-btn-submit">Submit Registration</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast-notification"></div>

    <script src="{{ asset('js/userPurpose.js') }}"></script>
</body>
</html>
