import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
    }
});

// Chat message listener
export function listenToChat(userId) {
    // Listen for direct messages
    window.Echo.private(`chat.${userId}`)
        .listen('ChatMessageSent', (e) => {
            console.log('New message received:', e);
            // Handle the new message (update UI, play sound, etc.)
            handleNewMessage(e);
        });
}

// Admin chat listener
export function listenToAdminChat() {
    window.Echo.private('admin.chat')
        .listen('ChatMessageSent', (e) => {
            console.log('New admin message:', e);
            handleNewMessage(e);
        });
}

// Pharmacist chat listener
export function listenToPharmacistChat(pharmacistId) {
    window.Echo.private(`pharmacist.chat.${pharmacistId}`)
        .listen('ChatMessageSent', (e) => {
            console.log('New pharmacist message:', e);
            handleNewMessage(e);
        });
}

// Patient chat listener
export function listenToPatientChat(patientId) {
    window.Echo.private(`patient.chat.${patientId}`)
        .listen('ChatMessageSent', (e) => {
            console.log('New patient message:', e);
            handleNewMessage(e);
        });
}

// Function to handle new messages
function handleNewMessage(message) {
    // Update the chat UI
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
        const messageElement = createMessageElement(message);
        chatContainer.appendChild(messageElement);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Play notification sound if not in the current chat
    if (message.sender_id !== currentUserId) {
        playNotificationSound();
    }
}

// Helper function to create message element
function createMessageElement(message) {
    const div = document.createElement('div');
    div.className = `message ${message.sender_id === currentUserId ? 'sent' : 'received'}`;
    
    div.innerHTML = `
        <div class="message-content">
            ${message.message ? `<p>${message.message}</p>` : ''}
            ${message.file_path ? `
                <div class="message-file">
                    ${message.file_type === 'image' ? 
                        `<img src="${message.file_path}" alt="${message.file_name}">` :
                        `<a href="${message.file_path}" target="_blank">${message.file_name}</a>`
                    }
                </div>
            ` : ''}
        </div>
        <div class="message-info">
            <span class="sender-name">${message.sender.name}</span>
            <span class="message-time">${new Date(message.created_at).toLocaleTimeString()}</span>
        </div>
    `;
    
    return div;
}

// Helper function to play notification sound
function playNotificationSound() {
    const audio = new Audio('/notification.mp3');
    audio.play().catch(e => console.log('Error playing sound:', e));
} 