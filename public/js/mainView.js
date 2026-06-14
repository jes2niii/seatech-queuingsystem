 //blink and sound of Ticket
 let previousServing = {}; // store previous tickets
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

                        // Trigger blink only if ticket changed
                        if (oldTicket !== newTicket && newTicket !== 'NONE') {
                            triggerBlink(cell);
                        }

                        // Update cell content
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
            cell.classList.add('blink', 'pulse');

            setTimeout(() => {
                cell.classList.remove('blink', 'pulse');
            }, 3000);

            playSound3Times();
        }

        // Refresh every 2 seconds
        setInterval(refreshServing, 2000);
        refreshServing();


        //For Video
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

        // Load first video on page load
        loadNext();

        // When current video ends, load and play next
        if (player) {
            player.addEventListener('ended', () => {
                loadNext();
                playNext();
            });
        }

        