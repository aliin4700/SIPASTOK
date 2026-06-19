@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama Penerima</label>
        <input type="text" name="receiver_name" value="{{ old('receiver_name', $address->receiver_name) }}" class="form-control @error('receiver_name') is-invalid @enderror" required>
        @error('receiver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Nomor HP Penerima</label>
        <input type="text" name="receiver_phone" value="{{ old('receiver_phone', $address->receiver_phone) }}" class="form-control @error('receiver_phone') is-invalid @enderror" required>
        @error('receiver_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Label</label>
        <input type="text" name="label" value="{{ old('label', $address->label) }}" class="form-control @error('label') is-invalid @enderror" placeholder="Rumah / Kantor / Kos">
        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Kode Pos</label>
        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $address->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror" required>
        @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="province_id" class="form-label">Provinsi</label>
        <select name="province_id" id="province_id" class="form-control @error('province_id') is-invalid @enderror" required>
            <option value="">Memuat provinsi...</option>
            @if(old('province_id', $address->province_id))
            <option value="{{ old('province_id', $address->province_id) }}" selected>{{ old('province_name', $address->province_name) ?: old('province_id', $address->province_id) }}</option>
            @endif
        </select>
        <input type="hidden" name="province_name" id="province_name" value="{{ old('province_name', $address->province_name) }}">
        @error('province_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('province_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="city_id" class="form-label">Kota/Kabupaten</label>
        <select name="city_id" id="city_id" class="form-control @error('city_id') is-invalid @enderror" required>
            <option value="">Pilih provinsi terlebih dahulu</option>
            @if(old('city_id', $address->city_id))
            <option value="{{ old('city_id', $address->city_id) }}" data-postal-code="{{ old('postal_code', $address->postal_code) }}" selected>{{ old('city_name', $address->city_name) ?: old('city_id', $address->city_id) }}</option>
            @endif
        </select>
        <input type="hidden" name="city_name" id="city_name" value="{{ old('city_name', $address->city_name) }}">
        @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('city_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="district_id" class="form-label">Kecamatan</label>
        <select name="district_id" id="district_id" class="form-control @error('district_id') is-invalid @enderror" required>
            <option value="">Pilih kota/kabupaten terlebih dahulu</option>
            @if(old('district_id', $address->district_id))
            <option value="{{ old('district_id', $address->district_id) }}" data-postal-code="{{ old('postal_code', $address->postal_code) }}" selected>{{ old('district_name', $address->district_name) ?: old('district_id', $address->district_id) }}</option>
            @endif
        </select>
        <input type="hidden" name="district_name" id="district_name" value="{{ old('district_name', $address->district_name) }}">
        @error('district_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('district_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="full_address" class="form-label">Nama Jalan, Gedung, No. Rumah</label>
        <div class="position-relative">
            <textarea name="full_address" id="full_address" rows="3" class="form-control @error('full_address') is-invalid @enderror" autocomplete="off" required>{{ old('full_address', $address->full_address) }}</textarea>
            <div class="list-group position-absolute w-100 d-none" id="address-suggestions" style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
        </div>
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $address->latitude) }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $address->longitude) }}">
        <div class="small text-muted mt-1" id="address-coordinate-status">
            @if(old('latitude', $address->latitude) && old('longitude', $address->longitude))
            Koordinat tersimpan: {{ old('latitude', $address->latitude) }}, {{ old('longitude', $address->longitude) }}
            @else
            Ketik minimal 3 karakter untuk mencari alamat dari OpenStreetMap, atau isi manual.
            @endif
        </div>
        @error('full_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('latitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('longitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Detail Alamat</label>
        <textarea name="detail_address" rows="2" class="form-control @error('detail_address') is-invalid @enderror" placeholder="Patokan, nomor rumah, nama gedung">{{ old('detail_address', $address->detail_address) }}</textarea>
        @error('detail_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <div class="text-danger small d-none" id="rajaongkir-address-error"></div>
    </div>
    <div class="col-12">
        <label class="form-check">
            <input type="hidden" name="is_default" value="0">
            <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked(old('is_default', $address->is_default))>
            <span class="form-check-label">Jadikan alamat utama</span>
        </label>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const routes = {
            provinces: @json(route('rajaongkir.provinces')),
            cities: @json(route('rajaongkir.cities')),
            districts: @json(route('rajaongkir.districts')),
        };
        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');
        const districtSelect = document.getElementById('district_id');
        const provinceName = document.getElementById('province_name');
        const cityName = document.getElementById('city_name');
        const districtName = document.getElementById('district_name');
        const postalCode = document.getElementById('postal_code');
        const fullAddress = document.getElementById('full_address');
        const latitude = document.getElementById('latitude');
        const longitude = document.getElementById('longitude');
        const suggestions = document.getElementById('address-suggestions');
        const coordinateStatus = document.getElementById('address-coordinate-status');
        const errorBox = document.getElementById('rajaongkir-address-error');
        const initialProvinceId = @json(old('province_id', $address->province_id));
        const initialProvinceName = @json(old('province_name', $address->province_name));
        const initialCityId = @json(old('city_id', $address->city_id));
        const initialCityName = @json(old('city_name', $address->city_name));
        const initialDistrictId = @json(old('district_id', $address->district_id));
        const initialDistrictName = @json(old('district_name', $address->district_name));
        const initialPostalCode = @json(old('postal_code', $address->postal_code));
        let nominatimTimer;
        let nominatimController;

        function results(payload) {
            return payload?.rajaongkir?.results || payload?.data || [];
        }

        function errorMessage(payload, fallback) {
            return payload?.message || payload?.body?.rajaongkir?.status?.description || payload?.raw?.message || fallback;
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('d-none');
        }

        function hideError() {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }

        function showSuggestions() {
            suggestions.classList.remove('d-none');
        }

        function hideSuggestions() {
            suggestions.innerHTML = '';
            suggestions.classList.add('d-none');
        }

        function optionText(item, fallback) {
            return item.province || item.city_name || item.name || item.label || fallback;
        }

        function optionId(item) {
            return item.province_id || item.city_id || item.district_id || item.subdistrict_id || item.id || item.destination_id || '';
        }

        function ensureSelectedOption(select, value, text, postal = '') {
            if (!value) {
                return;
            }

            let option = Array.from(select.options).find((item) => item.value === String(value));

            if (!option) {
                option = document.createElement('option');
                option.value = value;
                option.textContent = text || value;
                select.appendChild(option);
            }

            if (postal) {
                option.dataset.postalCode = postal;
            }

            select.value = value;
        }

        async function loadProvinces() {
            hideError();

            try {
                const response = await fetch(routes.provinces);
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    provinceSelect.innerHTML = '<option value="">Provinsi tidak tersedia</option>';
                    showError(errorMessage(payload, 'RajaOngkir tidak tersedia. Provinsi gagal dimuat.'));
                    return;
                }

                provinceSelect.innerHTML = '<option value="">Pilih provinsi</option>';

                results(payload).forEach((item) => {
                    const value = optionId(item);

                    if (!value) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = optionText(item, value);
                    provinceSelect.appendChild(option);
                });

                ensureSelectedOption(provinceSelect, initialProvinceId, initialProvinceName);

                if (provinceSelect.value) {
                    provinceName.value = provinceSelect.options[provinceSelect.selectedIndex]?.textContent || initialProvinceName || '';
                    await loadCities(initialCityId, initialCityName, initialPostalCode, initialDistrictId, initialDistrictName);
                }
            } catch (error) {
                provinceSelect.innerHTML = '<option value="">Provinsi tidak tersedia</option>';
                showError('RajaOngkir tidak tersedia. Periksa koneksi dan konfigurasi API.');
            }
        }

        async function loadCities(selectedId = '', selectedName = '', selectedPostalCode = '', selectedDistrictId = '', selectedDistrictName = '') {
            hideError();
            citySelect.innerHTML = '<option value="">Memuat kota/kabupaten...</option>';
            citySelect.disabled = true;
            cityName.value = '';
            districtSelect.innerHTML = '<option value="">Pilih kota/kabupaten terlebih dahulu</option>';
            districtName.value = '';

            if (!provinceSelect.value) {
                citySelect.innerHTML = '<option value="">Pilih provinsi terlebih dahulu</option>';
                citySelect.disabled = false;
                return;
            }

            try {
                const response = await fetch(`${routes.cities}?province_id=${encodeURIComponent(provinceSelect.value)}`);
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    citySelect.innerHTML = '<option value="">Kota tidak tersedia</option>';
                    showError(errorMessage(payload, 'Kota/kabupaten RajaOngkir gagal dimuat.'));
                    citySelect.disabled = false;
                    return;
                }

                citySelect.innerHTML = '<option value="">Pilih kota/kabupaten</option>';

                results(payload).forEach((item) => {
                    const value = optionId(item);

                    if (!value) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = `${item.type ? item.type + ' ' : ''}${optionText(item, value)}`;
                    option.dataset.postalCode = item.postal_code || item.zip_code || '';
                    citySelect.appendChild(option);
                });

                ensureSelectedOption(citySelect, selectedId, selectedName, selectedPostalCode);

                if (citySelect.value) {
                    applyCitySelection(false);
                    await loadDistricts(selectedDistrictId, selectedDistrictName, selectedPostalCode);
                }
            } catch (error) {
                citySelect.innerHTML = '<option value="">Kota tidak tersedia</option>';
                showError('RajaOngkir tidak tersedia. Kota/kabupaten gagal dimuat.');
            } finally {
                citySelect.disabled = false;
            }
        }

        async function loadDistricts(selectedId = '', selectedName = '', selectedPostalCode = '') {
            hideError();
            districtSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';
            districtSelect.disabled = true;
            districtName.value = '';

            if (!citySelect.value) {
                districtSelect.innerHTML = '<option value="">Pilih kota/kabupaten terlebih dahulu</option>';
                districtSelect.disabled = false;
                return;
            }

            try {
                const response = await fetch(`${routes.districts}?city_id=${encodeURIComponent(citySelect.value)}`);
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    districtSelect.innerHTML = '<option value="">Kecamatan tidak tersedia</option>';
                    showError(errorMessage(payload, 'Kecamatan RajaOngkir gagal dimuat.'));
                    districtSelect.disabled = false;
                    return;
                }

                districtSelect.innerHTML = '<option value="">Pilih kecamatan</option>';

                results(payload).forEach((item) => {
                    const value = optionId(item);

                    if (!value) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = optionText(item, value);
                    option.dataset.postalCode = item.postal_code || item.zip_code || '';
                    districtSelect.appendChild(option);
                });

                ensureSelectedOption(districtSelect, selectedId, selectedName, selectedPostalCode);

                if (districtSelect.value) {
                    applyDistrictSelection(false);
                }
            } catch (error) {
                districtSelect.innerHTML = '<option value="">Kecamatan tidak tersedia</option>';
                showError('RajaOngkir tidak tersedia. Kecamatan gagal dimuat.');
            } finally {
                districtSelect.disabled = false;
            }
        }

        function applyProvinceSelection() {
            provinceName.value = provinceSelect.options[provinceSelect.selectedIndex]?.textContent || '';
            citySelect.innerHTML = '<option value="">Pilih kota/kabupaten</option>';
            cityName.value = '';
            districtSelect.innerHTML = '<option value="">Pilih kota/kabupaten terlebih dahulu</option>';
            districtName.value = '';
            loadCities();
        }

        function applyCitySelection(allowPostalOverwrite = true) {
            const option = citySelect.options[citySelect.selectedIndex];
            cityName.value = option?.textContent || '';
            districtSelect.innerHTML = '<option value="">Pilih kecamatan</option>';
            districtName.value = '';

            if (allowPostalOverwrite && option?.dataset.postalCode) {
                postalCode.value = option.dataset.postalCode;
            }
        }

        function applyDistrictSelection(allowPostalOverwrite = true) {
            const option = districtSelect.options[districtSelect.selectedIndex];
            districtName.value = option?.textContent || '';

            if (allowPostalOverwrite && option?.dataset.postalCode) {
                postalCode.value = option.dataset.postalCode;
            }
        }

        function scopedAddressQuery() {
            return [
                fullAddress.value.trim(),
                districtName.value,
                cityName.value,
                provinceName.value,
                'Indonesia',
            ].filter(Boolean).join(', ');
        }

        async function searchAddressSuggestions() {
            const typed = fullAddress.value.trim();

            if (typed.length < 3) {
                hideSuggestions();
                return;
            }

            if (nominatimController) {
                nominatimController.abort();
            }

            nominatimController = new AbortController();
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.searchParams.set('format', 'jsonv2');
            url.searchParams.set('addressdetails', '1');
            url.searchParams.set('limit', '5');
            url.searchParams.set('countrycodes', 'id');
            url.searchParams.set('accept-language', 'id');
            url.searchParams.set('q', scopedAddressQuery());

            try {
                coordinateStatus.textContent = 'Mencari alamat di OpenStreetMap...';
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                    },
                    signal: nominatimController.signal,
                });

                if (!response.ok) {
                    throw new Error('Nominatim unavailable');
                }

                const places = await response.json();
                suggestions.innerHTML = '';

                if (!places.length) {
                    suggestions.innerHTML = '<div class="list-group-item text-muted">Alamat tidak ditemukan. Anda tetap bisa mengisi manual.</div>';
                    showSuggestions();
                    coordinateStatus.textContent = 'Alamat tidak ditemukan di OpenStreetMap.';
                    return;
                }

                places.forEach((place) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.textContent = place.display_name;
                    button.addEventListener('click', () => {
                        fullAddress.value = place.display_name;
                        latitude.value = Number(place.lat).toFixed(7);
                        longitude.value = Number(place.lon).toFixed(7);
                        coordinateStatus.textContent = `Koordinat tersimpan: ${latitude.value}, ${longitude.value}`;
                        hideSuggestions();
                    });
                    suggestions.appendChild(button);
                });

                showSuggestions();
                coordinateStatus.textContent = 'Pilih salah satu alamat atau lanjut isi manual.';
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                hideSuggestions();
                coordinateStatus.textContent = 'Autocomplete OpenStreetMap tidak tersedia. Anda tetap bisa mengisi alamat manual.';
            }
        }

        function scheduleAddressSearch() {
            latitude.value = '';
            longitude.value = '';
            coordinateStatus.textContent = 'Menunggu pencarian alamat...';
            clearTimeout(nominatimTimer);
            nominatimTimer = setTimeout(searchAddressSuggestions, 700);
        }

        provinceSelect.addEventListener('change', applyProvinceSelection);
        citySelect.addEventListener('change', () => {
            applyCitySelection(true);
            loadDistricts();
        });
        districtSelect.addEventListener('change', () => applyDistrictSelection(true));
        fullAddress.addEventListener('input', scheduleAddressSearch);
        document.addEventListener('click', (event) => {
            if (!suggestions.contains(event.target) && event.target !== fullAddress) {
                hideSuggestions();
            }
        });
        provinceSelect.closest('form')?.addEventListener('submit', (event) => {
            if (!provinceSelect.value || !provinceName.value || !citySelect.value || !cityName.value || !districtSelect.value || !districtName.value) {
                event.preventDefault();
                showError('Pilih provinsi, kota/kabupaten, dan kecamatan dari dropdown RajaOngkir sebelum menyimpan alamat.');
            }
        });

        loadProvinces();
    });
</script>
