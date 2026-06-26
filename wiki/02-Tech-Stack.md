# Tech Stack

## Backend

| Teknologi | Keterangan |
|---|---|
| Laravel 10 | Framework utama |
| PHP | Bahasa backend |
| PostgreSQL | Database utama |
| Blade | Template engine |
| Eloquent ORM | ORM Laravel |
| Artisan Command | Import master item dan maintenance |

## Frontend

| Teknologi | Keterangan |
|---|---|
| Bootstrap | UI utama |
| Blade Views | Halaman server-rendered |
| Chart.js | Dashboard chart |
| JavaScript sederhana | Dynamic form ringan |
| AJAX Polling | Chat order tanpa websocket |

## External Service

| Service | Fungsi | Status |
|---|---|---|
| Google OAuth | Login akun Google | Digunakan |
| RajaOngkir / Komerce | Shipping cost | Digunakan / masih perlu testing |
| Midtrans | Payment gateway | Belum, menunggu API key |
| Leaflet / OpenStreetMap | Local delivery map | Digunakan / direncanakan |

## Development Tools

| Tool | Fungsi |
|---|---|
| VS Code | Editor |
| PowerShell | Terminal Windows |
| GitHub | Version control |
| Codex | Bantuan generate/refactor code |
| Obsidian | Wiki dokumentasi project |

## Package / Library Penting

| Library | Fungsi |
|---|---|
| Laravel Auth | Login dan session |
| Laravel Socialite / Google OAuth | Login Google |
| Chart.js | Chart dashboard |
| Bootstrap | Tampilan UI |
| PHP ZipArchive/XML | Import XLSX master item tanpa package tambahan |

## Struktur Panel

| Panel | Base URL | Teknologi |
|---|---|---|
| Customer Website | `/` | Blade + Bootstrap |
| Backend Admin | `/backend` | Blade + Bootstrap |
| Cashier POS | `/cashier` | Blade + Bootstrap |
| Staff Outlet | `/staff` | Blade + Bootstrap |
| Manager | `/manager` | Blade + Bootstrap |

## Prinsip Development

- Perubahan harus incremental.
- Hindari refactor besar tanpa alasan.
- Jangan jalankan `migrate:fresh` kecuali benar-benar disetujui.
- Jangan ubah POS, finance, checkout, payment, shipment jika prompt tidak menyentuh area itu.
- Semua perubahan stok di masa depan harus lewat service pusat.
- Controller tidak boleh update stok langsung.
- Semua perubahan stok harus memiliki `stock_movements`.

## Command Umum

```bash
php artisan optimize:clear
php artisan migrate
php artisan route:list
php artisan test
php artisan view:cache