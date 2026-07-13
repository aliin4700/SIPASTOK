<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Menangani pembayaran manual customer dan verifikasi admin.
 *
 * Status pembayaran disimpan di payments.payment_status dan disinkronkan ke
 * orders.payment_status sebagai ringkasan, tanpa mencampur form status order.
 */
class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran customer.
     */
    public function show(Order $order)
    {
        // Customer hanya boleh melihat pembayaran dari order miliknya sendiri.
        $this->authorizeCustomerOrder($order);

        return view('v_payment.show', [
            'judul' => 'Pembayaran',
            'order' => $order->load('items.product', 'payment', 'shipment', 'customer.user'),
        ]);
    }

    /**
     * Menampilkan form upload bukti transfer.
     */
    public function uploadProof(Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $order->load('payment');

        // Bukti transfer hanya relevan untuk bank transfer yang belum lunas.
        abort_unless($order->payment_method === 'bank_transfer', 404);

        if ($order->payment?->payment_status === 'paid') {
            return redirect()->route('payment.show', $order)->with('error', 'Pembayaran sudah terverifikasi.');
        }

        return view('v_payment.upload-proof', [
            'judul' => 'Upload Bukti Pembayaran',
            'order' => $order,
        ]);
    }

    /**
     * Menyimpan bukti transfer dan mengubah status menjadi waiting_confirmation.
     */
    public function storeProof(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $order->load('payment');

        abort_unless($order->payment_method === 'bank_transfer', 404);

        if ($order->payment?->payment_status === 'paid') {
            return redirect()->route('payment.show', $order)->with('error', 'Pembayaran sudah terverifikasi.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        // File disimpan di public disk agar bisa ditampilkan admin saat verifikasi.
        $path = $validated['payment_proof']->store('payment-proofs', 'public');

        DB::transaction(function () use ($order, $path) {
            if ($order->payment?->payment_proof) {
                Storage::disk('public')->delete($order->payment->payment_proof);
            }

            // Setelah upload, pembayaran menunggu konfirmasi admin.
            $order->payment()->updateOrCreate([
                'order_id' => $order->id,
            ], [
                'payment_method' => 'bank_transfer',
                'payment_status' => 'waiting_confirmation',
                'payment_proof' => $path,
                'paid_at' => null,
            ]);

            $order->update([
                'payment_status' => 'waiting_confirmation',
            ]);
        });

        return redirect()->route('payment.show', $order)->with('success', 'Bukti pembayaran berhasil diupload.');
    }

    public function index()
    {
        $payments = Payment::with('order.customer.user')
            ->latest()
            ->paginate(15);

        return view('backend.v_payment.index', [
            'judul' => 'Pembayaran',
            'payments' => $payments,
        ]);
    }

    public function adminShow(Payment $payment)
    {
        return view('backend.v_payment.show', [
            'judul' => 'Detail Pembayaran',
            'payment' => $payment->load('order.customer.user', 'order.items.product', 'order.shipment'),
        ]);
    }

    /**
     * Admin memverifikasi pembayaran bank transfer.
     */
    public function verify(Payment $payment)
    {
        abort_unless($payment->payment_method === 'bank_transfer', 404);

        // Verifikasi manual hanya mengubah status pembayaran dan ringkasan order.
        DB::transaction(function () use ($payment) {
            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->syncOrderPayment($payment, 'paid', true);
        });

        return redirect()->route('backend.payment.show', $payment)->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    /**
     * Admin menolak bukti pembayaran yang tidak valid.
     */
    public function reject(Request $request, Payment $payment)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Rejected tidak otomatis membatalkan order agar admin masih bisa meminta upload ulang.
        DB::transaction(function () use ($payment) {
            $payment->update([
                'payment_status' => 'rejected',
                'paid_at' => null,
            ]);

            $this->syncOrderPayment($payment, 'rejected', false);
        });

        return redirect()->route('backend.payment.show', $payment)->with('success', 'Pembayaran ditolak.');
    }

    /**
     * Admin mengonfirmasi pembayaran cash.
     */
    public function confirmCash(Payment $payment)
    {
        abort_unless($payment->payment_method === 'cash', 404);

        // Cash dianggap lunas setelah admin menerima pembayaran fisik.
        DB::transaction(function () use ($payment) {
            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->syncOrderPayment($payment, 'paid', true);
        });

        return redirect()->route('backend.payment.show', $payment)->with('success', 'Pembayaran cash berhasil dikonfirmasi.');
    }

    private function authorizeCustomerOrder(Order $order): void
    {
        abort_unless((int) $order->customer_id === (int) $this->customer()->id, 403);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    private function syncOrderPayment(Payment $payment, string $status, bool $markOrderPaid): void
    {
        // Sinkronisasi dibuat terpusat agar verify, reject, dan confirm cash konsisten.
        $order = $payment->loadMissing('order')->order;

        $orderUpdates = [
            'payment_status' => $status,
        ];

        if ($markOrderPaid && in_array($order->status, ['pending', 'waiting_payment'], true)) {
            $orderUpdates['status'] = 'paid';
        }

        $order->update($orderUpdates);
    }
}
