@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 24px; }
    .chat-box { height: 430px; overflow-y: auto; }
    .message-row { display: flex; margin-bottom: 12px; }
    .message-row.mine { justify-content: flex-end; }
    .message-bubble { border-radius: 8px; max-width: 78%; padding: 10px 12px; }
    .message-row.mine .message-bubble { background: #2962ff; color: #fff; }
    .message-row.other .message-bubble { background: #fff4e4; border: 1px solid #efd8b6; color: #24160f; }
    .message-meta { font-size: 11px; opacity: .75; }
</style>

<div class="breadlab-admin-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('backend.chat.index') }}" class="text-decoration-none">&larr; Kembali ke Chat</a>
            <h3 class="mb-1 mt-2">{{ $conversation->order->order_number ?? 'Chat' }}</h3>
            <p class="text-muted mb-0">{{ $conversation->customer->user->name ?? '-' }} · {{ $conversation->customer->user->email ?? '-' }}</p>
        </div>
        <a href="{{ route('backend.order.show', $conversation->order) }}" class="btn btn-outline-primary">Detail Order</a>
    </div>

    <div id="chat-box" class="chat-box mb-3"></div>
    <form id="chat-form" class="d-flex gap-2">
        @csrf
        <input type="text" name="message" id="message-input" class="form-control" maxlength="2000" placeholder="Tulis balasan..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
    <div class="text-danger small mt-2 d-none" id="chat-error"></div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatError = document.getElementById('chat-error');
    const fetchUrl = @json(route('backend.chat.fetch', $conversation));
    const sendUrl = @json(route('backend.chat.send', $conversation));
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

        chatError.textContent = 'Balasan belum bisa dikirim. Coba lagi.';
        chatError.classList.remove('d-none');
    });

    fetchMessages();
    setInterval(fetchMessages, 5000);
</script>
@endsection
