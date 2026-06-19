<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - BreadLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffaf3; color: #24160f; font-family: Poppins, sans-serif; }
        .panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); }
        .btn-bread { background: #8B4513; border-color: #8B4513; color: #fff; }
        .btn-bread:hover { background: #D2691E; border-color: #D2691E; color: #fff; }
        .chat-box { height: 430px; overflow-y: auto; }
        .message-row { display: flex; margin-bottom: 12px; }
        .message-row.mine { justify-content: flex-end; }
        .message-bubble { border-radius: 8px; max-width: 78%; padding: 10px 12px; }
        .message-row.mine .message-bubble { background: #8B4513; color: #fff; }
        .message-row.other .message-bubble { background: #fff4e4; border: 1px solid #efd8b6; color: #24160f; }
        .message-meta { font-size: 11px; opacity: .75; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="mb-4">
            <a href="{{ route('order.detail', $order) }}" class="text-decoration-none">&larr; Kembali ke pesanan</a>
            <h1 class="h3 mt-3 mb-1">Chat Pesanan {{ $order->order_number }}</h1>
            <p class="text-muted mb-0">Percakapan dengan admin BreadLab untuk pesanan ini.</p>
        </div>

        <div class="panel p-4">
            <div id="chat-box" class="chat-box mb-3"></div>
            <form id="chat-form" class="d-flex gap-2">
                @csrf
                <input type="text" name="message" id="message-input" class="form-control" maxlength="2000" placeholder="Tulis pesan..." autocomplete="off" required>
                <button type="submit" class="btn btn-bread">Kirim</button>
            </form>
            <div class="text-danger small mt-2 d-none" id="chat-error"></div>
        </div>
    </main>

    <script>
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const chatError = document.getElementById('chat-error');
        const fetchUrl = @json(route('chat.fetch', $order));
        const sendUrl = @json(route('chat.send', $order));
        const csrfToken = @json(csrf_token());
        let lastRendered = '';

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        function renderMessages(messages) {
            const html = messages.map((item) => `
                <div class="message-row ${item.is_mine ? 'mine' : 'other'}">
                    <div class="message-bubble">
                        <div class="message-meta mb-1">${escapeHtml(item.sender_name)} · ${escapeHtml(item.created_at)}</div>
                        <div>${escapeHtml(item.message)}</div>
                    </div>
                </div>
            `).join('');

            if (html !== lastRendered) {
                chatBox.innerHTML = html || '<div class="text-muted text-center py-5">Belum ada pesan.</div>';
                chatBox.scrollTop = chatBox.scrollHeight;
                lastRendered = html;
            }
        }

        async function fetchMessages() {
            const response = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
            if (response.ok) {
                const payload = await response.json();
                renderMessages(payload.messages || []);
            }
        }

        chatForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            chatError.classList.add('d-none');

            const message = messageInput.value.trim();
            if (!message) {
                return;
            }

            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message }),
            });

            if (response.ok) {
                messageInput.value = '';
                await fetchMessages();
                return;
            }

            chatError.textContent = 'Pesan belum bisa dikirim. Coba lagi.';
            chatError.classList.remove('d-none');
        });

        fetchMessages();
        setInterval(fetchMessages, 5000);
    </script>
</body>

</html>
