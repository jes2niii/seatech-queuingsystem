//For Table of Tickets

 function attachRowClick() {
    document.querySelectorAll('.ticket-row').forEach(row => {
        row.onclick = function () {
            // Remove highlight from all rows
            document.querySelectorAll('.ticket-row')
                .forEach(r => r.classList.remove('selected'));

            // Highlight the clicked row
            this.classList.add('selected');
            selectedTicketId = this.dataset.ticketId;

            // Save the selected ID in hidden input
            document.getElementById('selectedTicket').value = selectedTicketId;
        };
    });
}

// Initial attach
attachRowClick();

// Auto refresh table
setInterval(() => {
    fetch('/tickets/live')
        .then(res => res.text())
        .then(html => {
            document.getElementById('ticketBody').innerHTML = html;

            // Re-attach click events
            attachRowClick();

            // Re-highlight the previously selected row if it still exists
            if (selectedTicketId) {
                const selectedRow = document.querySelector(`.ticket-row[data-ticket-id="${selectedTicketId}"]`);
                if (selectedRow) {
                    selectedRow.classList.add('selected');
                } else {
                    // If ticket no longer exists, clear selection
                    selectedTicketId = null;
                    document.getElementById('selectedTicket').value = '';
                }
            }
        });
}, 2000);

let selectedTicketId = null;
let callModalInstance = null;
let lastCallData = null;


function submitAction(action) {
    if (!selectedTicketId) {
        alert('Please select a ticket first');
        return;
    }

    if (action === 'call') {
        let sound = document.getElementById('callSound');
        if (sound) { sound.currentTime = 0; sound.play().catch(() => {}); }

        let token = document.querySelector('input[name="_token"]').value;

        fetch('/tickets/action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: selectedTicketId,
                action: 'call',
            }),
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(body => {
                    throw new Error(body.message || body.error || 'Server returned ' + res.status);
                });
            }
            return res.json();
        })
        .then(data => {
            showCallModal(data);
        })
        .catch(err => {
            alert('Failed to call the ticket: ' + err.message);
        });
    } else {
        document.getElementById('ticketAction').value = action;
        document.getElementById('ticketActionForm').action = `/tickets/action`;
        document.getElementById('ticketActionForm').submit();
    }
}

function showCallModal(data) {
    let ticket = data.ticket;
    let reg = data.registration;

    document.getElementById('nowServingDisplay').textContent = ticket.ticket_no;

    document.getElementById('callTicketNumber').textContent = ticket.ticket_no;
    document.getElementById('callTicketPurpose').textContent = ticket.purpose;

    let regInfo = document.getElementById('callRegistrationInfo');
    regInfo.innerHTML = '';
    if (reg) {
        let referralValue = reg.referral_type || '—';
        if (reg.referral_source) {
            referralValue += ' — ' + reg.referral_source;
        }

        regInfo.appendChild(buildRegTable('Enrollment Information', [
            ['Enrollee Type', reg.enrollee_type],
            ['Referral Type', referralValue],
            ['Date Enrolled', formatDate(reg.enrollment_date)],
            ['SRN', reg.srn],
            ['Application No.', reg.application_no],
        ]));

        regInfo.appendChild(buildRegTable('Personal Information', [
            ['First Name', reg.first_name],
            ['Middle Name', reg.middle_name],
            ['Last Name', reg.last_name],
            ['Address', reg.address],
            ['Gender', reg.gender],
            ['Date of Birth', formatDate(reg.birthdate)],
            ['Civil Status', reg.civil_status],
            ['Place of Birth', reg.place_of_birth],
            ['Email', reg.email, 'email'],
            ['Contact No.', reg.contact_no, 'phone'],
            ['Rank', reg.rank],
            ['Course', reg.course],
        ]));

        regInfo.appendChild(buildRegTable('Emergency Contact', [
            ['Contact Person', reg.contact_person],
            ['Relationship', reg.relationship],
            ['Contact Number', reg.contact_mobile, 'phone'],
        ]));
    } else {
        let p = document.createElement('p');
        p.className = 'call-modal-no-reg';
        p.textContent = 'No registration information for this ticket.';
        regInfo.appendChild(p);
    }

    // Show or hide the Print button depending on whether a registration is linked
    const printHtmlBtn = document.getElementById('btnCallPrintHtml');
    const editBtn = document.getElementById('btnCallEdit');
    if (reg && reg.id) {
        printHtmlBtn.style.display = '';
        printHtmlBtn.dataset.registrationId = reg.id;
        editBtn.style.display = '';
        editBtn.dataset.registrationId = reg.id;
    } else {
        printHtmlBtn.style.display = 'none';
        printHtmlBtn.dataset.registrationId = '';
        editBtn.style.display = 'none';
        editBtn.dataset.registrationId = '';
    }

    lastCallData = data;

    if (!callModalInstance) {
        callModalInstance = new bootstrap.Modal(document.getElementById('callModal'));
    }

    document.getElementById('btnCallDone').dataset.ticketId = ticket.id;
    callModalInstance.show();
}

