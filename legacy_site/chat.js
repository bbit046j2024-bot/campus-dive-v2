class ChatClient {
    constructor(userId, recipientId) {
        this.userId = userId;
        this.recipientId = recipientId;
        this.conn = null;
        this.reconnectInterval = 3000;

        this.init();
    }

    init() {
        // Connect to WebSocket Server with user credentials
        this.conn = new WebSocket('ws://localhost:8080?user_id=' + this.userId);

        this.conn.onopen = (e) => {
            console.log("Connection established!");
            this.updateStatusIndicator('online');
        };

        this.conn.onmessage = (e) => {
            const data = JSON.parse(e.data);
            this.handleMessage(data);
        };

        this.conn.onclose = (e) => {
            console.log("Connection closed, reconnecting...");
            this.updateStatusIndicator('offline');
            setTimeout(() => this.init(), this.reconnectInterval);
        };

        this.setupInputListeners();
    }

    sendMessage(message, attachment = null) {
        if (!message && !attachment) return;

        const payload = {
            type: 'chat',
            recipient_id: this.recipientId,
            message: message,
            attachment: attachment,
            temp_id: Date.now()
        };

        this.conn.send(JSON.stringify(payload));

        // Optimistic UI Update
        this.renderMessage({
            message: message,
            attachment: attachment,
            created_at: new Date().toISOString(),
            is_sender: true
        });
    }

    sendTypingInicator(isTyping) {
        this.conn.send(JSON.stringify({
            type: 'typing',
            recipient_id: this.recipientId,
            is_typing: isTyping
        }));
    }

    handleMessage(data) {
        switch (data.type) {
            case 'chat':
                if (data.sender_id == this.recipientId || data.sender_id == this.userId) { // Handle own echo if needed or filtered
                    this.renderMessage(data);
                    this.scrollToBottom();
                } else {
                    // Notification for other user
                    this.showNotification(data);
                }
                break;
            case 'typing':
                if (data.sender_id == this.recipientId) {
                    this.toggleTypingIndicator(data.is_typing);
                }
                break;
            case 'status':
                if (data.user_id == this.recipientId) {
                    this.updateUserStatus(data.status);
                }
                break;
        }
    }

    renderMessage(msg) {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        const div = document.createElement('div');
        div.className = `chat-message ${msg.is_sender ? 'sent' : 'received'}`;

        let content = `<div class="message-content">${msg.message}</div>`;

        if (msg.attachment) {
            content += `
                <div class="file-attachment">
                    <i class="fas fa-paperclip"></i>
                    <a href="${msg.attachment}" target="_blank">View Entitlement</a>
                </div>
            `;
        }

        content += `<div class="chat-meta">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>`;

        div.innerHTML = content;
        container.appendChild(div);
        this.scrollToBottom();
    }

    scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    }

    setupInputListeners() {
        const input = document.getElementById('chatInput');
        const form = document.getElementById('chatForm');
        let typingTimeout;

        if (input) {
            input.addEventListener('input', () => {
                this.sendTypingInicator(true);
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => this.sendTypingInicator(false), 1000);
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const msg = input.value.trim();
                if (msg) {
                    this.sendMessage(msg);
                    input.value = '';
                    this.sendTypingInicator(false);
                }
            });
        }
    }

    toggleTypingIndicator(show) {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.style.display = show ? 'flex' : 'none';
        if (show) this.scrollToBottom();
    }

    updateStatusIndicator(status) {
        const indicator = document.getElementById('connectionStatus');
        if (indicator) {
            indicator.className = `status-indicator ${status}`;
            indicator.title = status === 'online' ? 'Connected' : 'Reconnecting...';
        }
    }

    updateUserStatus(status) {
        const indicator = document.getElementById('userStatus');
        if (indicator) {
            indicator.className = `status-indicator ${status}`;
            indicator.setAttribute('title', status);
        }
    }

    showNotification(data) {
        // Simple toast or badge update
        console.log("New message from user " + data.sender_id);
    }
}

// Initialize if on chat page
document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        const userId = chatContainer.dataset.userId;
        const recipientId = chatContainer.dataset.recipientId;
        window.chatClient = new ChatClient(userId, recipientId);
    }
});
