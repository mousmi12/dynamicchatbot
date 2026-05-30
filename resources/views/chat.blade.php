<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company->name ?? 'Chatbot' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* BODY - Full viewport, no scroll */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }

        /* MAIN CONTAINER - Flexbox layout: Header + Messages + Input */
        .chat-container {
            width: 100%;
            height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
            /* Stack vertically */
            overflow: hidden;
        }

        /* HEADER - Fixed at top, compact size */
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            /* Reduced from 15px 20px */
            text-align: center;
            font-size: 16px;
            /* Reduced from 18px */
            font-weight: bold;
            flex-shrink: 0;
            /* Never shrinks */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* MESSAGES AREA - Grows to fill space, scrollable */
        .chat-messages {
            flex: 1;
            /* Takes all available space between header and input */
            padding: 15px;
            /* Reduced from 20px */
            overflow-y: auto;
            /* Only this section scrolls */
            background: #f5f5f5;
        }

        /* Individual message container */
        .message {
            margin-bottom: 12px;
            /* Reduced from 15px */
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* User messages align right */
        .message.user {
            text-align: right;
        }

        /* Bot messages align left */
        .message.bot {
            text-align: left;
        }

        /* Message bubble styling */
        .message-bubble {
            display: inline-block;
            padding: 10px 15px;
            /* Reduced from 12px 18px */
            border-radius: 16px;
            /* Slightly smaller */
            max-width: 80%;
            word-wrap: break-word;
            line-height: 1.4;
            /* Reduced from 1.5 */
            font-size: 14px;
            /* Added explicit size */
        }

        /* User message style - blue bubble */
        .user .message-bubble {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }

        /* Bot message style - white bubble */
        .bot .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message-bubble strong {
            font-weight: 600;
        }

        /* BUTTONS - Below bot messages */
        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            /* Reduced from 8px */
            margin-top: 8px;
            /* Reduced from 10px */
            max-width: 100%;
        }

        .message.bot .buttons-container {
            margin-left: 0;
        }

        /* Individual button styling */
        .chat-button {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 10px 16px;
            /* Reduced from 12px 20px */
            border-radius: 20px;
            /* Reduced from 25px */
            cursor: pointer;
            font-size: 13px;
            /* Reduced from 14px */
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: left;
            width: 100%;
        }

        .chat-button:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* TYPING INDICATOR - Shows while bot is "thinking" */
        .typing-indicator {
            display: none;
            text-align: left;
            padding: 0 15px 12px 15px;
            /* Reduced padding */
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-bubble {
            display: inline-block;
            background: white;
            padding: 10px 15px;
            /* Reduced */
            border-radius: 16px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Animated dots */
        .typing-dots span {
            display: inline-block;
            width: 7px;
            /* Reduced from 8px */
            height: 7px;
            border-radius: 50%;
            background: #667eea;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {

            0%,
            60%,
            100% {
                transform: translateY(0);
            }

            30% {
                transform: translateY(-8px);
                /* Reduced from -10px */
            }
        }

        /* INPUT AREA - Fixed at bottom */
        .chat-input-container {
            padding: 12px 15px;
            /* Reduced from 15px 20px */
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 8px;
            /* Reduced from 10px */
            flex-shrink: 0;
            /* Never shrinks */
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }

        /* Text input field */
        .chat-input {
            flex: 1;
            padding: 10px 16px;
            /* Reduced from 12px 20px */
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            /* Reduced from 25px */
            font-size: 13px;
            /* Reduced from 14px */
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: #667eea;
        }

        /* Send button */
        .send-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            /* Reduced from 12px 25px */
            border-radius: 20px;
            /* Reduced from 25px */
            cursor: pointer;
            font-size: 13px;
            /* Reduced from 14px */
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .send-button:hover {
            background: #764ba2;
            transform: scale(1.05);
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: scale(1);
        }

        /* SCROLLBAR STYLING - Custom thin scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            /* Reduced from 6px */
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 600px) {
            .chat-header {
                font-size: 15px;
                padding: 10px 12px;
            }

            .chat-messages {
                padding: 12px;
            }

            .chat-input-container {
                padding: 10px 12px;
            }

            .message-bubble {
                font-size: 13px;
                padding: 9px 13px;
            }

            .chat-button {
                font-size: 12px;
                padding: 9px 14px;
            }
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <!-- HEADER - Company name, fixed at top -->
        <div class="chat-header">
            🤖 {{ $company->name ?? 'Chatbot' }}
        </div>

        <!-- MESSAGES - Scrollable chat history -->
        <div class="chat-messages" id="chatMessages">
            <!-- Messages appear here dynamically via JavaScript -->
        </div>

        <!-- TYPING INDICATOR - Shows "..." when bot is responding -->
        <div class="typing-indicator" id="typingIndicator">
            <div class="typing-bubble">
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

        <!-- INPUT AREA - Text box + Send button, fixed at bottom -->
        <div class="chat-input-container">
            <input
                type="text"
                class="chat-input"
                id="chatInput"
                placeholder="Type a message..."
                autocomplete="off">
            <button class="send-button" id="sendButton">Send</button>
        </div>
    </div>

    <script>
        // Get DOM elements
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendButton = document.getElementById('sendButton');
        const typingIndicator = document.getElementById('typingIndicator');

        // Create unique session ID for this chat
        let sessionId = 'web_' + Date.now();

        // AUTO-START: Send "hi" when page loads
        window.addEventListener('load', () => {
            sendMessage('hi', true);
        });

        // SEND on button click
        sendButton.addEventListener('click', () => {
            const message = chatInput.value.trim();
            if (message) {
                // ✅ Save current type before clearing
                const currentType = chatInput.type;
                sendMessage(message);
                chatInput.value = '';
                // ✅ Restore type after clearing (don't reset to text yet)
                chatInput.type = currentType;
            }
        });

        // SEND on Enter key press
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const message = chatInput.value.trim();
                if (message) {
                    // ✅ Save current type before clearing
                    const currentType = chatInput.type;
                    sendMessage(message);
                    chatInput.value = '';
                    // ✅ Restore type after clearing
                    chatInput.type = currentType;
                }
            }
        });

        // MAIN FUNCTION: Send message to backend
        async function sendMessage(message, isInitial = false) {
            // Show user message (except initial "hi")
            if (!isInitial) {
                const displayMessage = chatInput.type === 'password' ? '••••••••' : message;
                addMessage(displayMessage, 'user');
            }

            // Disable input while waiting for response
            chatInput.disabled = true;
            sendButton.disabled = true;
            typingIndicator.classList.add('active');

            try {
                // POST request to backend
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: sessionId
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                console.log('API Response:', data);

                typingIndicator.classList.remove('active');

                // Show bot response with buttons
                if (data.reply) {
                    addMessage(data.reply, 'bot', data.buttons || []);

                    const replyLower = data.reply.toLowerCase();
                    if (replyLower.includes('password') || replyLower.includes('pass')) {
                        chatInput.type = 'password';
                        chatInput.placeholder = 'Enter your password...';
                    } else {
                        chatInput.type = 'text';
                        chatInput.placeholder = 'Type a message...';
                    }
                }

            } catch (error) {
                console.error('Error:', error);
                typingIndicator.classList.remove('active');
                addMessage('❌ Something went wrong. Please try again.', 'bot');
            }

            // Re-enable input
            chatInput.disabled = false;
            sendButton.disabled = false;
            chatInput.focus();
        }

        // ADD MESSAGE to chat UI
        function addMessage(text, type, buttons = []) {
            console.log('addMessage called:', {
                text,
                type,
                buttons
            });

            // Create message container
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;

            // Create message bubble
            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = 'message-bubble';

            // Convert markdown to HTML: **bold** and *italic*
            text = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            bubbleDiv.innerHTML = text;
            messageDiv.appendChild(bubbleDiv);

            // Add buttons if provided by backend
            if (buttons && Array.isArray(buttons) && buttons.length > 0) {
                console.log('Creating buttons container with', buttons.length, 'buttons');

                const buttonsContainer = document.createElement('div');
                buttonsContainer.className = 'buttons-container';

                buttons.forEach((btn, index) => {
                    console.log('Button', index, ':', btn);

                    const button = document.createElement('button');
                    button.className = 'chat-button';
                    button.textContent = btn.label || 'Button';
                    button.onclick = () => {
                        console.log('Button clicked, sending:', btn.value);
                        sendMessage(btn.value || btn.label);
                    };
                    buttonsContainer.appendChild(button);
                });

                messageDiv.appendChild(buttonsContainer);
            } else {
                console.log('No buttons to display');
            }

            // Add to chat and auto-scroll to bottom
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>

</html>