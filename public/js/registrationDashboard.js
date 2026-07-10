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
            ['Date Enrolled', reg.enrollment_date],
            ['SRN', reg.srn],
            ['Application No.', reg.application_no],
        ]));

        regInfo.appendChild(buildRegTable('Personal Information', [
            ['First Name', reg.first_name],
            ['Middle Name', reg.middle_name],
            ['Last Name', reg.last_name],
            ['Address', reg.address],
            ['Gender', reg.gender],
            ['Date of Birth', reg.birthdate],
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
    const printBtn = document.getElementById('btnCallPrint');
    if (reg && reg.id) {
        printBtn.style.display = '';
        printBtn.dataset.registrationId = reg.id;
    } else {
        printBtn.style.display = 'none';
        printBtn.dataset.registrationId = '';
    }

    if (!callModalInstance) {
        callModalInstance = new bootstrap.Modal(document.getElementById('callModal'));
    }

    document.getElementById('btnCallDone').dataset.ticketId = ticket.id;
    callModalInstance.show();
}

document.getElementById('btnCallPrint').addEventListener('click', function () {
    const regId = this.dataset.registrationId;
    if (!regId) return;
    const url = '/registration/' + encodeURIComponent(regId) + '/print-pdf';
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');

    // Close the call modal and show a confirmation toast
    const callModal = bootstrap.Modal.getInstance(document.getElementById('callModal'));
    if (callModal) {
        callModal.hide();
    }
    showDashboardToast('Print ready — use the browser print button on the opened PDF.');
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
                ['Date Enrolled', data.enrollment_date],
                ['SRN', data.srn],
                ['Application No.', data.application_no],
            ]));

            body.appendChild(buildRegTable('Personal Information', [
                ['First Name', data.first_name],
                ['Middle Name', data.middle_name],
                ['Last Name', data.last_name],
                ['Address', data.address],
                ['Gender', data.gender],
                ['Date of Birth', data.birthdate],
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

