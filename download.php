<?php
require_once __DIR__ . '/config/app.php'; require_login();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id){http_response_code(404);exit('File tidak ditemukan.');}
$q=db()->prepare('SELECT lokasi_file FROM dokumen WHERE id_dokumen=?');$q->execute([$id]);$row=$q->fetch();
if(!$row){http_response_code(404);exit('File tidak ditemukan.');}
$base=realpath(__DIR__.'/uploads');$file=realpath(__DIR__.'/'.$row['lokasi_file']);
if(!$base||!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit('File tidak tersedia.');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
header('Content-Type: '.$mime);header('Content-Length: '.filesize($file));header('Content-Disposition: inline; filename="'.basename($file).'"');header('X-Content-Type-Options: nosniff');readfile($file);
