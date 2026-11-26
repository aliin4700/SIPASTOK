<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        border: 1px solid #ccc;
    }

    table tr td {
        padding: 6px;
        font-weight: normal;
        border: 1px solid #ccc;
    }

    table th {
        border: 1px solid #ccc;
        padding: 6px;
    }

    .text-right {
        text-align: right;
    }
</style>

<table>
    <tr>
        <td align="left">
            BreadLab <br>
            Perihal : {{ $judul }} <br>
            Periode:
            @if($tanggalAwal || $tanggalAkhir)
                {{ $tanggalAwal ?: '-' }} s/d {{ $tanggalAkhir ?: '-' }}
            @else
                Semua Tanggal
            @endif
            <br>
            Status Order: {{ $status ? ucwords(str_replace('_', ' ', $status)) : 'Semua Status Non-Cancelled' }} <br>
            Channel: {{ $transactionMethod ? ucwords(str_replace('_', ' ', $transactionMethod)) : 'Semua Channel' }} <br>
            Tanggal Cetak: {{ now()->format('d-m-Y H:i') }}
        </td>
    </tr>
</table>

<p></p>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Kode Produk / SKU</th>
            <th>Nama Produk</th>
            <th>Total Terjual</th>
            <th>Harga Satuan Rata-rata</th>
            <th>Total Omzet</th>
            <th>Jumlah Transaksi</th>
            <th>Channel / Sumber Order</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($soldProducts as $product)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product->product_code ?: '-' }}</td>
            <td>{{ $product->product_name }}</td>
            <td class="text-right">{{ number_format($product->total_sold, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($product->average_price, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($product->transaction_count, 0, ',', '.') }}</td>
            <td>{{ $product->channels ?: '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" align="center">Belum ada produk terjual pada filter ini.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<script>
    window.onload = function() {
        window.print();
    }
</script>
