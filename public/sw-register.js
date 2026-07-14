if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .then((reg) => {
                console.log('Service Worker registered:', reg.scope);
            })
            .catch((err) => {
                console.log('Service Worker registration failed:', err);
            });
    });
}
