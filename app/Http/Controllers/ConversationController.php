<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;

/**
 * Membuka conversation per order untuk customer dan admin.
 */
class ConversationController extends Controller
{
    /**
     * Menampilkan chat customer untuk satu order.
     */
    public function show(Order $order)
    {
        $customer = $this->customer();
        // Customer hanya bisa membuka chat untuk order miliknya sendiri.
        abort_unless((int) $order->customer_id === (int) $customer->id, 403);

        $conversation = Conversation::firstOrCreate([
            'order_id' => $order->id,
        ], [
            'customer_id' => $customer->id,
            'status' => 'open',
        ]);

        // Saat customer membuka chat, pesan dari admin dianggap sudah dibaca.
        $conversation->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('v_chat.show', [
            'judul' => 'Chat Pesanan',
            'order' => $order->load('customer.user'),
            'conversation' => $conversation->load('order', 'customer.user', 'admin', 'messages.sender'),
        ]);
    }

    /**
     * Menampilkan daftar conversation untuk admin.
     */
    public function index()
    {
        $conversations = Conversation::with('order', 'customer.user', 'admin', 'latestMessage.sender')
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_type', 'customer')
                    ->where('is_read', false),
            ])
            ->latest('updated_at')
            ->paginate(15);

        return view('backend.v_chat.index', [
            'judul' => 'Chat Customer',
            'conversations' => $conversations,
        ]);
    }

    /**
     * Menampilkan detail conversation untuk admin.
     */
    public function adminShow(Conversation $conversation)
    {
        // Saat admin membuka chat, pesan customer di conversation ini dianggap sudah dibaca.
        $conversation->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('backend.v_chat.show', [
            'judul' => 'Detail Chat',
            'conversation' => $conversation->load('order.customer.user', 'customer.user', 'admin', 'messages.sender'),
        ]);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }
}
