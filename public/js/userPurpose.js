let currentTicket = null;

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
                    alert("Error generating ticket: " + data.error);
                    return;
                }

                closePopup();

                // ----------------- Bluetooth Print -----------------
                // Option 1: Using response page (recommended)
                const printURL = `my.bluetoothprint.scheme://${window.location.origin}/ticket/print-response?ticket=${data.ticket_no}&purpose=${encodeURIComponent(data.purpose)}`;
                window.location.href = printURL;
                // Option 2: Inline direct data (if supported)
                // const ticketText = encodeURIComponent(`Purpose: ${data.purpose}\nTicket No: ${data.ticket_no}\nThank you!`);
                // const printURL = `my.bluetoothprint.scheme://data=${ticketText}&copies=1`;

                // Open Bluetooth Print app
                window.location.href = printURL;

                // Optional: refresh tickets table if needed
                refreshTickets();
            })
            .catch(err => console.error(err));
        }

        // Refresh ticket table (if you have a table to show current tickets)
        function refreshTickets() {
            fetch('/registration/tickets/refresh')
                .then(res => res.text())
                .then(html => {
                    const table = document.getElementById('ticketTable');
                    if (table) table.innerHTML = html;
                });
        }