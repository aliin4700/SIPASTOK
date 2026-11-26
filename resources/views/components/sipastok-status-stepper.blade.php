@props([
    'status' => 'draft',
    'role' => 'staff',
    'customGuide' => null,
])

@php
    $normalizedStatus = strtolower((string) ($status ?? 'draft'));
    $isCancelled = $normalizedStatus === 'cancelled';

    $step1State = match ($normalizedStatus) {
        'draft' => 'active',
        'submitted', 'reviewed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'active',
    };

    $step2State = match ($normalizedStatus) {
        'draft' => 'pending',
        'submitted' => 'active',
        'reviewed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'pending',
    };

    $step3State = match ($normalizedStatus) {
        'reviewed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'pending',
    };

    if ($customGuide) {
        $guideText = $customGuide;
    } elseif ($isCancelled) {
        $guideText = 'Sesi Stock Opname ini telah dibatalkan dan tidak mempengaruhi saldo stok di sistem.';
    } elseif ($normalizedStatus === 'draft') {
        $guideText = $role === 'staff'
            ? 'Langkah saat ini: Periksa kembali angka stok fisik yang Anda hitung. Jika sudah yakin benar, klik tombol "Submit untuk Review" agar dikirim ke Manager/Admin.'
            : 'Status masih Draft (sedang diinput oleh Staff di outlet). Menunggu Staff menekan tombol Submit sebelum dapat direview.';
    } elseif ($normalizedStatus === 'submitted') {
        $guideText = $role === 'staff'
            ? 'Hasil perhitungan fisik sudah dikirim! Saat ini sedang menunggu pemeriksaan dan persetujuan (Approve) dari Manager atau Admin.'
            : 'Tindakan Diperlukan: Periksa daftar selisih di bawah ini. Anda dapat mengoreksi angka fisik atau memilih alasan selisih, lalu klik "Approve & Sesuaikan Stok" untuk memperbarui saldo inventori.';
    } else {
        $guideText = 'Selesai! Hasil Stock Opname ini telah diverifikasi dan saldo stok bahan baku di sistem telah disesuaikan secara otomatis. Anda dapat mencetak Berita Acara jika diperlukan.';
    }
@endphp

<style>
    .sipastok-stepper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .sipastok-step {
        flex: 1 1 220px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 0.6rem;
        border: 1px solid #dee2e6;
        background: #fff;
        transition: all 0.2s ease;
    }
    .sipastok-step-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .sipastok-step.is-active {
        border-color: #0d6efd;
        background: #f0f7ff;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.12);
    }
    .sipastok-step.is-active .sipastok-step-icon {
        background: #0d6efd;
        color: #fff;
    }
    .sipastok-step.is-completed {
        border-color: #198754;
        background: #f2fbf6;
    }
    .sipastok-step.is-completed .sipastok-step-icon {
        background: #198754;
        color: #fff;
    }
    .sipastok-step.is-pending {
        border-color: #e9ecef;
        background: #f8f9fa;
        color: #6c757d;
    }
    .sipastok-step.is-pending .sipastok-step-icon {
        background: #e9ecef;
        color: #6c757d;
    }
    .sipastok-step.is-cancelled {
        border-color: #dc3545;
        background: #fff5f5;
        color: #dc3545;
    }
    .sipastok-step.is-cancelled .sipastok-step-icon {
        background: #dc3545;
        color: #fff;
    }
</style>

<div class="mb-3 no-print">
    <div class="sipastok-stepper mb-2">
        <div class="sipastok-step is-{{ $step1State }}">
            <span class="sipastok-step-icon">
                @if($step1State === 'completed') &#10003; @else 1 @endif
            </span>
            <div>
                <div class="fw-bold small text-uppercase">Tahap 1: Input Fisik</div>
                <div class="small text-muted">Oleh Staff Outlet (Draft)</div>
            </div>
        </div>

        <div class="sipastok-step is-{{ $step2State }}">
            <span class="sipastok-step-icon">
                @if($step2State === 'completed') &#10003; @else 2 @endif
            </span>
            <div>
                <div class="fw-bold small text-uppercase">Tahap 2: Verifikasi & Approval</div>
                <div class="small text-muted">Oleh Manager / Admin (Submitted)</div>
            </div>
        </div>

        <div class="sipastok-step is-{{ $step3State }}">
            <span class="sipastok-step-icon">
                @if($isCancelled) &times; @elseif($step3State === 'completed') &#10003; @else 3 @endif
            </span>
            <div>
                @if($isCancelled)
                    <div class="fw-bold small text-uppercase text-danger">Dibatalkan (Cancelled)</div>
                    <div class="small text-danger">Sesi opname tidak dilanjutkan</div>
                @else
                    <div class="fw-bold small text-uppercase">Tahap 3: Selesai & Stok Disesuaikan</div>
                    <div class="small text-muted">Stok Sistem Terupdate (Reviewed)</div>
                @endif
            </div>
        </div>
    </div>

    <div class="alert {{ $isCancelled ? 'alert-danger' : ($normalizedStatus === 'reviewed' ? 'alert-success' : ($normalizedStatus === 'submitted' ? 'alert-warning' : 'alert-info')) }} py-2 px-3 mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="small">
            <strong>Panduan Alur SIPASTOK:</strong> {{ $guideText }}
        </div>
        @if($isCancelled)
            <span class="badge bg-danger text-uppercase">Status: Cancelled</span>
        @endif
    </div>
</div>
