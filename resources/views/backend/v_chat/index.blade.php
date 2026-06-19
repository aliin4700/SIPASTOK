@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 24px; }
    .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 700; padding: 6px 10px; text-transform: capitalize; }
    .status-open { background: #d1e7dd; color: #0f5132; }
    .status-closed { background: #f8d7da; color: #842029; }
</style>

<div class="breadlab-admin-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">Chat Customer</h3>
            <p class="text-muted mb-0">Percakapan customer berdasarkan pesanan.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Pesan Terakhir</th>
                    <th>Unread</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conversation)
                <tr>
                    <td>{{ $conversation->order->order_number ?? '-' }}</td>
                    <td>{{ $conversation->customer->user->name ?? '-' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $conversation->latestMessage->sender->name ?? '-' }}</div>
                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($conversation->latestMessage->message ?? 'Belum ada pesan.', 60) }}</div>
                    </td>
                    <td>{{ $conversation->unread_count }}</td>
                    <td><span class="status-badge status-{{ $conversation->status }}">{{ $conversation->status }}</span></td>
                    <td><a href="{{ route('backend.chat.show', $conversation) }}" class="btn btn-primary btn-sm">Buka</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada chat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $conversations->links() }}
</div>
@endsection
