if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register(window.TOOLTRACK_BASE_URL + '/service-worker.js')
            .catch(error => console.error('Service worker registration failed:', error));
    });
}
