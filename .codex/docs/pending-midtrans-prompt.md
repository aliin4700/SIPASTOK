PROMPT 10 — IMPLEMENTASI MIDTRANS MODULE KE OPTION B

Project Breadlab sudah selesai:

1. Refactor database foundation ke Option B.
2. Refactor Product + Category ke Option B.
3. Refactor Customer + Auth ke Option B.
4. Refactor Cart Module ke Option B.
5. Refactor Checkout Core ke Option B.
6. Refactor Order Module + Admin Order dasar ke Option B.
7. Refactor Payment Module Manual ke Option B.
8. Refactor Shipment Module dengan pickup, local_delivery, OpenStreetMap/Leaflet, dan RajaOngkir ke Option B.

Sekarang lanjutkan tahap berikutnya: MIDTRANS MODULE.

Fokus tahap ini:

* Midtrans Snap transaction
* Midtrans payment page/button
* Midtrans notification callback
* sinkronisasi payments dan orders
* tetap menjaga pemisahan order status dan payment status

PENTING:

* Jangan implementasi chat dulu.
* Jangan implementasi dashboard chart dulu.
* Jangan redesign UI.
* Pertahankan tampilan visual yang sudah ada.
* Jangan membuat ulang project dari nol.
* Jangan mengubah payment manual yang sudah stabil kecuali perlu link/integrasi Midtrans.
* Jangan mengubah RajaOngkir/OpenStreetMap yang sudah stabil kecuali ada konflik kecil.

Baca dulu:

* .codex/AGENTS.md
* .codex/docs/project-overview.md
* .codex/docs/database.md
* .codex/docs/modules.md
* .codex/docs/workflow.md
* .codex/docs/roadmap.md
* .codex/docs/ui-design.md
* .codex/docs/coding-standard.md
* .codex/docs/api.md

KONDISI SAAT INI:

* Checkout sudah membuat orders, order_items, payments, shipments.
* Payment manual sudah berjalan:

  * cash confirmation
  * bank transfer upload proof
  * admin verify/reject
* Payment method midtrans saat ini masih placeholder.
* payments table sudah memiliki field:

  * midtrans_order_id
  * midtrans_transaction_id
  * midtrans_snap_token
  * paid_at
* orders memiliki:

  * status
  * payment_status
  * payment_method
  * total_price
* Midtrans belum boleh mencampur order status dan payment status.

TARGET FINAL MIDTRANS MODULE:

Controller:

* MidtransController
* PaymentController jika perlu update kecil
* CheckoutController jika perlu redirect/link kecil
* OrderController jika perlu update tampilan kecil

Model:

* Payment
* Order
* OrderItem
* Shipment
* Customer
* User

View:

* resources/views/v_payment/show.blade.php
* resources/views/v_order/detail.blade.php
* resources/views/backend/v_payment/show.blade.php jika perlu

Route Midtrans:

* POST /midtrans/{order}/create -> midtrans.create
* POST /midtrans/callback -> midtrans.callback
* GET /midtrans/finish -> midtrans.finish
* GET /midtrans/unfinish -> midtrans.unfinish
* GET /midtrans/error -> midtrans.error

ENV:

* MIDTRANS_SERVER_KEY=
* MIDTRANS_CLIENT_KEY=
* MIDTRANS_IS_PRODUCTION=false
* MIDTRANS_IS_SANITIZED=true
* MIDTRANS_IS_3DS=true

MIDTRANS RULES:

1. Jangan hardcode Midtrans key.
2. Gunakan .env dan config/services.php.
3. Tambahkan konfigurasi ke .env.example.
4. Gunakan sandbox mode jika MIDTRANS_IS_PRODUCTION=false.
5. Jangan menyimpan secret key ke repository.
6. Jangan membuat tabel baru jika field payments sudah cukup.
7. Gunakan payments.midtrans_order_id untuk ID transaksi Midtrans.
8. Gunakan payments.midtrans_snap_token untuk menyimpan Snap token.
9. Gunakan payments.midtrans_transaction_id jika callback mengirim transaction_id.
10. Jangan mengubah payment manual yang sudah berjalan.

PAYMENT STATUS RULES:

payments.payment_status adalah sumber utama status pembayaran.

orders.payment_status hanya summary.

orders.status adalah status proses order.

Ketika Midtrans payment berhasil:

* payments.payment_status = paid
* payments.paid_at = now()
* payments.midtrans_transaction_id = transaction_id jika ada
* orders.payment_status = paid
* jika orders.status masih pending atau waiting_payment, ubah orders.status = paid

Ketika Midtrans payment pending:

* payments.payment_status = pending
* orders.payment_status = pending
* orders.status tetap waiting_payment

Ketika Midtrans expired:

* payments.payment_status = expired
* orders.payment_status = expired
* orders.status boleh tetap waiting_payment atau menjadi cancelled jika sudah menjadi rule project.
  Untuk tahap ini, biarkan orders.status tetap waiting_payment agar admin masih bisa cek.

Ketika Midtrans failed/deny/cancel:

* payments.payment_status = failed atau cancelled sesuai status
* orders.payment_status = failed atau cancelled
* jangan otomatis completed
* jangan otomatis processing

TRANSACTION STATUS MAPPING:

Map Midtrans transaction_status:

* capture + fraud_status accept/challenge:

  * accept -> paid
  * challenge -> pending
* settlement -> paid
* pending -> pending
* deny -> failed
* cancel -> cancelled
* expire -> expired
* failure -> failed

TUGAS:

1. Audit config/services.php, .env.example, PaymentController, CheckoutController, v_payment/show.blade.php, v_order/detail.blade.php, routes/web.php.

2. Buat MidtransController jika belum ada.

3. MidtransController harus memiliki method:

   * create(Order $order)
   * callback(Request $request)
   * finish(Request $request)
   * unfinish(Request $request)
   * error(Request $request)

