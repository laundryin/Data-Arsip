<!--  --><?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'folder_add') {
            $folderName = trim($_POST['nama_folder'] ?? '');
            if ($folderName === '') throw new RuntimeException('Nama folder wajib diisi.');
            if (mb_strlen($folderName) > 100) throw new RuntimeException('Nama folder maksimal 100 karakter.');
            $stmt = $pdo->prepare('INSERT INTO folder(nama_folder,tanggal_dibuat) VALUES(?,CURDATE())');
            $stmt->execute([$folderName]);
            $newFolderId = (int)$pdo->lastInsertId();
            flash('Folder berhasil dibuat.');
            redirect('arsip.php?folder=' . $newFolderId);
        }
        if ($action === 'document_save') {
            $id = (int)($_POST['id_dokumen'] ?? 0);
            $folderId = (int)($_POST['id_folder'] ?? 0);
            $info = trim($_POST['informasi_arsip'] ?? '');
            $quantity = filter_var($_POST['jumlah_arsip'] ?? null, FILTER_VALIDATE_INT);
            $date = $_POST['tanggal_upload'] ?? '';
            $condition = $_POST['kondisi_arsip'] ?? '';
            if (!$folderId || $info === '' || !$quantity || $quantity < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($condition, ['Baik', 'Rusak Ringan', 'Rusak Berat'], true)) {
                throw new RuntimeException('Lengkapi semua data dokumen dengan benar.');
            }
            $stmt = $pdo->prepare('SELECT id_folder FROM folder WHERE id_folder=?');
            $stmt->execute([$folderId]);
            if (!$stmt->fetch()) throw new RuntimeException('Folder tidak ditemukan. Buat folder dari menu Folder terlebih dahulu.');

            if ($id) {
                $stmt = $pdo->prepare('SELECT lokasi_file FROM dokumen WHERE id_dokumen=?');
                $stmt->execute([$id]);
                $old = $stmt->fetch();
                if (!$old) throw new RuntimeException('Dokumen tidak ditemukan.');
                $path = $old['lokasi_file'];
                if (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $path = upload_file($_FILES['file']);
                    delete_physical_file($old['lokasi_file']);
                }
                $stmt = $pdo->prepare('UPDATE dokumen SET id_folder=?,informasi_arsip=?,jumlah_arsip=?,tanggal_upload=?,kondisi_arsip=?,lokasi_file=? WHERE id_dokumen=?');
                $stmt->execute([$folderId, $info, $quantity, $date, $condition, $path, $id]);
                flash('Dokumen arsip berhasil diperbarui.');
            } else {
                $path = upload_file($_FILES['file'] ?? []);
                $stmt = $pdo->prepare('INSERT INTO dokumen(id_folder,informasi_arsip,jumlah_arsip,tanggal_upload,kondisi_arsip,lokasi_file) VALUES(?,?,?,?,?,?)');
                $stmt->execute([$folderId, $info, $quantity, $date, $condition, $path]);
                flash('Dokumen arsip berhasil ditambahkan ke folder.');
            }
            redirect('arsip.php?folder=' . $folderId);
        }
        if ($action === 'destroy') {
            $id = (int)($_POST['id_dokumen'] ?? 0);
            $stmt = $pdo->prepare('SELECT d.*,f.nama_folder FROM dokumen d JOIN folder f ON f.id_folder=d.id_folder WHERE d.id_dokumen=?');
            $stmt->execute([$id]);
            $document = $stmt->fetch();
            if (!$document) throw new RuntimeException('Dokumen tidak ditemukan.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO riwayat_pemusnahan(nama_file,tanggal_upload,tanggal_pemusnahan,nama_folder,nama_user_pemusnah) VALUES(?,?,NOW(),?,?)');
            $stmt->execute([basename($document['lokasi_file']), $document['tanggal_upload'], $document['nama_folder'], $_SESSION['user']['nama_user']]);
            $pdo->prepare('DELETE FROM dokumen WHERE id_dokumen=?')->execute([$id]);
            $pdo->commit();
            delete_physical_file($document['lokasi_file']);
            flash('Dokumen dicatat dalam riwayat pemusnahan.');
            redirect('arsip.php');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof PDOException ? 'Operasi gagal. Periksa data dan koneksi database.' : $e->getMessage();
    }
}

