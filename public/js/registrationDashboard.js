//For Table of Tickets

 function attachRowClick() {
    document.querySelectorAll('.ticket-row').forEach(row => {
        row.onclick = function () {
            // Remove highlight from all rows
            document.querySelectorAll('.ticket-row')
                .forEach(r => r.classList.remove('table-active'));

            // Highlight the clicked row
            this.classList.add('table-active');
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
                    selectedRow.classList.add('table-active');
                } else {
                    // If ticket no longer exists, clear selection
                    selectedTicketId = null;
                    document.getElementById('selectedTicket').value = '';
                }
            }
        });
}, 2000);

function submitAction(action) {
   let sound;

    if (action === 'call') {
        sound = document.getElementById('callSound');
    } else if (action === 'recall') {
        sound = document.getElementById('recallSound');
    }

    if (sound) {
        sound.currentTime = 0;
        sound.play();
    }
    setTimeout(() => {
        if (!selectedTicketId) {
            alert('Please select a ticket first');
            return;
        }

        document.getElementById('ticketAction').value = action;
        document.getElementById('ticketActionForm').action = `/tickets/action`;
        document.getElementById('ticketActionForm').submit();

     }, 700); // enough time for sound to finish
}

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

