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
        .choice-card { border: 1px solid #ead8bf; border-radius: 8px; cursor: pointer; }
        .choice-card:has(.form-check-input:checked) { border-color: #8B4513; box-shadow: 0 0 0 3px rgba(139, 69, 19, .14); }
        .summary-line { border-bottom: 1px solid #f0e4d3; }
        .cream-note { background: #fff4e4; border: 1px solid #efd8b6; color: #6d3b12; }
        .step-label { color: #8B4513; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="mb-4">
            <a href="{{ route('cart.index') }}" class="text-decoration-none">&larr; Kembali ke keranjang</a>
            <h1 class="h3 mt-3 mb-1">Checkout</h1>
            <p class="text-muted mb-0">Pilih metode transaksi dan pembayaran untuk membuat pesanan.</p>
        </div>

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('checkout.process') }}" method="post" id="checkout-form">
            @csrf
            <div class="row g-4">
                <section class="col-lg-8">
                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 1</div>
                        <h2 class="h5 mb-3">Metode Transaksi</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="pickup" @checked(old('transaction_method', 'pickup') === 'pickup') required>
                                        <span class="form-check-label fw-semibold">Pickup</span>
                                    </div>
                                    <div class="small text-muted mt-2">Ambil pesanan di toko. Ongkir Rp 0.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="local_delivery" @checked(old('transaction_method') === 'local_delivery') required>
                                        <span class="form-check-label fw-semibold">Local Delivery</span>
                                    </div>
                                    <div class="small text-muted mt-2">Pengantaran lokal manual. Peta akan ditambahkan nanti.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="shipping" @checked(old('transaction_method') === 'shipping') required>
                                        <span class="form-check-label fw-semibold">Shipping</span>
                                    </div>
                                    <div class="small text-muted mt-2">Input ongkir manual sementara. RajaOngkir menyusul.</div>
                                </label>
                            </div>
                        </div>
                        @error('transaction_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 2</div>
                        <h2 class="h5 mb-3">Metode Pembayaran</h2>
                        <div class="row g-3">
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="cash" @checked(old('payment_method', 'cash') === 'cash') required>
                                        <span class="form-check-label fw-semibold">Cash</span>
                                    </div>
                                    <div class="small text-muted mt-2">Hanya untuk pickup dan local delivery.</div>
                                </label>
                            </div>
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery,shipping">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer') required>
                                        <span class="form-check-label fw-semibold">Bank Transfer</span>
                                    </div>
                                    <div class="small text-muted mt-2">Status pembayaran pending sampai diverifikasi.</div>
                                </label>
                            </div>
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery,shipping">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="midtrans" @checked(old('payment_method') === 'midtrans') required>
                                        <span class="form-check-label fw-semibold">Midtrans</span>
                                    </div>
                                    <div class="small text-muted mt-2">Placeholder. Snap akan dibuat pada tahap Midtrans.</div>
                                </label>
                            </div>
                        </div>
                        @error('payment_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 3</div>
                        <h2 class="h5 mb-3">Detail Pesanan</h2>
                        <div class="row g-3">
                            <div class="col-12 shipping-field">
                                <label for="address" class="form-label">Alamat Shipping</label>
                                <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', auth()->user()->customer->address ?? '') }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="postal_code" class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', auth()->user()->customer->postal_code ?? '') }}" class="form-control @error('postal_code') is-invalid @enderror">
                                @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 local-field">
                                <label for="delivery_address" class="form-label">Alamat Local Delivery</label>
                                <textarea name="delivery_address" id="delivery_address" rows="3" class="form-control @error('delivery_address') is-invalid @enderror">{{ old('delivery_address', auth()->user()->customer->address ?? '') }}</textarea>
                                @error('delivery_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 local-field">
                                <label for="delivery_note" class="form-label">Catatan Delivery</label>
                                <textarea name="delivery_note" id="delivery_note" rows="2" class="form-control @error('delivery_note') is-invalid @enderror">{{ old('delivery_note') }}</textarea>
                                @error('delivery_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 shipping-field">
                                <label for="courier" class="form-label">Kurir</label>
                                <input type="text" name="courier" id="courier" value="{{ old('courier') }}" class="form-control @error('courier') is-invalid @enderror" placeholder="jne / jnt / pos">
                                @error('courier')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 shipping-field">
                                <label for="service" class="form-label">Service</label>
                                <input type="text" name="service" id="service" value="{{ old('service') }}" class="form-control @error('service') is-invalid @enderror" placeholder="REG / YES">
                                @error('service')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 shipping-field">
                                <label for="shipping_cost" class="form-label">Ongkir Manual</label>
                                <input type="number" name="shipping_cost" id="shipping_cost" value="{{ old('shipping_cost', 0) }}" min="0" class="form-control @error('shipping_cost') is-invalid @enderror">
                                @error('shipping_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="estimated_delivery" class="form-label">Estimasi</label>
                                <input type="text" name="estimated_delivery" id="estimated_delivery" value="{{ old('estimated_delivery') }}" class="form-control" placeholder="1-2 hari">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Catatan Pesanan</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="cream-note rounded-2 p-3 small">
                        Midtrans, RajaOngkir final, dan peta OpenStreetMap belum diproses pada tahap ini. Data yang dipilih akan disimpan sebagai struktur dasar order.
                    </div>
                </section>

                <aside class="col-lg-4">
                    <div class="panel p-4 position-sticky" style="top: 24px;">
                        <h2 class="h5 mb-3">Ringkasan Pesanan</h2>
                        @foreach($cart->items as $item)
                        <div class="d-flex justify-content-between gap-3 summary-line py-2">
                            <div>
                                <div class="fw-semibold">{{ $item->product->name }}</div>
                                <small class="text-muted">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                            </div>
                            <strong>Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</strong>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-between mt-3">
                            <span>Subtotal</span>
                            <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total Berat</span>
                            <strong>{{ number_format($totalWeight, 0, ',', '.') }} gram</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Biaya Pengiriman</span>
                            <strong id="shipping-cost-summary">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-3 mb-4 pt-3 border-top">
                            <span>Total Bayar</span>
                            <strong id="grand-total">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                        </div>
                        <button type="submit" class="btn btn-bread w-100">Buat Pesanan</button>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const subtotal = Number(@json($subtotal));
        const localDeliveryFee = Number(@json($localDeliveryFee));
        const transactionRadios = document.querySelectorAll('.transaction-radio');
        const paymentOptions = document.querySelectorAll('.payment-option');
        const localFields = document.querySelectorAll('.local-field');
        const shippingFields = document.querySelectorAll('.shipping-field');
        const deliveryAddress = document.getElementById('delivery_address');
        const address = document.getElementById('address');
        const postalCode = document.getElementById('postal_code');
        const courier = document.getElementById('courier');
        const service = document.getElementById('service');
        const shippingCost = document.getElementById('shipping_cost');
        const shippingCostSummary = document.getElementById('shipping-cost-summary');
        const grandTotal = document.getElementById('grand-total');

        function rupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(value);
        }

        function selectedTransaction() {
            return document.querySelector('input[name="transaction_method"]:checked')?.value || 'pickup';
        }

        function applyCheckoutState() {
            const method = selectedTransaction();
            const isLocal = method === 'local_delivery';
            const isShipping = method === 'shipping';

            localFields.forEach((field) => field.classList.toggle('d-none', !isLocal));
            shippingFields.forEach((field) => field.classList.toggle('d-none', !isShipping));

            deliveryAddress.required = isLocal;
            address.required = isShipping;
            postalCode.required = isShipping;
            courier.required = isShipping;
            service.required = isShipping;
            shippingCost.required = isShipping;

            paymentOptions.forEach((option) => {
                const allowed = option.dataset.methods.split(',').includes(method);
                option.classList.toggle('d-none', !allowed);
                const input = option.querySelector('input');
                input.disabled = !allowed;
                if (!allowed && input.checked) {
                    input.checked = false;
                }
            });

            if (!document.querySelector('input[name="payment_method"]:checked:not(:disabled)')) {
                document.querySelector('input[name="payment_method"]:not(:disabled)')?.click();
            }

            const cost = isLocal ? localDeliveryFee : (isShipping ? Number(shippingCost.value || 0) : 0);
            shippingCostSummary.textContent = rupiah(cost);
            grandTotal.textContent = rupiah(subtotal + cost);
        }

        transactionRadios.forEach((radio) => radio.addEventListener('change', applyCheckoutState));
        shippingCost.addEventListener('input', applyCheckoutState);
        applyCheckoutState();
    </script>
</body>

</html>
