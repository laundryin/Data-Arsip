<?php
require_once __DIR__ . '/config/app.php';
require_kepala();
$pdo = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama_user'] ?? '');
    $jabatan = $_POST['jabatan'] ?? '';
    $password = (string)($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username harus 3–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif ($nama === '' || mb_strlen($nama) > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif (!in_array($jabatan, ['Kepala Arsiparis', 'Staff Arsiparis'], true)) {
        $error = 'Pilih jabatan yang tersedia.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO `user` (username,nama_user,jabatan,password) VALUES (?,?,?,?)');
            $stmt->execute([$username, $nama, $jabatan, $password]);
            $success = 'Pengguna berhasil ditambahkan.';
            $_POST = [];
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000' ? 'Username sudah digunakan. Pilih username yang lain.' : 'Pengguna gagal ditambahkan. Periksa koneksi database.';
        }
    }
}

$users = $pdo->query('SELECT id_user,username,nama_user,jabatan FROM `user` ORDER BY nama_user')->fetchAll();
$title = 'Manajemen Pengguna';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Manajemen Pengguna</h1><p>Tambahkan akun Kepala Arsiparis atau Staff Arsiparis.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-5"><div class="card"><div class="card-body p-4"><h2 class="section-title mb-3">Tambah pengguna</h2><form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="mb-3"><label class="form-label" for="nama_user">Nama lengkap</label><input class="form-control" id="nama_user" name="nama_user" maxlength="100" value="<?=e($_POST['nama_user'] ?? '')?>" required></div><div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" minlength="3" maxlength="50" value="<?=e($_POST['username'] ?? '')?>" required><div class="form-text">3–50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div></div><div class="mb-3"><label class="form-label" for="jabatan">Jabatan</label><select class="form-select" id="jabatan" name="jabatan" required><option value="">Pilih jabatan</option><?php foreach (['Kepala Arsiparis','Staff Arsiparis'] as $role): ?><option value="<?=e($role)?>" <?=($_POST['jabatan'] ?? '') === $role ? 'selected' : ''?>><?=e($role)?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="password">Password awal</label><input class="form-control" id="password" type="password" name="password" minlength="8" required autocomplete="new-password"><div class="form-text">Minimal 8 karakter. Pengguna dapat menggantinya di menu Akun Saya.</div></div><button class="btn btn-primary">Simpan pengguna</button></form></div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="section-title mb-3">Pengguna terdaftar</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Username</th><th>Jabatan</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?=e($user['nama_user'])?><?php if ((int)$user['id_user'] === (int)$_SESSION['user']['id_user']): ?> <span class="badge text-bg-light">Anda</span><?php endif; ?></td><td><?=e($user['username'])?></td><td><?=e($user['jabatan'])?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