document.getElementById('btnCallPrintHtml').addEventListener('click', function () {
    const regId = this.dataset.registrationId;
    if (!regId) return;
    const url = '/registration/' + encodeURIComponent(regId) + '/print';
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');

    const callModal = bootstrap.Modal.getInstance(document.getElementById('callModal'));
    if (callModal) {
        callModal.hide();
    }
    showDashboardToast('Print preview opened — use the browser print button.');
});

/**
 * Show a brief toast on the dashboard (for actions that don't have a server
 * flash message, such as the call-modal "Print" button).
 */
function showDashboardToast(message) {
    let toast = document.getElementById('dashboardToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'dashboardToast';
        toast.className = 'dashboard-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(showDashboardToast._t);
    showDashboardToast._t = setTimeout(() => toast.classList.remove('show'), 3500);
}

/**
 * Format an ISO 8601 date string (e.g. "2026-07-13T00:00:00.000000Z")
 * to a human-readable "July 13, 2026" format.
 * Parses the Y-M-D prefix directly to avoid timezone shift issues.
 */
function formatDate(isoString) {
    if (!isoString) return '—';
    var parts = isoString.split('T')[0].split('-');
    var months = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    return months[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
}

/**
 * Build a registration detail table safely using DOM APIs (no innerHTML).
 * @param {string} title
 * @param {Array<[string, string|undefined|null, string?]>} rows
 * @returns {DocumentFragment}
 */
function buildRegTable(title, rows) {
    const frag = document.createDocumentFragment();

    const h6 = document.createElement('h6');
    h6.className = 'reg-section-label';
    h6.textContent = title;
    frag.appendChild(h6);

    const table = document.createElement('table');
    table.className = 'call-modal-reg-table';

    rows.forEach(([label, value, kind]) => {
        const tr = document.createElement('tr');

        const tdLabel = document.createElement('td');
        tdLabel.textContent = label;
        tr.appendChild(tdLabel);

        const tdValue = document.createElement('td');
        const safeValue = (value === null || value === undefined || value === '') ? '—' : String(value);

        if (kind === 'email' && safeValue !== '—') {
            const a = document.createElement('a');
            a.href = 'mailto:' + safeValue;
            a.textContent = safeValue;
            tdValue.appendChild(a);
        } else if (kind === 'phone' && safeValue !== '—') {
            const a = document.createElement('a');
            a.href = 'tel:' + safeValue.replace(/[^0-9+]/g, '');
            a.textContent = safeValue;
            tdValue.appendChild(a);
        } else {
            tdValue.textContent = safeValue;
        }

        // Copy-to-clipboard button
        const copyBtn = document.createElement('button');
        copyBtn.className = 'call-modal-copy-btn';
        copyBtn.type = 'button';
        copyBtn.title = 'Copy to clipboard';
        copyBtn.innerHTML = '<i class="bi bi-copy"></i>';
        copyBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            navigator.clipboard.writeText(safeValue).then(function () {
                copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                copyBtn.classList.add('copied');
                setTimeout(function () {
                    copyBtn.innerHTML = '<i class="bi bi-copy"></i>';
                    copyBtn.classList.remove('copied');
                }, 1500);
            });
        });
        tdValue.appendChild(copyBtn);

        tr.appendChild(tdValue);

        table.appendChild(tr);
    });

    frag.appendChild(table);
    return frag;
}

document.getElementById('btnCallDone').addEventListener('click', function () {
    let ticketId = this.dataset.ticketId;
    if (!ticketId) return;

    let token = document.querySelector('input[name="_token"]').value;

    fetch('/tickets/action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            ticket_id: ticketId,
            action: 'done',
        }),
    })
    .then(() => {
        document.getElementById('nowServingDisplay').textContent = '—';
        callModalInstance.hide();
    })
    .catch(() => {
        alert('Failed to mark ticket as done.');
    });
});

//For Clock
function updateClock() {
    const now = new Date();

    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    let ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // 0 becomes 12

    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;

    document.getElementById('clock').innerText =
        hours + ':' + minutes + ':' + seconds + ' ' + ampm;
}

setInterval(updateClock, 1000);
updateClock();

document.querySelectorAll('.global-flash').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; }, 3500);
    setTimeout(() => el.remove(), 3800);
});

