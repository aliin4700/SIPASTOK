@php
    $uid = uniqid('mr_rcpt_');
    $existingRequest = $materialRequest ?? null;
    $hasExistingReceipt = $existingRequest && method_exists($existingRequest, 'hasReceipt') && $existingRequest->hasReceipt();
    $isRequired = (bool) ($required ?? false);
    $isCompact = (bool) ($compact ?? false);
@endphp

<div class="mb-3" id="{{ $uid }}_wrapper">
    <div class="border rounded p-3 bg-light" style="border-style: dashed !important; border-color: #cbd5e1 !important;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <label for="{{ $uid }}_input" class="fw-bold mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem; color: #1e293b;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-primary flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Bukti Struk / Nota</span>
            </label>
            @if($isRequired)
                <span class="badge bg-primary text-white">Wajib Dipilih</span>
            @else
                <span class="badge bg-secondary text-white">Opsional</span>
            @endif
        </div>

        <p class="text-muted small mb-3">
            Lampirkan foto struk pembelian, nota kiriman gudang, atau dokumen PDF (maksimal 5 MB).
            @unless($isRequired)
                Kosongkan jika tidak ada nota fisik.
            @endunless
        </p>

        @if($hasExistingReceipt && ! $isCompact)
            <div id="{{ $uid }}_existing_box" class="bg-white border rounded p-2 mb-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($existingRequest->isReceiptPdf())
                            <span class="badge bg-danger text-white px-2 py-2">PDF</span>
                        @else
                            <img src="{{ $existingRequest->receipt_url }}" alt="Struk tersimpan" class="rounded border" style="width: 52px; height: 52px; object-fit: cover;">
                        @endif
                        <div>
                            <div class="small fw-semibold text-dark">Lampiran Tersimpan Saat Ini</div>
                            <div class="small text-muted text-break">{{ $existingRequest->receipt_original_name ?: basename($existingRequest->receipt_path) }}</div>
                        </div>
                    </div>
                    <a href="{{ $existingRequest->receipt_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Lihat File</a>
                </div>
                <div class="form-check mt-2 pt-2 border-top">
                    <input class="form-check-input" type="checkbox" name="remove_receipt" value="1" id="{{ $uid }}_remove_existing" @checked(old('remove_receipt'))>
                    <label class="form-check-label small text-danger fw-semibold" for="{{ $uid }}_remove_existing">
                        Hapus bukti struk tersimpan ini saat menyimpan perubahan
                    </label>
                </div>
            </div>
        @endif

        <input
            type="file"
            name="receipt"
            id="{{ $uid }}_input"
            accept="image/*,application/pdf"
            style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;"
        >

        <div class="row g-2 mb-2">
            <div class="col-12 col-sm-6">
                <button
                    type="button"
                    id="{{ $uid }}_camera_btn"
                    class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold"
                    style="min-height: 46px; border-radius: 0.5rem;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Ambil Foto Kamera HP</span>
                </button>
            </div>
            <div class="col-12 col-sm-6">
                <button
                    type="button"
                    id="{{ $uid }}_gallery_btn"
                    class="btn btn-outline-secondary bg-white w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold"
                    style="min-height: 46px; border-radius: 0.5rem; color: #334155;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Pilih Galeri / PDF</span>
                </button>
            </div>
        </div>

        <div class="text-muted" style="font-size: 0.78rem;">
            Format didukung: <strong>JPG, JPEG, PNG, WEBP, PDF</strong> (Maks. 5 MB).
        </div>

        <div id="{{ $uid }}_client_error" class="alert alert-danger py-2 px-3 small mt-2 mb-0 d-none" role="alert"></div>

        @error('receipt')
            <div class="alert alert-danger py-2 px-3 small mt-2 mb-0" role="alert">
                {{ $message }}
            </div>
        @enderror

        <div id="{{ $uid }}_preview_card" class="bg-white border rounded p-3 mt-3 d-none">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                <div class="small fw-bold text-success d-flex align-items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Pratinjau File Siap Diunggah</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="{{ $uid }}_change_btn" class="btn btn-sm btn-outline-primary">Ganti File</button>
                    <button type="button" id="{{ $uid }}_clear_btn" class="btn btn-sm btn-outline-danger">Hapus Pilihan</button>
                </div>
            </div>

            <div id="{{ $uid }}_image_wrap" class="text-center bg-light rounded border p-2 mb-2 d-none">
                <img id="{{ $uid }}_image_preview" src="" alt="Preview bukti struk" class="img-fluid rounded" style="max-height: 260px; object-fit: contain;">
            </div>

            <div id="{{ $uid }}_pdf_wrap" class="d-flex align-items-center gap-3 bg-light rounded border p-3 mb-2 d-none">
                <div class="bg-danger text-white rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="fw-bold text-dark small">Dokumen Nota PDF</div>
                    <div id="{{ $uid }}_pdf_name" class="text-muted small text-break"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small text-muted flex-wrap gap-1">
                <span id="{{ $uid }}_file_name" class="fw-semibold text-dark text-break"></span>
                <span id="{{ $uid }}_file_size"></span>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById(@json($uid . '_input'));
    const cameraBtn = document.getElementById(@json($uid . '_camera_btn'));
    const galleryBtn = document.getElementById(@json($uid . '_gallery_btn'));
    const changeBtn = document.getElementById(@json($uid . '_change_btn'));
    const clearBtn = document.getElementById(@json($uid . '_clear_btn'));
    const previewCard = document.getElementById(@json($uid . '_preview_card'));
    const imageWrap = document.getElementById(@json($uid . '_image_wrap'));
    const imagePreview = document.getElementById(@json($uid . '_image_preview'));
    const pdfWrap = document.getElementById(@json($uid . '_pdf_wrap'));
    const pdfName = document.getElementById(@json($uid . '_pdf_name'));
    const fileNameEl = document.getElementById(@json($uid . '_file_name'));
    const fileSizeEl = document.getElementById(@json($uid . '_file_size'));
    const errorBox = document.getElementById(@json($uid . '_client_error'));
    const removeExistingCheckbox = document.getElementById(@json($uid . '_remove_existing'));

    if (!input) {
        return;
    }

    const MAX_BYTES = 5 * 1024 * 1024;
    const IS_REQUIRED = @json($isRequired);
    const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
    let activeObjectUrl = null;
    const parentForm = input.closest('form');

    if (IS_REQUIRED && parentForm) {
        parentForm.addEventListener('submit', function (event) {
            if (!input.files || input.files.length === 0) {
                event.preventDefault();
                showError('Pilih atau ambil foto bukti struk / nota terlebih dahulu sebelum menyimpan.');
            }
        });
    }

    function formatBytes(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('d-none');
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    function revokePreviewUrl() {
        if (activeObjectUrl) {
            URL.revokeObjectURL(activeObjectUrl);
            activeObjectUrl = null;
        }
    }

    function resetSelection(keepError) {
        input.value = '';
        revokePreviewUrl();
        imagePreview.removeAttribute('src');
        imageWrap.classList.add('d-none');
        pdfWrap.classList.add('d-none');
        previewCard.classList.add('d-none');
        fileNameEl.textContent = '';
        fileSizeEl.textContent = '';
        pdfName.textContent = '';
        if (!keepError) {
            clearError();
        }
    }

    function openPicker(useCamera) {
        clearError();
        if (useCamera) {
            input.setAttribute('accept', 'image/*');
            input.setAttribute('capture', 'environment');
        } else {
            input.removeAttribute('capture');
            input.setAttribute('accept', 'image/*,application/pdf');
        }
        input.click();
    }

    cameraBtn.addEventListener('click', function () {
        openPicker(true);
    });

    galleryBtn.addEventListener('click', function () {
        openPicker(false);
    });

    changeBtn.addEventListener('click', function () {
        openPicker(false);
    });

    clearBtn.addEventListener('click', function () {
        resetSelection(false);
    });

    input.addEventListener('change', function () {
        clearError();
        const file = input.files && input.files[0];

        if (!file) {
            resetSelection(false);
            return;
        }

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const mimeOk = file.type ? (ALLOWED_MIMES.includes(file.type.toLowerCase()) || file.type.startsWith('image/')) : false;
        const extOk = ALLOWED_EXTS.includes(ext);

        if (!extOk && !mimeOk) {
            resetSelection(true);
            showError('Format file tidak didukung. Gunakan foto JPG, JPEG, PNG, WEBP, atau file PDF.');
            return;
        }

        if (file.size > MAX_BYTES) {
            resetSelection(true);
            showError('Ukuran file terlalu besar (' + formatBytes(file.size) + '). Batas maksimal adalah 5 MB.');
            return;
        }

        if (removeExistingCheckbox) {
            removeExistingCheckbox.checked = false;
        }

        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatBytes(file.size);

        const isPdf = file.type === 'application/pdf' || ext === 'pdf';
        revokePreviewUrl();

        if (isPdf) {
            imageWrap.classList.add('d-none');
            imagePreview.removeAttribute('src');
            pdfName.textContent = file.name;
            pdfWrap.classList.remove('d-none');
        } else {
            pdfWrap.classList.add('d-none');
            activeObjectUrl = URL.createObjectURL(file);
            imagePreview.src = activeObjectUrl;
            imageWrap.classList.remove('d-none');
        }

        previewCard.classList.remove('d-none');
    });
})();
</script>
