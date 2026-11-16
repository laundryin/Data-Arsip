<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $id = (int)($_POST['id_folder'] ?? 0);
            $name = trim($_POST['nama_folder'] ?? '');
            if ($name === '') throw new RuntimeException('Nama folder wajib diisi.');
            if ($id) {
                $stmt = $pdo->prepare('UPDATE folder SET nama_folder=? WHERE id_folder=?');
                $stmt->execute([$name, $id]);
                flash('Nama folder berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO folder(nama_folder,tanggal_dibuat) VALUES(?,CURDATE())');
                $stmt->execute([$name]);
                flash('Folder berhasil dibuat.');
            }
            redirect('folders.php');
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id_folder'] ?? 0);
            $stmt = $pdo->prepare('SELECT lokasi_file FROM dokumen WHERE id_folder=?');
            $stmt->execute([$id]);
            $files = $stmt->fetchAll();
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM folder WHERE id_folder=?')->execute([$id]);
            $pdo->commit();
            foreach ($files as $file) delete_physical_file($file['lokasi_file']);
            flash('Folder beserta semua dokumen di dalamnya berhasil dihapus.');
            redirect('folders.php');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof PDOException ? 'Operasi gagal. Periksa data dan koneksi database.' : $e->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM folder WHERE id_folder=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$folders = $pdo->query('SELECT f.*,COUNT(d.id_dokumen) AS jumlah_dokumen,COALESCE(SUM(d.jumlah_arsip),0) AS jumlah_arsip FROM folder f LEFT JOIN dokumen d ON d.id_folder=f.id_folder GROUP BY f.id_folder ORDER BY f.nama_folder')->fetchAll();
$title = 'Folder';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Folder Arsip</h1><p>Folder adalah wadah untuk mengelompokkan dokumen arsip.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="arsip.php">Kembali ke Data Arsip</a><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#folderModal">+ Tambah folder</button></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<div class="card"><div class="card-body"><h2 class="section-title mb-3">Daftar folder</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama folder</th><th>Tanggal dibuat</th><th>Dokumen</th><th>Jumlah arsip</th><th>Aksi</th></tr></thead><tbody><?php foreach ($folders as $folder): ?><tr><td><?=e($folder['nama_folder'])?></td><td><?=e($folder['tanggal_dibuat'])?></td><td><?=$folder['jumlah_dokumen']?></td><td><?=$folder['jumlah_arsip']?></td><td><a class="btn btn-sm btn-outline-primary" href="arsip.php?folder=<?=$folder['id_folder']?>">Lihat dokumen</a> <a class="btn btn-sm btn-outline-secondary" href="folders.php?edit=<?=$folder['id_folder']?>">Ubah</a> <form class="d-inline" method="post" onsubmit="return confirm('Hapus folder ini beserta semua dokumen dan file di dalamnya?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_folder" value="<?=$folder['id_folder']?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?><?php if (!$folders): ?><tr><td colspan="5" class="text-center text-muted">Belum ada folder. Buat folder terlebih dahulu sebelum menambahkan dokumen.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="modal fade" id="folderModal" tabindex="-1" aria-hidden="true" data-show-on-load="<?=isset($_GET['edit'])?'1':'0'?>"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post"><div class="modal-header"><div><h2 class="modal-title fs-5"><?=$edit ? 'Ubah folder' : 'Tambah folder'?></h2><div class="small text-muted">Folder adalah wadah untuk mengelompokkan dokumen.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_folder" value="<?=e((string)($edit['id_folder'] ?? ''))?>"><label class="form-label" for="nama_folder">Nama folder</label><input class="form-control" id="nama_folder" name="nama_folder" maxlength="100" value="<?=e($edit['nama_folder'] ?? '')?>" placeholder="Contoh: Keuangan" required></div><div class="modal-footer"><a class="btn btn-outline-secondary" href="folders.php">Batal</a><button class="btn btn-primary">Simpan folder</button></div></form></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
