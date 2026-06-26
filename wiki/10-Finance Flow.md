# Cashier POS
## Related Wiki Pages
- [[11-Cashier-Shift]]
- [[15-Finance-System]]
- [[04-Database-Schema]]
- [[02-Auth-and-Roles]]
- [[20-Changelog]]
## Fungsi
- Menampilkan katalog produk
- Session cart POS
- Finalisasi order outlet
- Cetak receipt
- Riwayat order kasir

## Service Type
- dine_in
- pickup
- delivery
- shipping

## Payment Method
- cash
- bank_transfer
- qris_manual

## Catatan penting
POS finalize membuat:
- order
- order_items
- payment
- cashier_cash_movements jika cash

Saat ini POS belum otomatis mengurangi stok bahan berdasarkan resep.