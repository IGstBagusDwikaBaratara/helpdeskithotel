<?php
require 'config.php';
wajib_it();

$f = filter_laporan();
$rows = tiket_laporan($db, $f);
$r = ringkas_laporan($rows);
$persen = $r['total'] ? round($r['status']['Selesai'] / $r['total'] * 100) : 0;
function fmt_tgl($d) { return date('d/m/Y', strtotime($d)); }
$qs = http_build_query(array_filter($f, 'strlen'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan Helpdesk IT <?= e(fmt_tgl($f['dari'])) ?> - <?= e(fmt_tgl($f['sampai'])) ?></title>
<link rel="stylesheet" href="cetak.css">
</head>
<body>
<div class="kontrol">
  <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
  <a href="laporan.php?<?= e($qs) ?>">Kembali</a>
</div>

<header class="kop">
  <img src="logo.png" alt="Quest Vibe Dewi Sri Bali">
  <div>
    <h1>Laporan Helpdesk IT</h1>
    <p>Periode <?= e(fmt_tgl($f['dari'])) ?> - <?= e(fmt_tgl($f['sampai'])) ?><?= $f['status'] !== '' ? ' · Status: ' . e($f['status']) : '' ?></p>
  </div>
</header>

<h2>Ringkasan</h2>
<table>
  <tr><th>Total tiket</th><th>Baru</th><th>Diproses</th><th>Selesai</th><th>Persentase selesai</th></tr>
  <tr>
    <td class="angka"><?= $r['total'] ?></td>
    <td class="angka"><?= $r['status']['Baru'] ?></td>
    <td class="angka"><?= $r['status']['Diproses'] ?></td>
    <td class="angka"><?= $r['status']['Selesai'] ?></td>
    <td class="angka"><?= $persen ?>%</td>
  </tr>
</table>

<div class="grid3">
  <?php tabel_hitung('Per Category 1', $r['kat'], 'blok'); ?>
  <?php tabel_hitung('Per divisi', $r['divisi'], 'blok'); ?>
  <?php tabel_hitung('Per penanggung jawab', $r['teknisi'], 'blok'); ?>
</div>

<h2>Rincian tiket</h2>
<table>
  <thead>
    <tr><th>No.</th><th>Tanggal</th><th>Judul</th><th>Lokasi</th><th>Divisi</th><th>Kategori</th><th>Ditangani oleh</th><th>Status</th></tr>
  </thead>
  <tbody>
    <?php if (!$rows): ?><tr><td colspan="8">Tidak ada tiket pada periode ini.</td></tr><?php endif; ?>
    <?php foreach ($rows as $t): ?>
      <tr>
        <td><?= e(kode($t['id'])) ?></td>
        <td><?= e(fmt_tgl($t['tanggal'])) ?></td>
        <td><?= e($t['judul']) ?></td>
        <td><?= e($t['lokasi'] ?? '') ?></td>
        <td><?= e($t['divisi']) ?></td>
        <td><?= e($t['kategori1']) ?></td>
        <td><?= e($t['pj_nama'] ?: '-') ?></td>
        <td><?= e($t['status']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="foot">Dicetak oleh <?= e($_SESSION['nama']) ?> pada <?= e(date('d/m/Y H:i')) ?> · Helpdesk IT Quest Vibe Dewi Sri Bali</p>
</body>
</html>