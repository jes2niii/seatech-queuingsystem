 //blink and sound of Ticket
 let previousServing = {}; // store previous tickets

        function refreshServing() {
            fetch('/tv/serving-status')
                .then(res => res.json())
                .then(data => {
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
                .catch(err => console.error(err));
        }

        function triggerBlink(cell) {
            cell.classList.add('blink');

            // Remove blink after 3 seconds
            setTimeout(() => {
                cell.classList.remove('blink');
            }, 8000);

            playSound3Times();
        }

        // Refresh every 2 seconds
        setInterval(refreshServing, 2000);
        refreshServing();


        //For Video
        let index = 0;
        const player = document.getElementById('tvPlayer');

        function playNext() {
            if (videos.length === 0) return; // no videos
            player.src = videos[index];
            player.load();
            player.play().catch(err => console.log("Autoplay blocked:", err));

            index = (index + 1) % videos.length; // loop to first video
        }

        // Play first video on page load
        playNext();

        // When current video ends, play next
        player.addEventListener('ended', playNext);

        