$folders = $pdo->query('SELECT * FROM folder ORDER BY nama_folder')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM dokumen WHERE id_dokumen=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$filter = (int)($_GET['folder'] ?? 0);
$year = (int)($_GET['tahun'] ?? 0);
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT d.*,f.nama_folder FROM dokumen d JOIN folder f ON f.id_folder=d.id_folder';
$where = [];
$args = [];
if ($filter) { $where[] = 'd.id_folder=?'; $args[] = $filter; }
if ($year >= 2000 && $year <= (int)date('Y')) { $where[] = 'YEAR(d.tanggal_upload)=?'; $args[] = $year; }
if ($search !== '') { $where[] = '(d.informasi_arsip LIKE ? OR f.nama_folder LIKE ?)'; $args[] = '%' . $search . '%'; $args[] = '%' . $search . '%'; }
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY d.tanggal_upload DESC,d.id_dokumen DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$documents = $stmt->fetchAll();
$selectedFolder = null;
foreach ($folders as $folder) if ((int)$folder['id_folder'] === $filter) $selectedFolder = $folder;
$showDocumentModal = isset($_GET['edit']) || ($error !== '' && ($_POST['action'] ?? '') === 'document_save');
$title = 'Dokumen (Arsip)';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Data Arsip</h1><p>Pilih folder untuk melihat dokumen di dalamnya, lalu kelola arsip pada tabel.</p></div><div class="d-flex gap-2"><button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#folderModal">+ Tambah folder</button><?php if ($folders): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal">+ Tambah dokumen</button><?php else: ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#folderModal">+ Buat folder dulu</button><?php endif; ?></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if (!$folders): ?><div class="alert alert-warning">Belum ada folder. Buat folder terlebih dahulu untuk mulai menambahkan dokumen arsip.</div><?php endif; ?>
<div class="d-flex flex-wrap gap-2 mb-4"><a class="folder-tile <?=$filter===0?'selected':''?>" href="arsip.php">Semua folder<small>Semua dokumen</small></a><?php foreach ($folders as $folder): ?><a class="folder-tile <?=$filter===(int)$folder['id_folder']?'selected':''?>" href="arsip.php?folder=<?=$folder['id_folder']?>"><?=e($folder['nama_folder'])?><small>Lihat dokumen</small></a><?php endforeach; ?></div>
<div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true" data-show-on-load="<?=($error !== '' && ($_POST['action'] ?? '') === 'folder_add')?'1':'0'?>"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post"><div class="modal-header"><div><h2 class="modal-title fs-5" id="folderModalLabel">Tambah folder</h2><div class="small text-muted">Folder menjadi wadah untuk mengelompokkan dokumen.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="folder_add"><label class="form-label" for="nama_folder">Nama folder</label><input class="form-control" id="nama_folder" name="nama_folder" maxlength="100" placeholder="Contoh: Keuangan" required></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan folder</button></div></form></div></div></div>
<?php if ($folders): ?><div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true" data-show-on-load="<?=$showDocumentModal?'1':'0'?>"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="post" enctype="multipart/form-data"><div class="modal-header"><div><h2 class="modal-title fs-5" id="documentModalLabel"><?=$edit ? 'Edit dokumen arsip' : 'Tambah dokumen arsip'?></h2><div class="small text-muted">Isi informasi utama dan pilih file arsip.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="document_save"><input type="hidden" name="id_dokumen" value="<?=e((string)($edit['id_dokumen'] ?? ''))?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">Folder penyimpanan</label><select class="form-select" name="id_folder" required><?php foreach ($folders as $folder): ?><option value="<?=$folder['id_folder']?>" <?=($edit['id_folder'] ?? $filter) == $folder['id_folder'] ? 'selected' : ''?>><?=e($folder['nama_folder'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Jumlah arsip</label><input type="number" min="1" class="form-control" name="jumlah_arsip" value="<?=e((string)($edit['jumlah_arsip'] ?? 1))?>" required></div><div class="col-md-6"><label class="form-label">Tanggal upload</label><input type="date" class="form-control" name="tanggal_upload" value="<?=e($edit['tanggal_upload'] ?? date('Y-m-d'))?>" required></div><div class="col-md-6"><label class="form-label">Kondisi arsip</label><select class="form-select" name="kondisi_arsip"><?php foreach (['Baik', 'Rusak Ringan', 'Rusak Berat'] as $condition): ?><option <?=($edit['kondisi_arsip'] ?? 'Baik') === $condition ? 'selected' : ''?>><?=e($condition)?></option><?php endforeach; ?></select></div><div class="col-12"><label class="form-label">Uraian informasi arsip</label><textarea class="form-control" name="informasi_arsip" rows="3" required><?=e($edit['informasi_arsip'] ?? '')?></textarea></div><div class="col-12"><label class="form-label">File arsip <?= $edit ? '(kosongkan jika tidak mengganti file)' : '' ?></label><input type="file" class="form-control" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" <?=$edit ? '' : 'required'?>></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan dokumen</button></div></form></div></div></div><?php endif; ?>
<div class="card"><div class="card-body"><div class="document-toolbar mb-3"><div><h2 class="section-title mb-1">Dokumen</h2><?php if ($selectedFolder): ?><span class="text-muted small">Folder terpilih: <?=e($selectedFolder['nama_folder'])?></span><?php endif; ?></div><form class="document-filter" method="get"><input type="hidden" name="folder" value="<?=$filter?>"><select class="form-select" name="tahun" aria-label="Filter tahun"><option value="0">Semua tahun</option><?php for ($y=(int)date('Y'); $y>=2000; $y--): ?><option value="<?=$y?>" <?=$year===$y?'selected':''?>><?=$y?></option><?php endfor; ?></select><div class="search-control"><input class="form-control" type="search" name="q" value="<?=e($search)?>" placeholder="Cari dokumen" aria-label="Cari dokumen"><button class="btn btn-outline-primary">Cari</button></div></form></div><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>No</th><th>Uraian informasi arsip</th><th>Jumlah arsip</th><th>Tanggal upload</th><th>Kondisi</th><th>Folder</th><th class="no-print">Action</th></tr></thead><tbody><?php foreach ($documents as $index=>$document): ?><tr><td><?=$index+1?></td><td><?=e($document['informasi_arsip'])?></td><td><?=$document['jumlah_arsip']?></td><td><?=e($document['tanggal_upload'])?></td><td><?=e($document['kondisi_arsip'])?></td><td><?=e($document['nama_folder'])?></td><td class="no-print"><a class="action-link" target="_blank" href="download.php?id=<?=$document['id_dokumen']?>">Lihat</a> <a class="action-link" href="arsip.php?edit=<?=$document['id_dokumen']?>#form-dokumen">Edit</a> <form class="d-inline" method="post" onsubmit="return confirm('Musnahkan dokumen ini dan hapus file fisiknya?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="destroy"><input type="hidden" name="id_dokumen" value="<?=$document['id_dokumen']?>"><button class="action-link btn-link border-0 bg-transparent text-danger">Musnahkan</button></form></td></tr><?php endforeach; ?><?php if (!$documents): ?><tr><td colspan="7"><div class="empty-state">Tidak ada dokumen yang cocok dengan pilihan folder dan filter.</div></td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
