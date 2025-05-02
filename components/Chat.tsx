import { useState, useRef, useEffect } from 'react';
import { useChat } from '../hooks/useChat';
import Image from 'next/image';

interface ChatProps {
    userId: number;
    userRole: number;
    receiverId: number;
    receiverName: string;
}

export default function Chat({ userId, userRole, receiverId, receiverName }: ChatProps) {
    const [message, setMessage] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const { messages, sendMessage } = useChat(userId, userRole);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!message.trim() && !file) return;

        try {
            await sendMessage(receiverId, message, file || undefined);
            setMessage('');
            setFile(null);
        } catch (error) {
            console.error('Error sending message:', error);
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setFile(e.target.files[0]);
        }
    };

    return (
        <div className="flex flex-col h-full">
            <div className="p-4 border-b">
                <h2 className="text-xl font-semibold">Chat with {receiverName}</h2>
            </div>

            <div className="flex-1 overflow-y-auto p-4">
                {messages.map((msg) => (
                    <div
                        key={msg.id}
                        className={`mb-4 ${
                            msg.sender_id === userId ? 'ml-auto' : 'mr-auto'
                        }`}
                    >
                        <div
                            className={`rounded-lg p-3 max-w-xs ${
                                msg.sender_id === userId
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-800'
                            }`}
                        >
                            {msg.message && <p>{msg.message}</p>}
                            {msg.file_path && (
                                <div className="mt-2">
                                    {msg.file_type === 'image' ? (
                                        <Image
                                            src={msg.file_path}
                                            alt={msg.file_name || 'File'}
                                            width={200}
                                            height={200}
                                            className="rounded"
                                        />
                                    ) : (
                                        <a
                                            href={msg.file_path}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-500 hover:underline"
                                        >
                                            {msg.file_name}
                                        </a>
                                    )}
                                </div>
                            )}
                            <div className="text-xs mt-1">
                                {new Date(msg.created_at).toLocaleTimeString()}
                            </div>
                        </div>
                    </div>
                ))}
                <div ref={messagesEndRef} />
            </div>

            <form onSubmit={handleSubmit} className="p-4 border-t">
                <div className="flex items-center space-x-2">
                    <input
                        type="text"
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        placeholder="Type a message..."
                        className="flex-1 p-2 border rounded"
                    />
                    <input
                        type="file"
                        onChange={handleFileChange}
                        className="hidden"
                        id="file-upload"
                    />
                    <label
                        htmlFor="file-upload"
                        className="p-2 bg-gray-200 rounded cursor-pointer"
                    >
                        📎
                    </label>
                    <button
                        type="submit"
                        className="p-2 bg-blue-500 text-white rounded"
                    >
                        Send
                    </button>
                </div>
                {file && (
                    <div className="mt-2 text-sm text-gray-600">
                        Selected file: {file.name}
                    </div>
                )}
            </form>
        </div>
    );
} 