// Tab Switching
function switchTab(tab) {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => link.classList.remove('active'));

    // Match sidebar link by data-tab attribute (set in blade) or by index
    const targetLink = Array.from(sidebarLinks).find(l => l.dataset.tab === tab);
    if (targetLink) {
        targetLink.classList.add('active');
    } else if (tab === 'queue') {
        sidebarLinks[0]?.classList.add('active');
    } else {
        sidebarLinks[1]?.classList.add('active');
    }

    const contentQueue = document.getElementById('tabContentQueue');
    const contentRegistrations = document.getElementById('tabContentRegistrations');

    if (tab === 'queue') {
        contentQueue.style.display = 'block';
        contentRegistrations.style.display = 'none';
    } else {
        contentQueue.style.display = 'none';
        contentRegistrations.style.display = 'block';
    }
}

// View Registration Modal
function viewRegistration(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewRegModal'));
    const body = document.getElementById('viewRegBody');

    document.getElementById('btnViewPrint').dataset.registrationId = id;

    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('/registration/' + id)
        .then(res => res.json())
        .then(data => {
            body.innerHTML = '';
            const referralValue = data.referral_source
                ? (data.referral_type || '—') + ' — ' + data.referral_source
                : (data.referral_type || '—');

            body.appendChild(buildRegTable('Enrollment Information', [
                ['Enrollee Type', data.enrollee_type],
                ['Referral Type', referralValue],
                ['Date Enrolled', formatDate(data.enrollment_date)],
                ['SRN', data.srn],
                ['Application No.', data.application_no],
            ]));

            body.appendChild(buildRegTable('Personal Information', [
                ['First Name', data.first_name],
                ['Middle Name', data.middle_name],
                ['Last Name', data.last_name],
                ['Address', data.address],
                ['Gender', data.gender],
                ['Date of Birth', formatDate(data.birthdate)],
                ['Civil Status', data.civil_status],
                ['Place of Birth', data.place_of_birth],
                ['Email', data.email, 'email'],
                ['Contact No.', data.contact_no, 'phone'],
                ['Rank', data.rank],
                ['Course', data.course],
            ]));

            body.appendChild(buildRegTable('Emergency Contact', [
                ['Contact Person', data.contact_person],
                ['Relationship', data.relationship],
                ['Contact Number', data.contact_mobile, 'phone'],
            ]));
        })
        .catch(() => {
            body.innerHTML = '<div class="alert alert-danger">Failed to load registration details.</div>';
        });
}

document.getElementById('btnViewPrint').addEventListener('click', function () {
    const regId = this.dataset.registrationId;
    if (!regId) return;
    const url = '/registration/' + encodeURIComponent(regId) + '/print';
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');
});

// ======================= INLINE EDIT IN CALL MODAL =======================

function enterEditMode() {
    if (!lastCallData || !lastCallData.registration) return;
    const reg = lastCallData.registration;

    const container = document.getElementById('callRegistrationInfo');
    container.innerHTML = '';

    const frag = document.createDocumentFragment();

    frag.appendChild(buildEditableRegTable('Enrollment Information', [
        ['enrollee_type', 'Enrollee Type', reg.enrollee_type, 'select', ['New Enrollee', 'Old Enrollee']],
        ['referral_type', 'Referral Type', reg.referral_type, 'select', ['Onsite/Walk-in', 'Online Enrollment', 'Marketing', 'Company']],
        ['referral_source', 'Referral Source', reg.referral_source, 'text'],
        ['enrollment_date', 'Date Enrolled', reg.enrollment_date, 'date'],
        ['srn', 'SRN', reg.srn, 'text'],
        ['application_no', 'Application No.', reg.application_no, 'text'],
    ]));

    frag.appendChild(buildEditableRegTable('Personal Information', [
        ['first_name', 'First Name', reg.first_name, 'text'],
        ['middle_name', 'Middle Name', reg.middle_name, 'text'],
        ['last_name', 'Last Name', reg.last_name, 'text'],
        ['address', 'Address', reg.address, 'text'],
        ['gender', 'Gender', reg.gender, 'select', ['Male', 'Female']],
        ['birthdate', 'Date of Birth', reg.birthdate, 'date'],
        ['civil_status', 'Civil Status', reg.civil_status, 'select', ['Single', 'Married', 'Widowed', 'Separated']],
        ['place_of_birth', 'Place of Birth', reg.place_of_birth, 'text'],
        ['email', 'Email', reg.email, 'email'],
        ['contact_no', 'Contact No.', reg.contact_no, 'text'],
        ['rank', 'Rank', reg.rank, 'text'],
        ['course', 'Course', reg.course, 'text'],
    ]));

    frag.appendChild(buildEditableRegTable('Emergency Contact', [
        ['contact_person', 'Contact Person', reg.contact_person, 'text'],
        ['relationship', 'Relationship', reg.relationship, 'text'],
        ['contact_mobile', 'Contact Number', reg.contact_mobile, 'text'],
    ]));

    container.appendChild(frag);

    document.getElementById('readModeButtons').style.display = 'none';
    document.getElementById('editModeButtons').style.display = '';
}