4. Route create:

   * hanya untuk customer pemilik order
   * hanya untuk order dengan payment_method = midtrans
   * hanya jika payment belum paid
   * load orderItems, customer.user, payment, shipment
   * buat midtrans_order_id unik, misalnya order_number atau BRD-MT-{order_number}
   * buat parameter Snap:

     * transaction_details:

       * order_id
       * gross_amount = orders.total_price
     * customer_details:

       * first_name/name
       * email
       * phone jika ada
     * item_details:

       * produk order_items
       * shipping cost sebagai item tambahan jika shipping_cost > 0
     * callbacks/finish_url jika diperlukan
   * simpan snap token ke payments.midtrans_snap_token
   * simpan midtrans_order_id ke payments.midtrans_order_id
   * redirect kembali ke payment.show atau order.detail dengan token tersedia

5. Payment page:

   * update v_payment/show.blade.php
   * jika payment_method = midtrans dan payment belum paid:

     * tampilkan tombol "Bayar dengan Midtrans"
     * jika snap token belum ada, form POST ke midtrans.create
     * jika snap token sudah ada, tampilkan tombol bayar yang memanggil Snap JS
   * tambahkan Midtrans client key dari config
   * load Snap JS sandbox/production sesuai config
   * jangan redesign UI

6. Order detail:

   * update v_order/detail.blade.php
   * jika payment_method = midtrans, tampilkan link ke payment.show
   * jangan taruh logic Midtrans terlalu banyak di order detail

7. Callback:

   * route midtrans.callback harus tanpa middleware customer/admin
   * validasi signature key jika memungkinkan
   * baca order_id dari callback
   * cari Payment berdasarkan midtrans_order_id
   * update Payment sesuai transaction_status
   * update Order summary sesuai status
   * jangan mengubah order status menjadi processing/completed otomatis
   * response JSON 200 setelah berhasil

8. Jika signature key mismatch:

   * return 403 atau error yang sesuai
   * jangan update payment/order

9. Finish/unfinish/error:

   * redirect ke order detail atau payment show jika order bisa dikenali
   * jika tidak bisa, redirect ke order.history atau beranda dengan pesan
   * jangan update status dari finish URL; status final harus dari callback

10. Update backend/v_payment/show.blade.php:

* tampilkan midtrans_order_id
* tampilkan midtrans_transaction_id
* tampilkan snap_token status jika ada
* jangan tampilkan server key
* jangan beri admin tombol verify manual untuk Midtrans paid kecuali hanya display

11. Update .env.example:

* MIDTRANS_SERVER_KEY=
* MIDTRANS_CLIENT_KEY=
* MIDTRANS_IS_PRODUCTION=false
* MIDTRANS_IS_SANITIZED=true
* MIDTRANS_IS_3DS=true

12. Update config/services.php:

* midtrans config membaca env di atas

13. Jika package Midtrans belum ada:

* cek composer.json
* jika belum ada, tambahkan package official yang sesuai
* jika tidak bisa install otomatis, berikan instruksi composer require
* jangan membuat custom HTTP Midtrans yang besar jika package resmi lebih cocok

14. Jangan mengubah migration foundation kecuali benar-benar perlu.
    Field Midtrans sudah ada di payments, jadi seharusnya tidak perlu migration baru.

15. Jangan ubah flow bank transfer dan cash.
    Pastikan:

    * upload proof tetap berjalan
    * verify/reject tetap berjalan
    * confirm cash tetap berjalan

16. Jalankan:

* php artisan optimize
* php artisan route
* php artisan route --name=midtrans
* php artisan route --name=payment
* php artisan test jika memungkinkan
* php artisan view jika Blade aman

BATASAN:

* Jangan implementasi chat.
* Jangan implementasi dashboard chart.
* Jangan redesign UI.
* Jangan mengubah RajaOngkir/Leaflet besar-besaran.
* Jangan mengubah Product/Category/Customer/Cart/Order/Payment manual yang sudah stabil kecuali integrasi link Midtrans.
* Jangan hardcode API key.
* Jangan menyimpan secret ke repository.
* Jangan update orders.status menjadi completed otomatis dari Midtrans.

OUTPUT YANG SAYA INGINKAN:

A. Ringkasan perubahan
B. Route Midtrans final yang aktif
C. Controller yang dibuat/diubah
D. Method MidtransController final
E. Perubahan Payment page
F. Perubahan Order detail
G. Perubahan Backend Payment detail
H. Konfigurasi .env.example dan services.php
I. Flow create Snap token
J. Flow callback Midtrans
K. Mapping transaction_status ke payments.payment_status
L. Pemisahan payment status dan order status
M. Package composer yang ditambah jika ada
N. File lama yang dihapus atau dipertahankan sementara
O. Command artisan yang dijalankan
P. Hasil route --name=midtrans
Q. Risiko tersisa
R. Checklist testing manual

Checklist testing manual:

* isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY sandbox di .env
* php artisan optimize
* login customer
* checkout dengan payment_method midtrans
* buka detail order
* buka halaman payment
* klik buat/bayar Midtrans
* pastikan snap token tersimpan
* pastikan Snap popup muncul
* lakukan pembayaran sandbox
* pastikan callback mengubah payments.payment_status menjadi paid
* pastikan payments.paid_at terisi
* pastikan orders.payment_status menjadi paid
* pastikan orders.status menjadi paid jika sebelumnya waiting_payment
* cek pending/expired/cancel jika memungkinkan
* login admin
* buka backend payment detail
* cek midtrans_order_id dan transaction_id tampil
* pastikan bank_transfer upload proof masih berjalan
* pastikan cash confirm masih berjalan
* pastikan tidak ada server key tampil di UI
