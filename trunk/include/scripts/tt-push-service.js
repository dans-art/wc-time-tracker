/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Javascript functions for the Service Worker.
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 * @todo: Minify the JS
 */

/**
 * Closes the notification on click and opens the existing tab / pwa
 */
self.addEventListener('notificationclick', function(event) {
    console.log('On notification click: ', event.notification.tag);
    event.notification.close();
    const urlToOpen = new URL(event.notification.data.url, self.location.origin).href;

    const promiseChain = clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then((windowClients) => {
      let matchingClient = null;
    
      for (let i = 0; i < windowClients.length; i++) {
        const windowClient = windowClients[i];
        if (windowClient.url === urlToOpen) {
          console.log(windowClient);
          matchingClient = windowClient;
          break;
        }
      }
    
      if (matchingClient) {
          console.log("focus");
        return matchingClient.focus();
      } else {
        console.log("open");
        return clients.openWindow(urlToOpen);
      }
    });
    
    event.waitUntil(promiseChain);

});
