@if($materialRequest->hasReceipt())
    @php
        $badgeName = $materialRequest->receipt_original_name ?: basename($materialRequest->receipt_path);
        $isPdf = $materialRequest->isReceiptPdf();
    @endphp
    <span
        class="badge d-inline-flex align-items-center gap-1"
        style="background-color: {{ $isPdf ? '#dc3545' : '#198754' }}; color: #fff; font-weight: 600;"
        title="{{ $badgeName }}"
    >
        @if($isPdf)
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>Nota PDF</span>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Ada Struk</span>
        @endif
    </span>
@else
    <span class="text-muted">-</span>
@endif
