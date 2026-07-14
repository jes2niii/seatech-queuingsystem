// blink and sound of Ticket
let previousServing = {};
let consecutiveErrors = 0;

function refreshServing() {
    fetch('/tv/serving-status')
        .then(res => res.json())
        .then(data => {
            consecutiveErrors = 0;
            for (const id in data) {
                let cell = document.getElementById('serving-' + id);
                if (!cell) continue;

                const newTicket = data[id];
                const oldTicket = previousServing[id] || '';

                if (oldTicket !== newTicket && newTicket !== 'NONE' && oldTicket !== '') {
                    triggerBlink(cell);
                }

                cell.innerHTML = newTicket;
                previousServing[id] = newTicket;
            }
        })
        .catch(err => {
            consecutiveErrors++;
            if (consecutiveErrors >= 5) {
                location.reload();
            }
        });
}

function triggerBlink(cell) {
    const row = cell.closest('.tv-serving-item');
    if (row) {
        row.classList.add('tv-flash');
        setTimeout(() => row.classList.remove('tv-flash'), 3000);
    }
    if (window.playSound3Times) {
        window.playSound3Times();
    }
}

setInterval(refreshServing, 2000);
refreshServing();

// Video rotation
let index = 0;
const player = document.getElementById('tvPlayer');
if (!player) { videos = []; }

function loadNext() {
    if (videos.length === 0) return;
    if (!player) return;
    player.src = videos[index];
    player.load();
    index = (index + 1) % videos.length;
}

function playNext() {
    if (videos.length === 0) return;
    if (!player) return;
    player.play().catch(err => console.log("Playback error:", err));
}

loadNext();

if (player) {
    player.addEventListener('ended', () => {
        loadNext();
        playNext();
    });
}
