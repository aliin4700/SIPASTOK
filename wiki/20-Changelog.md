# Changelog

## 2026-06-21

### Implemented
- Daily Operation Closing Summary untuk staff.
- Validasi stock opname sebelum submit/tutup operasional.
- Backend admin monitoring daily operation.
- Manager daily operation read-only.
- Shared summary partial.
- Link menu staff, manager, backend.

### Files Changed
- `app/Http/Controllers/Staff/DailyOperationController.php`
- `app/Http/Controllers/Backend/DailyOperationController.php`
- `app/Http/Controllers/Manager/InventoryMonitoringController.php`
- `routes/web.php`
- `resources/views/partials/daily-operation-summary.blade.php`

### Database Changes
- Tidak ada migration baru.
- Tidak ada update stok.
- Tidak ada stock movement.
- Tidak ada perubahan POS, checkout, payment, shipment, atau finance.

### Routes Added / Updated
- `staff.daily-operations.closing-summary`
- `backend.daily-operations.index`
- `backend.daily-operations.show`
- `manager.daily-operations.show`

### Notes
Perubahan ini hanya menambahkan monitoring dan validasi daily operation. Tidak mengubah alur POS, checkout, payment, shipment, atau finance.