<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db();
$error = '';
$items = $pdo->query('SELECT * FROM sop ORDER BY tanggal_update DESC')->fetchAll();
$selectedSopId = (int)($_GET['sop'] ?? 0);
$selectedSop = null;
foreach ($items as $item) {
    if ((int)$item['id_sop'] === $selectedSopId) { $selectedSop = $item; break; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_kepala(); csrf_check();
    try {
        $id = (int)($_POST['id_sop'] ?? 0); $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $titleInput = trim($_POST['judul_sop'] ?? ''); $info = trim($_POST['informasi_sop'] ?? '');
            if ($titleInput === '' || $info === '') throw new RuntimeException('Judul dan informasi SOP wajib diisi.');
            if ($id) { $stmt=$pdo->prepare('UPDATE sop SET judul_sop=?,informasi_sop=?,tanggal_update=NOW() WHERE id_sop=?'); $stmt->execute([$titleInput,$info,$id]); }
            else { $stmt=$pdo->prepare('INSERT INTO sop(judul_sop,informasi_sop,tanggal_update) VALUES(?,?,NOW())'); $stmt->execute([$titleInput,$info]); }
            flash('SOP berhasil disimpan.'); redirect('sop.php');
        }
        if ($action === 'delete') { $pdo->prepare('DELETE FROM sop WHERE id_sop=?')->execute([$id]); flash('SOP berhasil dihapus.'); redirect('sop.php'); }
    } catch (Throwable $e) { $error=$e instanceof PDOException ? 'Operasi database gagal.' : $e->getMessage(); }
}
$edit = null;
if (isset($_GET['edit']) && is_kepala()) { $stmt=$pdo->prepare('SELECT * FROM sop WHERE id_sop=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch() ?: null; }
$title = 'SOP Daur Hidup'; require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>SOP Daur Hidup</h1><p>Judul pada kartu diambil dari data SOP. Pilih judul untuk membaca prosedurnya.</p></div><?php if (is_kepala()): ?><a class="btn btn-primary" href="sop.php?edit=0#form-sop">+ Tambah SOP</a><?php endif; ?></div>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<div class="row g-3 mb-4"><?php foreach ($items as $index=>$item): ?><div class="col-sm-6 col-xl-3"><a class="stage-tile <?=$selectedSopId===(int)$item['id_sop']?'border-primary bg-light':''?>" href="sop.php?sop=<?=(int)$item['id_sop']?>#detail-sop"><span class="stage-number"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></span><span><?=e($item['judul_sop'])?></span></a></div><?php endforeach; ?><?php if (!$items): ?><div class="col-12"><div class="empty-state card">Belum ada judul SOP.<?php if (is_kepala()): ?> <a href="sop.php?edit=0#form-sop">Tambahkan SOP pertama</a> agar muncul sebagai kartu di halaman ini.<?php endif; ?></div></div><?php endif; ?></div>
<?php if ($selectedSop): ?><article class="card mb-4" id="detail-sop"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="stat-label mb-2">PROSEDUR SOP</div><h2 class="section-title mb-2"><?=e($selectedSop['judul_sop'])?></h2><div class="small text-muted">Diperbarui <?=e($selectedSop['tanggal_update'])?></div></div><div class="d-flex gap-2 align-self-start"><?php if (is_kepala()): ?><a class="btn btn-sm btn-outline-primary" href="sop.php?edit=<?=$selectedSop['id_sop']?>#form-sop">Edit</a><form method="post" onsubmit="return confirm('Hapus SOP ini?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_sop" value="<?=$selectedSop['id_sop']?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form><?php endif; ?><a class="btn btn-sm btn-outline-secondary" href="sop.php">Tutup</a></div></div><hr><div class="content-text"><?=e($selectedSop['informasi_sop'])?></div></div></article><?php elseif ($items): ?><div class="alert alert-primary">Pilih kartu judul SOP untuk melihat prosedurnya.</div><?php endif; ?>
<?php if (is_kepala() && isset($_GET['edit'])): ?><div class="card mb-4" id="form-sop"><div class="card-body"><h2 class="section-title mb-3"><?=$edit ? 'Edit SOP' : 'Tambah SOP'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_sop" value="<?=e((string)($edit['id_sop'] ?? ''))?>"><div class="mb-3"><label class="form-label">Judul SOP</label><input class="form-control" maxlength="150" name="judul_sop" value="<?=e($edit['judul_sop'] ?? '')?>" required></div><div class="mb-3"><label class="form-label">Informasi / prosedur</label><textarea class="form-control" name="informasi_sop" rows="8" required><?=e($edit['informasi_sop'] ?? '')?></textarea></div><button class="btn btn-primary">Simpan</button> <a class="btn btn-outline-secondary" href="sop.php">Batal</a></form></div></div><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
