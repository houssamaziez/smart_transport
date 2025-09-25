<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Professional Realtime Chat</title>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.14.1/dist/echo.iife.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f9;
            display: flex;
            justify-content: center;
            padding: 50px;
        }

        .chat-container {
            width: 400px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 15px;
            background: #007bff;
            color: white;
            font-size: 1.2em;
            font-weight: bold;
        }

        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #e5e9f0;
        }

        .chat-messages li {
            list-style: none;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 20px;
            max-width: 80%;
            word-wrap: break-word;
            position: relative;
        }

        .sent {
            background: #d1e7dd;
            color: #0f5132;
            align-self: flex-end;
        }

        .received {
            background: #cfe2ff;
            color: #084298;
            align-self: flex-start;
        }

        .chat-footer {
            display: flex;
            padding: 10px;
            background: #f1f3f6;
        }

        .chat-footer input {
            flex: 1;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid #ccc;
            outline: none;
            margin-right: 10px;
        }

        .chat-footer button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
        }

        .chat-footer button:hover {
            background: #0056b3;
        }

        .timestamp {
            font-size: 0.7em;
            opacity: 0.7;
            margin-top: 3px;
        }

        #status {
            padding: 5px 10px;
            font-size: 0.85em;
            background: #ffc107;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">Realtime Professional Chat</div>
        <div id="status">Connecting to Pusher...</div>
        <ul class="chat-messages" id="messages"></ul>
        <div class="chat-footer">
            <input type="text" id="messageInput" placeholder="Type a message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>



    <script>
        // إعداد Pusher و Echo
        window.Pusher = Pusher;
        const echo = window.Echo = new Echo({
            broadcaster: 'pusher',
            key: "{{ env('PUSHER_APP_KEY') }}",
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            forceTLS: true
        });

        const messagesEl = document.getElementById('messages');
        const statusEl = document.getElementById('status');

        // تحقق من الاتصال
        const pusher = echo.connector.pusher;
        pusher.connection.bind('connected', () => {
            statusEl.textContent = '✅ Connected to Pusher!';
            statusEl.style.background = '#198754';
            console.log("✅ Connected to Pusher");
        });

        pusher.connection.bind('error', (err) => {
            statusEl.textContent = '❌ Connection error: ' + JSON.stringify(err);
            statusEl.style.background = '#dc3545';
            console.error("❌ Pusher error:", err);
        });

        // الاستماع للقناة chat
        echo.channel('chat')
            .listen('.MessageSent', (e) => {
                console.log("📩 Received event:", e);
                addMessage(e.message, 'received');
            });

        // إضافة رسالة للواجهة
        function addMessage(msg, type) {
            const li = document.createElement('li');
            li.textContent = msg;
            li.classList.add(type);

            const time = document.createElement('div');
            time.textContent = new Date().toLocaleTimeString();
            time.classList.add('timestamp');
            li.appendChild(time);

            messagesEl.appendChild(li);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        // إرسال رسالة
        function sendMessage() {
            const message = document.getElementById('messageInput').value.trim();
            if (!message) return;

            console.log("📤 Sending message:", message);

            fetch('/api/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message
                    })
                })
                .then(async (res) => {
                    const data = await res.json();
                    console.log("📥 API response:", data);
                    return data;
                })
                .then(res => {
                    if (res.status) {
                        addMessage(res.message, 'sent');
                        document.getElementById('messageInput').value = '';
                    } else if (res.error) {
                        console.error('⚠️ API error:', res.error);
                    }
                })
                .catch(err => console.error('❌ Fetch error:', err));
        }
    </script>

</body>

</html>
