// import './bootstrap';

import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

window.Pusher = Pusher;
window.Echo = new Echo({
  broadcaster: "pusher",
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  enabledTransports: ['ws', 'wss'], // Add this line
});

// // Add connection status listeners
// window.Echo.connector.pusher.connection.bind('connected', () => {
//   console.log('Connected to Pusher');
// });

// window.Echo.connector.pusher.connection.bind('disconnected', () => {
//   console.log('Disconnected from Pusher');
// });

// window.Echo.connector.pusher.connection.bind('state_change', (states) => {
//   console.log('Pusher state changed:', states);
// });

// // To this:
// window.Echo.channel("telegram-messages").listen("MessageReceived", (e) => {
//   console.log("Message received:", e);
// });

// Listen for telegram messages
window.Echo.channel('telegram-messages')
    .listen('.MessageReceived', (event) => {
        console.log('Telegram message received:', event);
        // Add your message handling logic here
    });