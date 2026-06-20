(function() {
    'use strict';

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    if (!paksa_cr_push || !paksa_cr_push.sw_url) return;

    // Don't ask immediately — wait 10 seconds
    setTimeout(function() {
        // Only ask if not already subscribed
        if (Notification.permission === 'granted') {
            registerSubscription();
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    registerSubscription();
                }
            });
        }
    }, 10000);

    function registerSubscription() {
        navigator.serviceWorker.register(paksa_cr_push.sw_url, { scope: '/' })
            .then(function(registration) {
                return registration.pushManager.getSubscription().then(function(sub) {
                    if (sub) return sub;
                    return registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(paksa_cr_push.vapid_public || 'BEmptyKeyPlaceholder')
                    });
                });
            })
            .then(function(subscription) {
                // Send subscription to server
                var xhr = new XMLHttpRequest();
                xhr.open('POST', paksa_cr_push.ajax_url);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(
                    'action=paksa_cr_push_subscribe' +
                    '&nonce=' + paksa_cr_push.nonce +
                    '&subscription=' + encodeURIComponent(JSON.stringify(subscription.toJSON()))
                );
            })
            .catch(function() {
                // Silent fail — push not critical
            });
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
})();
