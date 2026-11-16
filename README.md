# Sistem Informasi Pengarsipan Dokumen

## Struktur folder

```text
PENGARSIPAN_PROTOTYPE/
├── assets/style.css
├── config/{app.php,database.php}
├── includes/{functions.php,header.php,footer.php}
├── uploads/.htaccess        # File arsip yang diunggah
├── arsip.php                # CRUD folder dan dokumen, pemusnahan manual
├── usul_musnah.php          # Arsip usul musnah dan log pemusnahan
├── database.sql
├── download.php             # Akses file oleh pengguna yang login
├── folders.php              # CRUD wadah/kategori folder
├── index.php                # Dashboard
├── laporan.php
├── login.php
├── logout.php
├── setup_admin.php          # Seeder sekali pakai
└── sop.php
```

## Instalasi di XAMPP

1. Buka XAMPP Control Panel dan klik **Start** pada Apache dan MySQL.
2. Sesuaikan host, nama database, user, dan password pada `config/database.php` bila konfigurasi MySQL Anda berbeda dari bawaan XAMPP.
3. Buka `http://localhost/PENGARSIPAN_PROTOTYPE/install_database.php`, lalu klik **Buat database dan tabel**. Alternatifnya, impor `database.sql` melalui phpMyAdmin.
4. Buka `http://localhost/PENGARSIPAN_PROTOTYPE/setup_admin.php` **satu kali**, lalu segera hapus `setup_admin.php` dan `install_database.php`.
5. Masuk di `http://localhost/PENGARSIPAN_PROTOTYPE/login.php` dengan `admin` / `admin123`, lalu ganti password akun awal melalui pengelolaan user/database.

Sesuai kebutuhan prototype, password baru disimpan sebagai teks biasa. Semua query yang membawa input pengguna memakai PDO prepared statements; formulir mutasi memakai token CSRF. Upload dibatasi 10 MB dan hanya menerima PDF, dokumen Office, JPG, dan PNG. File disimpan memakai nama acak untuk menghindari benturan nama.

Folder dan dokumen arsip dikelola terpisah: menu **Folder** hanya mengatur wadah/kategori arsip, sedangkan **Dokumen (Arsip)** mengatur informasi dan file fisik dokumen yang ditempatkan pada salah satu folder. Akun staff dapat ditambahkan lewat menu **Manajemen Pengguna**. Menu SOP staff hanya menampilkan data. Folder yang dihapus menghapus dokumen terkait sesuai aturan cascade, sedangkan tombol Musnahkan membuat catatan riwayat atas operator yang sedang login.

Tampilan aplikasi menggunakan sidebar kiri, ringkasan dashboard, grafik jumlah arsip per folder, kartu tahapan SOP daur hidup, serta filter folder/tahun dan pencarian dokumen pada halaman Data Arsip. Catatan pemusnahan tetap disimpan di database sebagai log internal.


Pengguna dapat mengubah username dan password melalui menu **Akun Saya** di bagian bawah sidebar. Perubahan mensyaratkan password saat ini; password baru minimal 8 karakter. Tombol pintas upload pada dashboard dihilangkan, sementara unggah file tetap dilakukan saat menambahkan dokumen dari menu **Data Arsip**.

Menu **Manajemen Pengguna** hanya tersedia untuk Kepala Arsiparis. Dari menu ini Kepala dapat menambahkan akun Kepala Arsiparis atau Staff Arsiparis; password awal disimpan sebagai teks biasa dan dapat diubah pengguna melalui halaman Akun Saya.

