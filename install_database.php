<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Installer lokal XAMPP: koneksi awal ke server tanpa memilih database.
        $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $dbName = str_replace('`', '``', DB_NAME);
        $server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $server->exec("USE `$dbName`");

        $schema = file_get_contents(__DIR__ . '/database.sql');
        if ($schema === false) throw new RuntimeException('File database.sql tidak ditemukan.');
        // Buang baris CREATE DATABASE / USE karena installer sudah memilih database.
        $schema = preg_replace('/^\s*(CREATE\s+DATABASE|USE)\b[^;]*;\s*/im', '', $schema);
        foreach (explode(';', (string)$schema) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $server->exec($statement);
        }
        $message = 'Database dan tabel berhasil disiapkan. Sekarang jalankan setup_admin.php satu kali untuk membuat akun awal.';
    } catch (Throwable $e) {
        $error = 'Setup gagal: ' . $e->getMessage() . '. Pastikan MySQL di XAMPP sudah Start dan pengaturan config/database.php sesuai.';
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Database ArsipKu</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5" style="max-width:720px"><div class="card shadow-sm"><div class="card-body p-4"><h1 class="h3">Setup database ArsipKu</h1><p>Pastikan modul <strong>MySQL</strong> di XAMPP Control Panel berstatus <strong>Running</strong>. Installer membuat database <code><?=htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8')?></code> beserta tabel-tabelnya. Data tabel yang sudah ada tidak ditimpa.</p><?php if($message):?><div class="alert alert-success"><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?><br><a href="setup_admin.php">Lanjut membuat akun awal</a></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div><?php endif;?><form method="post"><button class="btn btn-primary">Buat database dan tabel</button></form><p class="small text-muted mt-3 mb-0">Setelah setup berhasil, hapus file <code>install_database.php</code>.</p></div></div></main></body></html>
