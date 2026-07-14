let currentTicket = null;
let pendingRegistrationId = null;
let isGenerating = false;

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast-notification toast-' + type;
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => toast.classList.remove('show'), 3500);
}

function showPopup(title) {
    document.getElementById('popupTitle').innerText = title;
    document.getElementById('ticketNumber').innerText = '----';
    document.getElementById('popupModal').classList.add('active');

    fetch('/ticket/preview', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ purpose: title })
    })
    .then(res => res.json())
    .then(data => {
        currentTicket = data;
        document.getElementById('ticketNumber').innerText = data.ticket_no;
    })
    .catch(err => console.error(err));
}

function closePopup() {
    document.getElementById('popupModal').classList.remove('active');
    currentTicket = null;
}

function confirmTicket() {
    if (!currentTicket || isGenerating) return;

    isGenerating = true;
    const body = { purpose: currentTicket.purpose };
    const regId = pendingRegistrationId;
    if (regId) {
        body.registration_id = regId;
    }

    fetch('/ticket/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(body)
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(b => { throw new Error(b.error || b.message || 'HTTP ' + res.status); });
        }
        return res.json();
    })
    .then(data => {
        if (data.error) {
            showToast('Error generating ticket: ' + data.error, 'error');
            return;
        }

        if (regId) {
            pendingRegistrationId = null;
        }

        closePopup();

        if (window.KioskPrint) {
            KioskPrint.print(data.ticket_no, data.purpose);
        } else {
            const printURL = `my.bluetoothprint.scheme://${window.location.origin}/ticket/print-response?ticket=${data.ticket_no}&purpose=${encodeURIComponent(data.purpose)}`;
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = printURL;
            document.body.appendChild(iframe);
            setTimeout(() => iframe.remove(), 5000);
            showToast('Ticket printed successfully!', 'success');
        }
    })
    .catch(err => showToast('Print failed: ' + err.message, 'error'))
    .finally(() => {
        isGenerating = false;
    });
}

function openRegistrationForm() {
    let dateInput = document.getElementById('enrollmentDate');
    if (dateInput && !dateInput.value) {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        dateInput.value = yyyy + '-' + mm + '-' + dd;
    }
    document.getElementById('registrationModal').classList.add('active');
}

function closeRegistrationForm() {
    document.getElementById('registrationModal').classList.remove('active');
    document.getElementById('registrationForm').reset();
    document.getElementById('enrolleeTypeHidden').value = '';
    document.getElementById('referralSourceHidden').value = '';
    document.getElementById('refMarketingText').value = '';
    document.getElementById('refMarketingText').disabled = true;
    document.getElementById('refCompanyText').value = '';
    document.getElementById('refCompanyText').disabled = true;
}

document.addEventListener('DOMContentLoaded', function () {
    let newCheck = document.getElementById('enrolleeNew');
    let oldCheck = document.getElementById('enrolleeOld');
    let enrolleeHidden = document.getElementById('enrolleeTypeHidden');

    if (newCheck && oldCheck) {
        newCheck.addEventListener('change', function () {
            if (this.checked) {
                oldCheck.checked = false;
                enrolleeHidden.value = this.value;
            } else if (!oldCheck.checked) {
                enrolleeHidden.value = '';
            }
        });
        oldCheck.addEventListener('change', function () {
            if (this.checked) {
                newCheck.checked = false;
                enrolleeHidden.value = this.value;
            } else if (!newCheck.checked) {
                enrolleeHidden.value = '';
            }
        });
    }

    let refMarketing = document.getElementById('refMarketing');
    let refCompany = document.getElementById('refCompany');
    let refMarketingText = document.getElementById('refMarketingText');
    let refCompanyText = document.getElementById('refCompanyText');
    let referralSourceHidden = document.getElementById('referralSourceHidden');

    function syncReferralSource() {
        if (refMarketing.checked) {
            referralSourceHidden.value = refMarketingText.value;
        } else if (refCompany.checked) {
            referralSourceHidden.value = refCompanyText.value;
        } else {
            referralSourceHidden.value = '';
        }
    }

    if (refMarketing && refCompany) {
        refMarketing.addEventListener('change', function () {
            refMarketingText.disabled = !this.checked;
            refCompany.checked = false;
            refCompanyText.disabled = true;
            refCompanyText.value = '';
            if (!this.checked) refMarketingText.value = '';
            syncReferralSource();
        });
        refCompany.addEventListener('change', function () {
            refCompanyText.disabled = !this.checked;
            refMarketing.checked = false;
            refMarketingText.disabled = true;
            refMarketingText.value = '';
            if (!this.checked) refCompanyText.value = '';
            syncReferralSource();
        });
        refMarketingText.addEventListener('input', syncReferralSource);
        refCompanyText.addEventListener('input', syncReferralSource);
    }
});

document.getElementById('registrationForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};
    formData.forEach((val, key) => {
        if (key === 'referral_source_marketing' || key === 'referral_source_company') return;
        data[key] = val;
    });

    fetch('/registration/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json().then(body => ({ status: res.status, body })))
    .then(result => {
        if (result.status === 422) {
            const errors = result.body.errors;
            let msg = '';
            for (const key in errors) {
                msg += errors[key].join('\n') + '\n';
            }
            showToast(msg, 'error');
            return;
        }
        if (result.status >= 400) {
            showToast(result.body.message || 'Submission failed.', 'error');
            return;
        }
        closeRegistrationForm();
        pendingRegistrationId = result.body.id;

        showPopup('REGISTRATION (ENROLLMENT)');

        setTimeout(() => {
            confirmTicket();
        }, 1500);
    })
    .catch(err => showToast('Error: ' + err.message, 'error'));
});
