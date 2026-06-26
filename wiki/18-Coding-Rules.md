# Coding Rules

## General Rules
- Kerjakan task secara incremental.
- Jangan ubah modul yang tidak diminta.
- Jangan rename route tanpa mengecek view.
- Jangan buat migration baru jika tidak perlu.
- Jangan reset database tanpa izin.
- Jangan ubah payment, checkout, shipment, finance, atau POS finalize kecuali diminta.

## Output Rules
Setelah selesai, selalu jelaskan:
- ringkasan perubahan
- file changed
- route changed
- migration changed
- cara test
- risiko

## Laravel Rules
- Gunakan Laravel 10 convention.
- Gunakan Bootstrap.
- Gunakan Blade view.
- Ikuti role middleware yang sudah ada.