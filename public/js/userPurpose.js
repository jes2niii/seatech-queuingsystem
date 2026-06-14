let currentTicket = null;

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast-notification toast-' + type;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Show popup and fetch preview ticket
        function showPopup(title) {
            document.getElementById('popupTitle').innerText = title;
            document.getElementById('ticketNumber').innerText = '----';
            document.getElementById('popupModal').style.display = 'flex';

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

        // Close popup
        function closePopup() {
            document.getElementById('popupModal').style.display = 'none';
            currentTicket = null;
        }

        // Confirm ticket, generate, and send to Bluetooth Print app
        function confirmTicket() {
            if (!currentTicket) return;

            fetch('/ticket/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ purpose: currentTicket.purpose })
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    showToast('Error generating ticket: ' + data.error, 'error');
                    return;
                }

                closePopup();

                // ----------------- Bluetooth Print -----------------
                const printURL = `my.bluetoothprint.scheme://${window.location.origin}/ticket/print-response?ticket=${data.ticket_no}&purpose=${encodeURIComponent(data.purpose)}`;

                // Trigger Bluetooth print via hidden iframe (no page redirect)
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = printURL;
                document.body.appendChild(iframe);

                showToast('Ticket printed successfully!', 'success');
            })
            .catch(err => showToast('Print failed: ' + err.message, 'error'));
        }