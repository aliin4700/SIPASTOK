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
        .choice-card { border: 1px solid #ead8bf; border-radius: 8px; cursor: pointer; transition: border-color .2s ease, box-shadow .2s ease; }
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
            <p class="text-muted mb-0">Pilih cara transaksi, lengkapi detail pesanan, lalu konfirmasi.</p>
        </div>

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('checkout.store') }}" method="post" id="checkout-form">
            @csrf
            <input type="hidden" name="shipping_province" id="shipping_province" value="{{ old('shipping_province') }}">
            <input type="hidden" name="shipping_city" id="shipping_city" value="{{ old('shipping_city') }}">

            <div class="row g-4">
                <section class="col-lg-8">
                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 1</div>
                        <h2 class="h5 mb-3">Pilih Metode Transaksi</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" id="transaction_pickup" value="pickup" @checked(old('transaction_method') === 'pickup') required>
                                        <span class="form-check-label fw-semibold">Pickup</span>
                                    </div>
                                    <div class="small text-muted mt-2">Ambil pesanan langsung di BreadLab.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" id="transaction_delivery" value="delivery" @checked(old('transaction_method') === 'delivery') required>
                                        <span class="form-check-label fw-semibold">Local Delivery</span>
                                    </div>
                                    <div class="small text-muted mt-2">Pengantaran lokal oleh BreadLab.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" id="transaction_shipping" value="shipping" @checked(old('transaction_method') === 'shipping') required>
                                        <span class="form-check-label fw-semibold">Shipping</span>
                                    </div>
                                    <div class="small text-muted mt-2">Pengiriman jarak jauh via kurir.</div>
                                </label>
                            </div>
                        </div>
                        @error('transaction_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4 mb-4 d-none" id="address-panel">
                        <div class="step-label mb-2">Step 2</div>
                        <h2 class="h5 mb-3">Alamat Tujuan</h2>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Alamat Lengkap</label>
                            <textarea name="shipping_address" id="shipping_address" rows="4" class="form-control @error('shipping_address') is-invalid @enderror">{{ old('shipping_address') }}</textarea>
                            @error('shipping_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="shipping_postal_code" class="form-label">Kode Pos</label>
                            <input type="text" name="shipping_postal_code" id="shipping_postal_code" value="{{ old('shipping_postal_code') }}" class="form-control @error('shipping_postal_code') is-invalid @enderror">
                            @error('shipping_postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="panel p-4 mb-4 d-none" id="shipping-panel">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                            <div>
                                <div class="step-label mb-2">Step 2</div>
                                <h2 class="h5 mb-1">Pengiriman Jarak Jauh</h2>
                                <p class="text-muted small mb-0">Berat dihitung {{ number_format($totalWeight, 0, ',', '.') }} gram.</p>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge cream-note">RajaOngkir</span>
                                <button type="button" class="btn btn-sm btn-outline-dark" id="load-provinces-button">Muat Provinsi</button>
                            </div>
                        </div>

                        <div id="shipping-alert" class="alert alert-warning d-none"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="shipping_province_id" class="form-label">Provinsi</label>
                                <select name="shipping_province_id" id="shipping_province_id" class="form-select @error('shipping_province_id') is-invalid @enderror" data-old="{{ old('shipping_province_id') }}">
                                    <option value="">Pilih shipping untuk memuat provinsi</option>
                                </select>
                                @error('shipping_province_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('shipping_province')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_city_id" class="form-label">Kota/Kabupaten</label>
                                <select name="shipping_city_id" id="shipping_city_id" class="form-select @error('shipping_city_id') is-invalid @enderror" data-old="{{ old('shipping_city_id') }}" disabled>
                                    <option value="">Pilih provinsi dahulu</option>
                                </select>
                                @error('shipping_city_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('shipping_city')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_courier" class="form-label">Kurir</label>
                                <select name="shipping_courier" id="shipping_courier" class="form-select @error('shipping_courier') is-invalid @enderror" data-old="{{ old('shipping_courier') }}">
                                    <option value="">Pilih kurir</option>
                                    @foreach($couriers as $code => $name)
                                    <option value="{{ $code }}" @selected(old('shipping_courier') === $code)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('shipping_courier')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h3 class="h6 mb-0">Layanan Tersedia</h3>
                                <div id="shipping-loading" class="spinner-border spinner-border-sm text-warning d-none" role="status">
                                    <span class="visually-hidden">Memuat ongkir...</span>
                                </div>
                            </div>
                            <div id="shipping-services" class="row g-3">
                                <div class="col-12">
                                    <div class="cream-note rounded-2 p-3 small">Pilih provinsi, kota, dan kurir untuk melihat ongkir.</div>
                                </div>
                            </div>
                            @error('shipping_service')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 3</div>
                        <h2 class="h5 mb-3">Metode Pembayaran</h2>
                        <div id="payment-options" class="row g-3">
                            <div class="col-12">
                                <div class="cream-note rounded-2 p-3 small">Pilih metode transaksi terlebih dahulu.</div>
                            </div>
                        </div>
                        @error('payment_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4">
                        <div class="step-label mb-2">Step 4</div>
                        <h2 class="h5 mb-2">Konfirmasi Order</h2>
                        <p class="text-muted mb-0">Periksa ringkasan pesanan di samping sebelum membuat pesanan.</p>
                    </div>
                </section>

                <aside class="col-lg-4">
                    <div class="panel p-4 position-sticky" style="top: 24px;">
                        <h2 class="h5 mb-3">Ringkasan Pesanan</h2>
                        @foreach($cart->items as $item)
                        <div class="d-flex justify-content-between gap-3 summary-line py-2">
                            <div>
                                <div class="fw-semibold">{{ $item->product->name }}</div>
                                <small class="text-muted">{{ $item->qty }} x Rp {{ number_format($item->product->price, 0, ',', '.') }}</small>
                            </div>
                            <strong>Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</strong>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-between mt-3">
                            <span>Subtotal</span>
                            <strong>Rp {{ number_format($cart->total(), 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Biaya Pengiriman</span>
                            <strong id="shipping-cost-summary">Rp 0</strong>
                        </div>
                        <div id="transaction-summary" class="small text-muted mt-2">Belum memilih metode transaksi</div>
                        <div id="shipping-choice-summary" class="small text-muted mt-1"></div>
                        <div class="d-flex justify-content-between mt-3 mb-4 pt-3 border-top">
                            <span>Total Bayar</span>
                            <strong id="grand-total">Rp {{ number_format($cart->total(), 0, ',', '.') }}</strong>
                        </div>
                        <button type="submit" class="btn btn-bread w-100" id="submit-order" disabled>Buat Pesanan</button>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const checkoutState = {
            subtotal: Number(@json($cart->total())),
            selectedCost: 0,
            provincesLoaded: false,
            oldTransaction: @json(old('transaction_method')),
            oldPayment: @json(old('payment_method')),
            oldService: @json(old('shipping_service')),
            midtransAvailable: @json($midtransAvailable),
            routes: {
                provinces: @json(route('checkout.shipping.provinces')),
                cities: @json(route('checkout.shipping.cities')),
                costs: @json(route('checkout.shipping.costs')),
            },
            csrf: @json(csrf_token()),
        };

        const friendlyOngkirMessage = 'Layanan ongkir sedang tidak tersedia. Silakan pilih pickup atau delivery lokal.';
        const transactionRadios = document.querySelectorAll('.transaction-radio');
        const addressPanel = document.getElementById('address-panel');
        const shippingPanel = document.getElementById('shipping-panel');
        const paymentOptions = document.getElementById('payment-options');
        const addressInput = document.getElementById('shipping_address');
        const postalInput = document.getElementById('shipping_postal_code');
        const provinceSelect = document.getElementById('shipping_province_id');
        const citySelect = document.getElementById('shipping_city_id');
        const courierSelect = document.getElementById('shipping_courier');
        const provinceNameInput = document.getElementById('shipping_province');
        const cityNameInput = document.getElementById('shipping_city');
        const servicesWrap = document.getElementById('shipping-services');
        const alertBox = document.getElementById('shipping-alert');
        const loading = document.getElementById('shipping-loading');
        const submitButton = document.getElementById('submit-order');
        const shippingCostSummary = document.getElementById('shipping-cost-summary');
        const grandTotal = document.getElementById('grand-total');
        const transactionSummary = document.getElementById('transaction-summary');
        const choiceSummary = document.getElementById('shipping-choice-summary');
        const loadProvincesButton = document.getElementById('load-provinces-button');

        const paymentMap = {
            pickup: [
                { value: 'cash_pickup', label: 'Cash on Pickup', note: 'Bayar saat mengambil pesanan.' },
                { value: 'bank_transfer', label: 'Bank Transfer', note: 'Transfer sebelum pesanan diproses.' },
            ],
            delivery: [
                { value: 'cash_delivery', label: 'Cash on Delivery', note: 'Bayar saat pesanan tiba.' },
                { value: 'bank_transfer', label: 'Bank Transfer', note: 'Transfer sebelum pesanan dikirim.' },
            ],
            shipping: [
                { value: 'bank_transfer', label: 'Bank Transfer', note: 'Transfer sebelum pesanan dikirim.' },
                ...(checkoutState.midtransAvailable ? [{ value: 'midtrans', label: 'Midtrans', note: 'Bayar melalui kanal Midtrans.' }] : []),
            ],
        };

        function rupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(value);
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function selectedTransaction() {
            return document.querySelector('input[name="transaction_method"]:checked')?.value || '';
        }

        function selectedPayment() {
            return document.querySelector('input[name="payment_method"]:checked')?.value || '';
        }

        function optionText(option) {
            return option ? option.textContent.trim() : '';
        }

        function showAlert(message = friendlyOngkirMessage) {
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        }

        function clearAlert() {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }

        function setLoading(isLoading) {
            loading.classList.toggle('d-none', !isLoading);
        }

        function updateTotals(cost = 0, label = '') {
            checkoutState.selectedCost = Number(cost);
            shippingCostSummary.textContent = rupiah(checkoutState.selectedCost);
            grandTotal.textContent = rupiah(checkoutState.subtotal + checkoutState.selectedCost);
            choiceSummary.textContent = label;
            updateSubmitState();
        }

        function updateSubmitState() {
            const method = selectedTransaction();
            const payment = selectedPayment();
            const needsAddress = ['delivery', 'shipping'].includes(method);
            const hasAddress = !needsAddress || (addressInput.value.trim() && postalInput.value.trim());
            const hasShipping = method !== 'shipping' || document.querySelector('.shipping-service-radio:checked');

            submitButton.disabled = !(method && payment && hasAddress && hasShipping);
        }

        function resetServices(message = 'Pilih provinsi, kota, dan kurir untuk melihat ongkir.') {
            servicesWrap.innerHTML = `<div class="col-12"><div class="cream-note rounded-2 p-3 small">${message}</div></div>`;
            updateTotals();
        }

        async function fetchJson(url, options = {}) {
            const response = await fetch(url, options);
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || friendlyOngkirMessage);
            }

            return payload.data || [];
        }

        function renderPaymentOptions(method) {
            if (!method) {
                paymentOptions.innerHTML = '<div class="col-12"><div class="cream-note rounded-2 p-3 small">Pilih metode transaksi terlebih dahulu.</div></div>';
                return;
            }

            paymentOptions.innerHTML = paymentMap[method].map((payment) => {
                const checked = checkoutState.oldPayment === payment.value;
                return `
                    <div class="col-md-6">
                        <label class="choice-card d-block p-3 h-100">
                            <div class="form-check">
                                <input class="form-check-input payment-radio" type="radio" name="payment_method" value="${payment.value}" ${checked ? 'checked' : ''} required>
                                <span class="form-check-label fw-semibold">${payment.label}</span>
                            </div>
                            <div class="small text-muted mt-2">${payment.note}</div>
                        </label>
                    </div>
                `;
            }).join('');

            document.querySelectorAll('.payment-radio').forEach((radio) => {
                radio.addEventListener('change', updateSubmitState);
            });

            if (!document.querySelector('input[name="payment_method"]:checked')) {
                document.querySelector('input[name="payment_method"]')?.click();
            }
        }

        async function loadProvinces() {
            if (checkoutState.provincesLoaded) {
                return;
            }

            provinceSelect.innerHTML = '<option value="">Memuat provinsi...</option>';

            try {
                clearAlert();
                const provinces = await fetchJson(checkoutState.routes.provinces);
                provinceSelect.innerHTML = '<option value="">Pilih provinsi</option>';

                provinces.forEach((province) => {
                    provinceSelect.appendChild(new Option(province.province, province.province_id));
                });

                checkoutState.provincesLoaded = true;

                if (provinceSelect.dataset.old) {
                    provinceSelect.value = provinceSelect.dataset.old;
                    provinceNameInput.value = optionText(provinceSelect.selectedOptions[0]);
                    await loadCities();
                }
            } catch (error) {
                provinceSelect.innerHTML = '<option value="">Gagal memuat provinsi</option>';
                showAlert();
            }
        }

        async function loadCities() {
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Memuat kota...</option>';
            resetServices();

            if (!provinceSelect.value) {
                citySelect.innerHTML = '<option value="">Pilih provinsi dahulu</option>';
                return;
            }

            try {
                clearAlert();
                const url = `${checkoutState.routes.cities}?province_id=${encodeURIComponent(provinceSelect.value)}`;
                const cities = await fetchJson(url);
                citySelect.innerHTML = '<option value="">Pilih kota/kabupaten</option>';

                cities.forEach((city) => {
                    citySelect.appendChild(new Option(`${city.type} ${city.city_name}`, city.city_id));
                });

                citySelect.disabled = false;

                if (citySelect.dataset.old) {
                    citySelect.value = citySelect.dataset.old;
                    cityNameInput.value = optionText(citySelect.selectedOptions[0]);
                    citySelect.dataset.old = '';
                    loadCosts();
                }
            } catch (error) {
                citySelect.innerHTML = '<option value="">Gagal memuat kota</option>';
                showAlert();
            }
        }

        async function loadCosts() {
            clearAlert();
            provinceNameInput.value = optionText(provinceSelect.selectedOptions[0]);
            cityNameInput.value = optionText(citySelect.selectedOptions[0]);
            resetServices('Memuat layanan pengiriman...');

            if (!citySelect.value || !courierSelect.value) {
                resetServices();
                return;
            }

            setLoading(true);

            try {
                const services = await fetchJson(checkoutState.routes.costs, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': checkoutState.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        city_id: citySelect.value,
                        courier: courierSelect.value,
                    }),
                });

                if (!services.length) {
                    resetServices('Tidak ada layanan pengiriman untuk pilihan ini.');
                    return;
                }

                servicesWrap.innerHTML = services.map((service, index) => {
                    const checked = checkoutState.oldService === service.service || (!checkoutState.oldService && index === 0);
                    const courierName = escapeHtml(service.courier_name);
                    const serviceName = escapeHtml(service.service);
                    const description = escapeHtml(service.description || 'Layanan pengiriman');
                    const estimation = escapeHtml(service.estimation || '-');

                    return `
                        <div class="col-md-6">
                            <label class="choice-card d-block p-3 h-100">
                                <div class="form-check">
                                    <input class="form-check-input shipping-service-radio" type="radio" name="shipping_service" value="${serviceName}" data-cost="${Number(service.cost)}" data-courier="${courierName}" data-estimation="${estimation}" ${checked ? 'checked' : ''} required>
                                    <span class="form-check-label fw-semibold">${courierName} ${serviceName}</span>
                                </div>
                                <div class="small text-muted mt-2">${description}</div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="small">Estimasi ${estimation} hari</span>
                                    <strong>${rupiah(service.cost)}</strong>
                                </div>
                            </label>
                        </div>
                    `;
                }).join('');

                bindServiceRadios();
                checkoutState.oldService = null;
            } catch (error) {
                resetServices('Belum ada layanan pengiriman.');
                showAlert();
            } finally {
                setLoading(false);
            }
        }

        function bindServiceRadios() {
            document.querySelectorAll('.shipping-service-radio').forEach((radio) => {
                radio.addEventListener('change', () => {
                    updateTotals(
                        radio.dataset.cost,
                        `${radio.dataset.courier} ${radio.value} - estimasi ${radio.dataset.estimation || '-'} hari`
                    );
                });

                if (radio.checked) {
                    radio.dispatchEvent(new Event('change'));
                }
            });
        }

        function applyTransactionFlow(loadRemoteShipping = true) {
            const method = selectedTransaction();
            const needsAddress = ['delivery', 'shipping'].includes(method);
            const needsShipping = method === 'shipping';

            addressPanel.classList.toggle('d-none', !needsAddress);
            shippingPanel.classList.toggle('d-none', !needsShipping);
            addressInput.required = needsAddress;
            postalInput.required = needsAddress;
            provinceSelect.required = needsShipping;
            citySelect.required = needsShipping;
            courierSelect.required = needsShipping;

            renderPaymentOptions(method);

            if (method === 'pickup') {
                transactionSummary.textContent = 'Pickup di BreadLab';
                updateTotals(0, '');
            } else if (method === 'delivery') {
                transactionSummary.textContent = 'Local Delivery';
                updateTotals(0, 'Biaya local delivery: gratis / manual');
            } else if (method === 'shipping') {
                transactionSummary.textContent = 'Shipping / Pengiriman Jarak Jauh';
                resetServices();
                if (loadRemoteShipping) {
                    loadProvinces();
                }
            } else {
                transactionSummary.textContent = 'Belum memilih metode transaksi';
                updateTotals(0, '');
            }

            updateSubmitState();
        }

        transactionRadios.forEach((radio) => {
            radio.addEventListener('change', () => applyTransactionFlow(true));
        });

        provinceSelect.addEventListener('change', () => {
            provinceNameInput.value = optionText(provinceSelect.selectedOptions[0]);
            cityNameInput.value = '';
            citySelect.dataset.old = '';
            loadCities();
        });

        citySelect.addEventListener('change', () => {
            cityNameInput.value = optionText(citySelect.selectedOptions[0]);
            loadCosts();
        });

        courierSelect.addEventListener('change', loadCosts);
        loadProvincesButton.addEventListener('click', loadProvinces);
        addressInput.addEventListener('input', updateSubmitState);
        postalInput.addEventListener('input', updateSubmitState);

        if (checkoutState.oldTransaction) {
            applyTransactionFlow(false);
        } else {
            applyTransactionFlow(false);
        }
    </script>
</body>

</html>
