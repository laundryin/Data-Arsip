<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $id = (int)($_POST['id_dokumen'] ?? 0);
        $stmt = $pdo->prepare('SELECT d.*,f.nama_folder FROM dokumen d JOIN folder f ON f.id_folder=d.id_folder WHERE d.id_dokumen=? AND d.tanggal_upload<=DATE_SUB(CURDATE(),INTERVAL 5 YEAR)');
        $stmt->execute([$id]);
        $document = $stmt->fetch();
        if (!$document) throw new RuntimeException('Dokumen tidak ditemukan atau belum mencapai retensi lima tahun.');

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO riwayat_pemusnahan(nama_file,tanggal_upload,tanggal_pemusnahan,nama_folder,nama_user_pemusnah) VALUES(?,?,NOW(),?,?)');
        $stmt->execute([basename($document['lokasi_file']), $document['tanggal_upload'], $document['nama_folder'], $_SESSION['user']['nama_user']]);
        $pdo->prepare('DELETE FROM dokumen WHERE id_dokumen=?')->execute([$id]);
        $pdo->commit();
        delete_physical_file($document['lokasi_file']);
        flash('Pemusnahan arsip berhasil dicatat.');
        redirect('usul_musnah.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof PDOException ? 'Pemusnahan gagal diproses. Periksa database.' : $e->getMessage();
    }
}

