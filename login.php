<?php
require_once __DIR__ . '/config/app.php';
if (!empty($_SESSION['user'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = db()->prepare('SELECT * FROM `user` WHERE username = ? LIMIT 1');
    $stmt->execute([trim($_POST['username'] ?? '')]); $user = $stmt->fetch();
    if ($user && password_matches((string)($_POST['password'] ?? ''), (string)$user['password'])) {
        session_regenerate_id(true); $_SESSION['user'] = ['id_user'=>$user['id_user'],'username'=>$user['username'],'nama_user'=>$user['nama_user'],'jabatan'=>$user['jabatan']]; redirect('index.php');
    }
    $error = 'Username atau password salah.';
}
$title='Login'; require __DIR__ . '/includes/header.php'; ?>
<div class="card auth-card"><div class="card-body p-4"><h1 class="h3 mb-1">Masuk ke ArsipKu</h1><p class="text-muted mb-4">Gunakan akun arsiparis Anda.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" required autofocus></div><div class="mb-3"><label class="form-label" for="loginPassword">Password</label><div class="input-group password-group"><input type="password" class="form-control" id="loginPassword" name="password" required autocomplete="current-password"><button class="btn password-toggle" type="button" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false"><svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div><button class="btn btn-primary w-100">Masuk</button></form></div></div>
<script>document.getElementById('togglePassword').addEventListener('click',function(){const input=document.getElementById('loginPassword');const visible=input.type==='password';input.type=visible?'text':'password';this.setAttribute('aria-pressed',visible?'true':'false');this.setAttribute('aria-label',visible?'Sembunyikan password':'Tampilkan password');this.title=visible?'Sembunyikan password':'Tampilkan password';});</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
