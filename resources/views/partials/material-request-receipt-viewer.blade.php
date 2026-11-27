@php
    $viewerUid = uniqid('mr_rcpt_view_');
    $hasReceipt = $materialRequest->hasReceipt();
    $isPdf = $materialRequest->isReceiptPdf();
    $receiptName = $materialRequest->receipt_original_name ?: ($hasReceipt ? basename($materialRequest->receipt_path) : '');
    $canManage = (bool) ($canManageReceipt ?? false) && ! empty($updateRoute);
    $showUploadFormInitially = $errors->has('receipt');
@endphp

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-primary flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h5 class="mb-0 fw-bold">Bukti Struk / Nota Lampiran</h5>
            </div>

            @if($hasReceipt && $isPdf)
                <span class="badge bg-info text-white">Nota PDF Terlampir</span>
            @elseif($hasReceipt)
                <span class="badge bg-success text-white">Foto Struk Terlampir</span>
            @else
                <span class="badge bg-secondary text-white">Tanpa Lampiran (Opsional)</span>
            @endif
        </div>

        @if($hasReceipt)
            @if($isPdf)
                <div class="border rounded bg-light p-3 mb-3">
                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                        <div class="bg-danger text-white rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold text-dark">Dokumen Nota (PDF)</div>
                            <div class="text-muted small text-break">{{ $receiptName }}</div>
                        </div>
                    </div>

                    <div class="d-none d-md-block mb-3">
                        <iframe src="{{ $materialRequest->receipt_url }}" title="Preview Nota PDF" class="w-100 border rounded bg-white" style="height: 380px;"></iframe>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $materialRequest->receipt_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>Buka PDF di Tab Baru</span>
                        </a>
                        <a href="{{ $downloadRoute }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Unduh Nota Asli</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="border rounded bg-light p-3 mb-3">
                    <div class="text-center mb-2">
                        <img
                            id="{{ $viewerUid }}_thumb"
                            src="{{ $materialRequest->receipt_url }}"
                            alt="Bukti Struk {{ $materialRequest->request_number }}"
                            class="img-fluid rounded border bg-white shadow-sm"
                            style="max-height: 360px; object-fit: contain; cursor: zoom-in;"
                        >
                    </div>
                    <div class="text-center small text-muted mb-3">
                        <span class="text-break">{{ $receiptName }}</span> &middot; Ketuk gambar untuk memperbesar
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <button type="button" id="{{ $viewerUid }}_zoom_btn" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                            </svg>
                            <span>Perbesar Foto Struk</span>
                        </button>
                        <a href="{{ $materialRequest->receipt_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>Buka Ukuran Penuh</span>
                        </a>
                        <a href="{{ $downloadRoute }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Unduh Nota Asli</span>
                        </a>
                    </div>
                </div>

                <div
                    id="{{ $viewerUid }}_lightbox"
                    style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.92); padding: 1rem; overflow: auto;"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Pratinjau Bukti Struk"
                >
                    <div style="min-height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem;">
                        <div style="width: 100%; max-width: 960px; display: flex; justify-content: space-between; align-items: center; color: #fff; gap: 1rem;">
                            <span class="small fw-semibold text-break">{{ $receiptName }}</span>
                            <button type="button" id="{{ $viewerUid }}_close_lightbox" class="btn btn-light btn-sm fw-bold">Tutup &times;</button>
                        </div>
                        <img
                            src="{{ $materialRequest->receipt_url }}"
                            alt="Bukti Struk Penuh"
                            style="max-width: 100%; max-height: 82vh; object-fit: contain; border-radius: 0.5rem; background: #fff;"
                        >
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <a href="{{ $materialRequest->receipt_url }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm">Buka Tab Baru</a>
                            <a href="{{ $downloadRoute }}" class="btn btn-primary btn-sm">Unduh Nota Asli</a>
                        </div>
                    </div>
                </div>

                <script>
                (function () {
                    const thumb = document.getElementById(@json($viewerUid . '_thumb'));
                    const zoomBtn = document.getElementById(@json($viewerUid . '_zoom_btn'));
                    const lightbox = document.getElementById(@json($viewerUid . '_lightbox'));
                    const closeBtn = document.getElementById(@json($viewerUid . '_close_lightbox'));

                    if (!lightbox) {
                        return;
                    }

                    function openLightbox() {
                        lightbox.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    }

                    function closeLightbox() {
                        lightbox.style.display = 'none';
                        document.body.style.overflow = '';
                    }

                    if (thumb) {
                        thumb.addEventListener('click', openLightbox);
                    }
                    if (zoomBtn) {
                        zoomBtn.addEventListener('click', openLightbox);
                    }
                    if (closeBtn) {
                        closeBtn.addEventListener('click', closeLightbox);
                    }

                    lightbox.addEventListener('click', function (event) {
                        if (event.target === lightbox) {
                            closeLightbox();
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' && lightbox.style.display === 'block') {
                            closeLightbox();
                        }
                    });
                })();
                </script>
            @endif
        @else
            <div class="border rounded bg-light p-3 text-muted small mb-3">
                Tidak ada foto struk atau nota yang dilampirkan pada dokumen Stok In ini (lampiran bersifat opsional).
            </div>
        @endif

        @if($canManage)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" id="{{ $viewerUid }}_toggle_upload" class="btn btn-outline-primary btn-sm">
                    {{ $hasReceipt ? 'Ganti / Perbarui Bukti Struk' : '+ Upload Bukti Struk (Foto / PDF)' }}
                </button>

                @if($hasReceipt && ! empty($deleteRoute))
                    <form method="post" action="{{ $deleteRoute }}" class="d-inline" onsubmit="return confirm('Hapus file bukti struk ini secara permanen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Lampiran</button>
                    </form>
                @endif
            </div>

            <div id="{{ $viewerUid }}_upload_panel" class="mt-3 {{ $showUploadFormInitially ? '' : 'd-none' }}">
                <form method="post" action="{{ $updateRoute }}" enctype="multipart/form-data" class="border rounded p-3 bg-white">
                    @csrf
                    @include('partials.material-request-receipt-input', [
                        'materialRequest' => $materialRequest,
                        'required' => true,
                        'compact' => true,
                    ])
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">Simpan Bukti Struk</button>
                        <button type="button" id="{{ $viewerUid }}_cancel_upload" class="btn btn-secondary btn-sm">Batal</button>
                    </div>
                </form>
            </div>

            <script>
            (function () {
                const toggleBtn = document.getElementById(@json($viewerUid . '_toggle_upload'));
                const cancelBtn = document.getElementById(@json($viewerUid . '_cancel_upload'));
                const panel = document.getElementById(@json($viewerUid . '_upload_panel'));

                if (!toggleBtn || !panel) {
                    return;
                }

                toggleBtn.addEventListener('click', function () {
                    panel.classList.toggle('d-none');
                });

                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function () {
                        panel.classList.add('d-none');
                    });
                }
            })();
            </script>
        @endif
    </div>
</div>