$documents = $pdo->query('SELECT d.*,f.nama_folder,DATEDIFF(CURDATE(),d.tanggal_upload) umur_hari FROM dokumen d JOIN folder f ON f.id_folder=d.id_folder WHERE d.tanggal_upload<=DATE_SUB(CURDATE(),INTERVAL 5 YEAR) ORDER BY d.tanggal_upload ASC')->fetchAll();
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM riwayat_pemusnahan';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE nama_file LIKE ? OR nama_folder LIKE ? OR nama_user_pemusnah LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY tanggal_pemusnahan DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();
$title = 'Data Arsip Musnah';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>DATA ARSIP USUL MUSNAH</h1><p>Monitoring dokumen yang mencapai batas retensi 5 tahun sejak tanggal unggah &amp; rekam jejak audit pemusnahan resmi.</p></div><button class="btn btn-outline-dark no-print" onclick="window.print()">▣ Cetak Log Pemusnahan</button></div>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<section class="card mb-4"><div class="retention-heading"><div class="retention-icon">◴</div><div><h2 class="section-title">Daftar Usul Musnah (Masa Retensi ≥ 5 Tahun)</h2><div class="text-muted">Arsip yang telah mencapai masa retensi dan diusulkan untuk dimusnahkan secara berkala.</div></div><span class="retention-count"><?=count($documents)?> Berkas Usul Musnah</span></div><div class="table-responsive"><table class="table retention-table"><thead><tr><th>No</th><th>Uraian Informasi Arsip</th><th>Kategori Folder</th><th>Tanggal Upload</th><th>Usia Simpan</th><th>Kondisi</th><th>Nama File</th><th class="no-print">Action</th></tr></thead><tbody><?php foreach ($documents as $index=>$document): $age=(new DateTimeImmutable($document['tanggal_upload']))->diff(new DateTimeImmutable('today')); ?><tr><td><?=$index+1?></td><td><strong><?=e($document['informasi_arsip'])?></strong><div><span class="retention-badge">! Usul Musnah</span></div></td><td><span class="folder-badge">▰ <?=e($document['nama_folder'])?></span></td><td><?=e(date('d/m/Y',strtotime($document['tanggal_upload'])))?></td><td><span class="retention-age"><?=$age->y?> tahun <?=$age->m?> bulan</span></td><td><span class="condition-badge"><?=e($document['kondisi_arsip'])?></span></td><td><span class="physical-filename" title="<?=e(basename($document['lokasi_file']))?>"><?=e(basename($document['lokasi_file']))?></span></td><td class="no-print"><button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#destroyModal" data-id="<?=$document['id_dokumen']?>" data-info="<?=e($document['informasi_arsip'])?>" data-folder="<?=e($document['nama_folder'])?>" data-date="<?=e(date('d F Y',strtotime($document['tanggal_upload'])))?>" data-file="<?=e(basename($document['lokasi_file']))?>">♨ Musnahkan</button></td></tr><?php endforeach; ?><?php if (!$documents): ?><tr><td colspan="8"><div class="empty-state">Tidak ada arsip yang mencapai retensi lima tahun.</div></td></tr><?php endif; ?></tbody></table></div></section>
<section class="card"><div class="history-heading"><div class="d-flex align-items-center gap-2"><span class="history-icon">◷</span><div><h2 class="section-title">Riwayat Arsip Keluar / Dimusnahkan</h2><span class="history-count"><?=count($history)?> Data</span></div></div><form method="get" class="history-search no-print"><input type="search" name="q" value="<?=e($search)?>" placeholder="Cari arsip musnah..." aria-label="Cari arsip musnah"><button aria-label="Cari">⌕</button></form></div><div class="table-responsive"><table class="table history-table"><thead><tr><th>No</th><th>Nama File Fisik Dimusnahkan</th><th>Folder Asal</th><th>Tanggal Upload Awal</th><th>Waktu Eksekusi Pemusnahan</th><th>Petugas / Pemusnah</th><th>Status Berkas</th></tr></thead><tbody><?php foreach ($history as $index=>$row): $fileName=basename($row['nama_file']); $fileExists=is_file(__DIR__.'/uploads/'.$fileName); ?><tr><td><?=$index+1?></td><td><span class="deleted-file">▣ <?=e($fileName)?></span></td><td><span class="folder-badge">▰ <?=e($row['nama_folder'])?></span></td><td><?=e(date('d/m/Y',strtotime($row['tanggal_upload'])))?></td><td><?=e(date('d F Y H:i',strtotime($row['tanggal_pemusnahan'])))?> WIB</td><td><span class="operator-name">♟ <?=e($row['nama_user_pemusnah'])?></span></td><td><span class="status-badge"><?=$fileExists?'File masih tersedia':'✓ Musnah (Unlinked)'?></span></td></tr><?php endforeach; ?><?php if (!$history): ?><tr><td colspan="7"><div class="empty-state"><?=$search?'Tidak ada hasil yang cocok.':'Belum ada riwayat pemusnahan.'?></div></td></tr><?php endif; ?></tbody></table></div></section>
<div class="modal fade" id="destroyModal" tabindex="-1" aria-labelledby="destroyTitle" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content destroy-modal"><div class="modal-header destroy-header"><h2 class="modal-title fs-4" id="destroyTitle">⚠ Pemusnahan Resmi Arsip Usul Musnah</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button></div><form method="post"><div class="modal-body p-4"><p class="text-secondary fs-6">Anda akan melakukan pemusnahan resmi terhadap arsip berikut yang telah melampaui retensi:</p><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id_dokumen" id="destroyDocumentId"><div class="destroy-detail"><div><strong>Uraian Arsip:</strong> <span id="destroyInfo"></span></div><div><strong>Folder:</strong> <span class="folder-badge" id="destroyFolder"></span></div><div><strong>Tgl Upload:</strong> <span id="destroyDate"></span></div><div><strong>File Fisik:</strong></div><code id="destroyFile"></code></div><div class="destroy-notice"><strong>◈ Prosedur Eksekusi Sistem:</strong><ol class="mb-0 mt-2"><li>Data pemusnahan dicatat di tabel <code>riwayat_pemusnahan</code> atas nama <strong><?=e($_SESSION['user']['nama_user'])?></strong>.</li><li>File fisik di server dihapus menggunakan <code>unlink()</code>.</li><li>Data dokumen dihapus dari tabel <code>dokumen</code>.</li></ol></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batalkan</button><button class="btn btn-danger">♨ Konfirmasi &amp; Musnahkan</button></div></form></div></div></div>
<script>document.getElementById('destroyModal').addEventListener('show.bs.modal',function(event){const b=event.relatedTarget;document.getElementById('destroyDocumentId').value=b.dataset.id;document.getElementById('destroyInfo').textContent=b.dataset.info;document.getElementById('destroyFolder').textContent=b.dataset.folder;document.getElementById('destroyDate').textContent=b.dataset.date;document.getElementById('destroyFile').textContent=b.dataset.file;});</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
