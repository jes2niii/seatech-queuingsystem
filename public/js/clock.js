function updateClock() {
    const now = new Date();

    const timeOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };

    const dateOptions = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit'
    };

    document.getElementById('time').textContent =
        now.toLocaleTimeString('en-US', timeOptions);

    document.getElementById('date').textContent =
        now.toLocaleDateString('en-US', dateOptions);
}

setInterval(updateClock, 1000);
updateClock();
