@extends('cashier.layouts.app')

@section('content')
@if(!$openShift)
    <div class="cashier-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h5 mb-0">POS / Orders</h2>
            <a href="{{ route('cashier.orders.history') }}" class="btn btn-outline-dark btn-sm">Riwayat POS</a>
        </div>
        <p class="text-muted mb-3">Buka shift terlebih dahulu sebelum membuat transaksi POS.</p>
        <a href="{{ route('cashier.shift.index') }}" class="btn btn-bread">Buka Shift</a>
    </div>
@else
    <div class="row g-4">
        <section class="col-xl-8">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('cashier.orders.history') }}" class="btn btn-outline-dark btn-sm">Riwayat POS</a>
            </div>
            <div class="cashier-card p-3 mb-3">
                <form action="{{ route('cashier.orders.index') }}" method="get" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Cari Produk</label>
                        <input type="search" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="Nama produk">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Kategori</label>
                        <select name="category" id="category" class="form-control">
                            <option value="">Semua kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-bread">Filter</button>
                    </div>
                </form>
            </div>

            <div class="row g-3">
                @forelse($products as $product)
                    <div class="col-md-6 col-xxl-4">
                        <article class="cashier-card h-100 overflow-hidden">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-100" style="aspect-ratio: 4 / 3; object-fit: cover; background: #f7ead8;">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <span class="badge bg-light text-dark">{{ $product->category->name ?? 'BreadLab' }}</span>
                                    <span class="text-muted small">Stok {{ $product->stock }}</span>
                                </div>
                                <h2 class="h6 mb-2">{{ $product->name }}</h2>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <strong>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</strong>
                                    <form action="{{ route('cashier.orders.cart.add') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn btn-sm btn-bread" @disabled($product->stock < 1)>Tambah</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="cashier-card p-5 text-center">
                            <h2 class="h5">Produk tidak ditemukan</h2>
                            <p class="text-muted mb-0">Coba ubah pencarian atau kategori.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if(method_exists($products, 'links'))
                <div class="mt-3">{{ $products->links() }}</div>
            @endif
        </section>

        <aside class="col-xl-4">
            <div class="cashier-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-0">POS Cart</h2>
                        <div class="text-muted small">{{ $openShift->shift_code }}</div>
                    </div>
                    @if($cart->isNotEmpty())
                        <form action="{{ route('cashier.orders.cart.clear') }}" method="post">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Clear</button>
                        </form>
                    @endif
                </div>

                @forelse($cart as $item)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex gap-3">
                            @if(!empty($item['image_url']))
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="rounded" style="width: 58px; height: 58px; object-fit: cover;">
                            @endif
                            <div class="flex-fill">
                                <div class="fw-semibold">{{ $item['name'] }}</div>
                                <div class="text-muted small">Rp {{ number_format((float) $item['price'], 0, ',', '.') }}</div>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <form action="{{ route('cashier.orders.cart.update') }}" method="post" class="d-flex align-items-center gap-2">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <input type="number" name="qty" value="{{ $item['qty'] }}" min="1" class="form-control form-control-sm" style="width: 76px;">
                                        <button type="submit" class="btn btn-outline-dark btn-sm">Update</button>
                                    </form>
                                    <form action="{{ route('cashier.orders.cart.remove') }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="text-end fw-semibold mt-2">Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Belum ada item di cart.</div>
                @endforelse

                <div class="d-flex justify-content-between border-top pt-3">
                    <span>Subtotal</span>
                    <strong>Rp {{ number_format((float) $cartSubtotal, 0, ',', '.') }}</strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>Total</span>
                    <strong>Rp {{ number_format((float) $cartTotal, 0, ',', '.') }}</strong>
                </div>
            </div>

            <form action="{{ route('cashier.orders.finalize') }}" method="post" class="cashier-card p-3">
                @csrf
                <h2 class="h5 mb-3">Detail Transaksi</h2>
                <div class="mb-3">
                    <label for="service_type" class="form-label">Service Type</label>
                    <select name="service_type" id="service_type" class="form-control" required>
                        <option value="dine_in" @selected(old('service_type') === 'dine_in')>Dine In</option>
                        <option value="pickup" @selected(old('service_type') === 'pickup')>Pickup</option>
                        <option value="local_delivery" @selected(old('service_type') === 'local_delivery')>Local Delivery</option>
                        <option value="shipping" @selected(old('service_type') === 'shipping')>Shipping</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label for="customer_phone" class="form-label">Customer Phone</label>
                        <input type="text" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label for="table_number" class="form-label">Table Number</label>
                        <input type="text" name="table_number" id="table_number" value="{{ old('table_number') }}" class="form-control" placeholder="Dine in">
                    </div>
                    <div class="col-md-6">
                        <label for="pickup_time" class="form-label">Pickup Time</label>
                        <input type="datetime-local" name="pickup_time" id="pickup_time" value="{{ old('pickup_time') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label for="delivery_address" class="form-label">Delivery Address</label>
                        <textarea name="delivery_address" id="delivery_address" class="form-control" rows="2" placeholder="Local delivery/shipping">{{ old('delivery_address') }}</textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                        <option value="qris_manual" @selected(old('payment_method') === 'qris_manual')>QRIS Manual</option>
                    </select>
                </div>
                <div class="mt-3">
                    <label for="paid_amount" class="form-label">Paid Amount</label>
                    <input type="number" name="paid_amount" id="paid_amount" value="{{ old('paid_amount') }}" min="0" step="100" class="form-control" placeholder="Nominal bayar">
                    <div class="text-muted small mt-1">Kembalian: <span id="change_amount">Rp 0</span></div>
                </div>
                <button type="submit" class="btn btn-bread w-100 mt-3" @disabled($cart->isEmpty())>Finalisasi Order POS</button>
                @if($cart->isEmpty())
                    <p class="text-muted small mb-0 mt-2 text-center">Tambahkan produk ke cart terlebih dahulu.</p>
                @endif
            </form>
        </aside>
    </div>
@endif
@endsection

@push('scripts')
<script>
    const posTotal = {{ (float) $cartTotal }};
    const paidAmountInput = document.getElementById('paid_amount');
    const paymentMethodInput = document.getElementById('payment_method');
    const changeAmountText = document.getElementById('change_amount');

    function formatRupiah(value) {
        return 'Rp ' + Math.max(0, value).toLocaleString('id-ID');
    }

    function updateChangeAmount() {
        if (!paidAmountInput || !paymentMethodInput || !changeAmountText) {
            return;
        }

        const paidAmount = Number(paidAmountInput.value || 0);
        const changeAmount = paymentMethodInput.value === 'cash' ? paidAmount - posTotal : 0;
        changeAmountText.textContent = formatRupiah(changeAmount);
    }

    paidAmountInput?.addEventListener('input', updateChangeAmount);
    paymentMethodInput?.addEventListener('change', updateChangeAmount);
    updateChangeAmount();
</script>
@endpush
