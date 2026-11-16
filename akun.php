<?php
require_once __DIR__ . '/config/app.php';
require_login();
$error = '';
$success = '';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username harus 3–50 karakter, hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif ($currentPassword === '') {
        $error = 'Masukkan password saat ini untuk menyimpan perubahan.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM `user` WHERE id_user=?');
        $stmt->execute([$_SESSION['user']['id_user']]);
        $storedPassword = $stmt->fetchColumn();
        if ($storedPassword === false || !password_matches($currentPassword, (string)$storedPassword)) {
            $error = 'Password saat ini tidak sesuai.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password baru tidak sama.';
        } else {
            try {
                // Simpan plaintext sesuai kebutuhan prototype. Jika akun lama masih hash,
                // menyimpan perubahan username juga memigrasikannya ke plaintext.
                $passwordToStore = $newPassword === '' ? $currentPassword : $newPassword;
                $stmt = $pdo->prepare('UPDATE `user` SET username=?, password=? WHERE id_user=?');
                $stmt->execute([$username, $passwordToStore, $_SESSION['user']['id_user']]);
                $_SESSION['user']['username'] = $username;
                $success = 'Perubahan username dan password berhasil disimpan.';
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000' ? 'Username tersebut sudah digunakan akun lain.' : 'Perubahan akun gagal disimpan. Coba lagi.';
            }
        }
    }
}

$title = 'Akun Saya';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Akun Saya</h1><p>Perbarui username atau password akun Anda.</p></div></div>
<div class="row"><div class="col-lg-8 col-xl-7"><div class="card"><div class="card-body p-4">
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="mb-4"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" value="<?=e($_POST['username'] ?? $_SESSION['user']['username'])?>" minlength="3" maxlength="50" required><div class="form-text">3–50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div></div>
<hr class="my-4"><h2 class="h6 fw-bold mb-1">Ubah password</h2><p class="text-muted small">Kosongkan password baru jika hanya ingin mengubah username.</p>
<div class="mb-3"><label class="form-label" for="current_password">Password saat ini</label><input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
<div class="row g-3"><div class="col-md-6"><label class="form-label" for="new_password">Password baru</label><input class="form-control" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password"><div class="form-text">Minimal 8 karakter.</div></div><div class="col-md-6"><label class="form-label" for="confirm_password">Ulangi password baru</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password"></div></div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Simpan perubahan</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
</form></div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
