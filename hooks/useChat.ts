import { useEffect, useState } from 'react';
import pusher from '../lib/pusher';

interface Message {
    id: number;
    sender_id: number;
    receiver_id: number;
    message: string | null;
    file_path: string | null;
    file_type: string | null;
    file_name: string | null;
    is_read: boolean;
    created_at: string;
    sender: {
        id: number;
        name: string;
        is_role: number;
    };
}

export function useChat(userId: number, userRole: number) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [newMessage, setNewMessage] = useState<Message | null>(null);

    useEffect(() => {
        // Subscribe to the appropriate channel based on user role
        let channel;
        if (userRole === 0) { // Admin
            channel = pusher.subscribe('admin.chat');
        } else if (userRole === 2) { // Pharmacist
            channel = pusher.subscribe(`pharmacist.chat.${userId}`);
        } else if (userRole === 1) { // Patient
            channel = pusher.subscribe(`patient.chat.${userId}`);
        }

        // Also subscribe to private chat channel
        const privateChannel = pusher.subscribe(`chat.${userId}`);

        // Listen for new messages
        channel?.bind('ChatMessageSent', (data: Message) => {
            setNewMessage(data);
            setMessages(prev => [...prev, data]);
        });

        privateChannel.bind('ChatMessageSent', (data: Message) => {
            setNewMessage(data);
            setMessages(prev => [...prev, data]);
        });

        // Cleanup
        return () => {
            channel?.unsubscribe();
            privateChannel.unsubscribe();
        };
    }, [userId, userRole]);

    const sendMessage = async (receiverId: number, message: string, file?: File) => {
        try {
            const formData = new FormData();
            formData.append('receiver_id', receiverId.toString());
            formData.append('message', message);
            if (file) {
                formData.append('file', file);
            }

            const response = await fetch('/api/chat/send', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to send message');
            }

            return await response.json();
        } catch (error) {
            console.error('Error sending message:', error);
            throw error;
        }
    };

    return {
        messages,
        newMessage,
        sendMessage
    };
} 