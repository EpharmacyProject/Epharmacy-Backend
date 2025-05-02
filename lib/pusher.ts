import Pusher from 'pusher-js';

// Initialize Pusher
const pusher = new Pusher(process.env.NEXT_PUBLIC_PUSHER_APP_KEY!, {
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER!,
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
    }
});

export default pusher; 