function buildEditableRegTable(title, rows) {
    const frag = document.createDocumentFragment();

    const h6 = document.createElement('h6');
    h6.className = 'reg-section-label';
    h6.textContent = title;
    frag.appendChild(h6);

    const table = document.createElement('table');
    table.className = 'call-modal-reg-table call-modal-edit-table';

    rows.forEach(([name, label, value, type, options]) => {
        const tr = document.createElement('tr');

        const tdLabel = document.createElement('td');
        tdLabel.textContent = label;
        tr.appendChild(tdLabel);

        const tdValue = document.createElement('td');

        let input;
        if (type === 'select' && options) {
            input = document.createElement('select');
            input.name = name;
            input.className = 'call-modal-edit-input';
            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '—';
            input.appendChild(emptyOpt);
            options.forEach(optText => {
                const opt = document.createElement('option');
                opt.value = optText;
                opt.textContent = optText;
                input.appendChild(opt);
            });
        } else if (type === 'date') {
            input = document.createElement('input');
            input.type = 'date';
            input.name = name;
            input.className = 'call-modal-edit-input';
        } else {
            input = document.createElement('input');
            input.type = type;
            input.name = name;
            input.className = 'call-modal-edit-input';
            if (type === 'email') input.placeholder = 'name@example.com';
            if (name === 'contact_no' || name === 'contact_mobile') input.placeholder = '0912-345-6789';
        }

        if (value !== null && value !== undefined) {
            if (type === 'date' && value) {
                input.value = String(value).split('T')[0];
            } else {
                input.value = String(value);
            }
        }

        tdValue.appendChild(input);
        tr.appendChild(tdValue);
        table.appendChild(tr);
    });

    frag.appendChild(table);
    return frag;
}

function exitEditMode() {
    if (!lastCallData || !lastCallData.registration) return;
    const reg = lastCallData.registration;

    const container = document.getElementById('callRegistrationInfo');
    container.innerHTML = '';

    let referralValue = reg.referral_type || '—';
    if (reg.referral_source) {
        referralValue += ' — ' + reg.referral_source;
    }

    container.appendChild(buildRegTable('Enrollment Information', [
        ['Enrollee Type', reg.enrollee_type],
        ['Referral Type', referralValue],
        ['Date Enrolled', formatDate(reg.enrollment_date)],
        ['SRN', reg.srn],
        ['Application No.', reg.application_no],
    ]));

    container.appendChild(buildRegTable('Personal Information', [
        ['First Name', reg.first_name],
        ['Middle Name', reg.middle_name],
        ['Last Name', reg.last_name],
        ['Address', reg.address],
        ['Gender', reg.gender],
        ['Date of Birth', formatDate(reg.birthdate)],
        ['Civil Status', reg.civil_status],
        ['Place of Birth', reg.place_of_birth],
        ['Email', reg.email, 'email'],
        ['Contact No.', reg.contact_no, 'phone'],
        ['Rank', reg.rank],
        ['Course', reg.course],
    ]));

    container.appendChild(buildRegTable('Emergency Contact', [
        ['Contact Person', reg.contact_person],
        ['Relationship', reg.relationship],
        ['Contact Number', reg.contact_mobile, 'phone'],
    ]));

    document.getElementById('readModeButtons').style.display = '';
    document.getElementById('editModeButtons').style.display = 'none';
}

// Edit button: enter inline edit mode
document.getElementById('btnCallEdit').addEventListener('click', enterEditMode);

// Cancel edit: revert to readonly
document.getElementById('btnCallCancelEdit').addEventListener('click', exitEditMode);

// Save: collect values, PUT to server
document.getElementById('btnCallSave').addEventListener('click', function () {
    if (!lastCallData || !lastCallData.registration) return;
    const regId = lastCallData.registration.id;

    const inputs = document.querySelectorAll('#callRegistrationInfo .call-modal-edit-input');
    const data = {};
    inputs.forEach(el => {
        data[el.name] = el.value;
    });

    const token = document.querySelector('input[name="_token"]').value;

    fetch('/registration/' + regId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
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
            alert('Validation error:\n' + msg);
            return;
        }
        if (result.status >= 400) {
            alert(result.body.message || 'Update failed.');
            return;
        }

        lastCallData.registration = result.body.registration;
        exitEditMode();
        showDashboardToast('Registration updated successfully!');
    })
    .catch(err => alert('Error: ' + err.message));
});
