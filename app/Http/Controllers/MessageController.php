<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint AJAX polling untuk chat customer-admin.
 */
class MessageController extends Controller
{
    /**
     * Customer mengirim pesan tanpa reload halaman.
     */
    public function send(Request $request, Order $order): JsonResponse
    {
        $customer = $this->customer();
        // Kirim pesan customer tetap dibatasi pada order miliknya.
        abort_unless((int) $order->customer_id === (int) $customer->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::firstOrCreate([
            'order_id' => $order->id,
        ], [
            'customer_id' => $customer->id,
            'status' => 'open',
        ]);

        // Conversation disentuh agar daftar chat admin terurut dari aktivitas terbaru.
        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'sender_type' => 'customer',
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $conversation->touch();

        return response()->json([
            'message' => $this->formatMessage($message->load('sender'), 'customer'),
        ], 201);
    }

    /**
     * Customer mengambil pesan terbaru lewat AJAX polling.
     */
    public function fetch(Order $order): JsonResponse
    {
        $customer = $this->customer();
        // Fetch polling memakai authorization yang sama dengan pengiriman pesan.
        abort_unless((int) $order->customer_id === (int) $customer->id, 403);

        $conversation = Conversation::where('order_id', $order->id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $conversation->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'messages' => $conversation->messages()
                ->with('sender')
                ->oldest()
                ->get()
                ->map(fn (Message $message) => $this->formatMessage($message, 'customer')),
        ]);
    }

    /**
     * Admin membalas pesan customer.
     */
    public function adminSend(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! $conversation->admin_id) {
            // Admin pertama yang membalas menjadi admin penanggung jawab conversation.
            $conversation->update([
                'admin_id' => auth()->id(),
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $conversation->touch();

        return response()->json([
            'message' => $this->formatMessage($message->load('sender'), 'admin'),
        ], 201);
    }

    /**
     * Admin mengambil pesan terbaru lewat AJAX polling.
     */
    public function adminFetch(Conversation $conversation): JsonResponse
    {
        $conversation->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'messages' => $conversation->messages()
                ->with('sender')
                ->oldest()
                ->get()
                ->map(fn (Message $message) => $this->formatMessage($message, 'admin')),
        ]);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    private function formatMessage(Message $message, string $viewer): array
    {
        // Format JSON dibuat kecil agar view bisa render ulang tanpa reload penuh.
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender->name ?? ucfirst($message->sender_type),
            'message' => $message->message,
            'is_read' => (bool) $message->is_read,
            'is_mine' => $message->sender_type === $viewer,
            'created_at' => $message->created_at?->format('d M Y H:i'),
        ];
    }
}
