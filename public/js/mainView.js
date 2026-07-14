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

// Video rotation (dual-player preload)
let index = 0;
const player1 = document.getElementById('tvPlayer');
const player2 = document.getElementById('tvPlayer2');
if (!player1 || !player2) { videos = []; }

let activePlayer = player1;
let standbyPlayer = player2;

function loadNext() {
    if (videos.length === 0 || !player1 || !player2) return;
    standbyPlayer.src = videos[index];
    standbyPlayer.load();
    index = (index + 1) % videos.length;
}

function playNext() {
    if (videos.length === 0 || !player1 || !player2) return;
    activePlayer.volume = 0.25;
    activePlayer.play().catch(() => {
        activePlayer.addEventListener('canplay', function retry() {
            activePlayer.removeEventListener('canplay', retry);
            activePlayer.play().catch(() => {});
        }, { once: true });
        activePlayer.load();
    });
}

function swapPlayers() {
    if (videos.length === 0 || !player1 || !player2) return;

    [activePlayer, standbyPlayer] = [standbyPlayer, activePlayer];

    activePlayer.classList.remove('tv-video-hidden');
    standbyPlayer.classList.add('tv-video-hidden');

    standbyPlayer.pause();
    standbyPlayer.currentTime = 0;
    standbyPlayer.muted = true;

    playNext();
    loadNext();
}

if (player1 && player2) {
    player1.addEventListener('ended', swapPlayers);
    player2.addEventListener('ended', swapPlayers);

    loadNext();
    standbyPlayer.addEventListener('canplay', function startPlaying() {
        standbyPlayer.removeEventListener('canplay', startPlaying);
        swapPlayers();
    }, { once: true });
    setTimeout(function () {
        standbyPlayer.removeEventListener('canplay', startPlaying);
        if (activePlayer === player1 && standbyPlayer === player2) {
            swapPlayers();
        }
    }, 3000);
}
