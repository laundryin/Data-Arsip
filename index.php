<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db();
$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT COALESCE(SUM(jumlah_arsip),0) FROM dokumen WHERE tanggal_upload=?');
$stmt->execute([$today]);
$incoming = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM riwayat_pemusnahan WHERE DATE(tanggal_pemusnahan)=?');
$stmt->execute([$today]);
$outgoing = (int)$stmt->fetchColumn();
$total = (int)$pdo->query('SELECT COALESCE(SUM(jumlah_arsip),0) FROM dokumen')->fetchColumn();
$folders = $pdo->query('SELECT f.id_folder,f.nama_folder,COALESCE(SUM(d.jumlah_arsip),0) jumlah_arsip,COUNT(d.id_dokumen) jumlah_dokumen FROM folder f LEFT JOIN dokumen d ON d.id_folder=f.id_folder GROUP BY f.id_folder ORDER BY f.nama_folder')->fetchAll();
$recent = $pdo->query('SELECT d.*,f.nama_folder FROM dokumen d JOIN folder f ON f.id_folder=d.id_folder ORDER BY d.tanggal_upload DESC,d.id_dokumen DESC LIMIT 6')->fetchAll();
$colors = ['#3988c1','#57a99a','#7a78c8','#e3a84b','#df7b70','#5594a5','#8ca65a'];
$chartParts = []; $legend = []; $cursor = 0; $colorIndex = 0;
foreach ($folders as $folder) {
    $color = $colors[$colorIndex++ % count($colors)];
    $value = (int)$folder['jumlah_arsip'];
    if ($total > 0 && $value > 0) {
        $next = $cursor + ($value / $total * 100);
        $chartParts[] = $color . ' ' . round($cursor, 2) . '% ' . round($next, 2) . '%';
        $cursor = $next;
    }
    $legend[] = ['name'=>$folder['nama_folder'],'value'=>$value,'color'=>$color];
}
$chartBackground = $chartParts ? 'conic-gradient(' . implode(',', $chartParts) . ')' : '#e8eef3';
$title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Selamat datang, <?=e($_SESSION['user']['nama_user'])?>!</h1><p>Berikut ringkasan pengarsipan Anda hari ini, <?=e(date('d/m/Y'))?>.</p></div></div>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-label">Total Arsip</div><div class="stat mt-2"><?=number_format($total)?></div><div class="stat-trend">tersimpan dalam seluruh folder</div></div><div class="stat-icon" style="background:#e9f3fb;color:#347db1">▤</div></div></div></div></div><div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-label">Arsip Masuk</div><div class="stat mt-2"><?=number_format($incoming)?></div><div class="stat-trend">jumlah arsip hari ini</div></div><div class="stat-icon" style="background:#e9f6f0;color:#399879">↙</div></div></div></div></div><div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-label">Arsip Keluar</div><div class="stat mt-2"><?=$outgoing?></div><div class="stat-trend">dokumen dimusnahkan hari ini</div></div><div class="stat-icon" style="background:#fff1ec;color:#d97e62">↗</div></div></div></div></div></div>
<div class="row g-3 mb-4"><div class="col-lg-7"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="section-title">Dokumen terbaru</h2><small class="text-muted">Arsip yang baru ditambahkan ke sistem</small></div><a class="action-link" href="arsip.php">Lihat semua →</a></div><div class="table-responsive"><table class="table"><thead><tr><th>No</th><th>Uraian informasi arsip</th><th>Jumlah</th><th>Tanggal upload</th><th>Kondisi</th><th>Aksi</th></tr></thead><tbody><?php foreach ($recent as $index=>$document): ?><tr><td><?=$index+1?></td><td><div class="fw-semibold"><?=e($document['informasi_arsip'])?></div><small class="text-muted"><?=e($document['nama_folder'])?></small></td><td><?=$document['jumlah_arsip']?></td><td><?=e($document['tanggal_upload'])?></td><td><span class="badge text-bg-light"><?=e($document['kondisi_arsip'])?></span></td><td><a class="action-link" target="_blank" href="download.php?id=<?=$document['id_dokumen']?>">Lihat</a></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td colspan="6"><div class="empty-state"><div class="empty-icon">▤</div>Belum ada dokumen arsip.<br><a href="folders.php">Buat folder</a> lalu tambahkan dokumen.</div></td></tr><?php endif; ?></tbody></table></div></div></div></div>
<div class="col-lg-5"><div class="card h-100"><div class="card-body"><h2 class="section-title">Arsip per folder</h2><small class="text-muted">Distribusi jumlah arsip</small><div class="chart-wrap"><div class="donut-chart" style="background:<?=$chartBackground?>"><div class="chart-center"><strong><?=number_format($total)?></strong><small>TOTAL ARSIP</small></div></div><div class="chart-legend"><?php foreach ($legend as $item): ?><div class="legend-item"><span class="legend-dot" style="background:<?=e($item['color'])?>"></span><span><?=e($item['name'])?></span><strong class="ms-auto"><?=$item['value']?></strong></div><?php endforeach; ?><?php if (!$legend): ?><span class="text-muted small">Folder belum tersedia.</span><?php endif; ?></div></div><a class="action-link" href="folders.php">Kelola folder →</a></div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
