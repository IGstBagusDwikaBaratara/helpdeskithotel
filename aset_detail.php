<?php
require 'config.php';
wajib_it();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare('SELECT * FROM aset WHERE id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) {
    $_SESSION['flash'] = 'Aset tidak ditemukan.';
    header('Location: aset.php'); exit;
}

$st = $db->prepare('SELECT t.*, u.nama AS pj_nama FROM tiket t
                    LEFT JOIN users u ON u.id = t.penanggung_id
                    WHERE t.aset_id = ? ORDER BY t.tanggal DESC, t.id DESC');
$st->execute([$id]);
$rows = $st->fetchAll();

$hit = array_fill_keys(STATUS, 0);
foreach ($rows as $t) $hit[$t['status']]++;

kepala('Aset ' . $a['kode'], 'aset', $a['nama']);
?>
  <section class="stats" aria-label="Ringkasan aset">
    <div class="stat total"><span>Total tiket</span><b><?= count($rows) ?></b><small>sepanjang waktu</small></div>
    <div class="stat baru"><span>Baru</span><b><?= $hit['Baru'] ?></b></div>
    <div class="stat proses"><span>Diproses</span><b><?= $hit['Diproses'] ?></b></div>
    <div class="stat selesai"><span>Selesai</span><b><?= $hit['Selesai'] ?></b></div>
  </section>

  <section class="panel" style="margin-bottom:14px">
    <h2>Informasi aset</h2>
    <p class="note" style="margin:0">
      <b><?= e($a['kode']) ?></b> · <?= e($a['nama']) ?> · <?= e($a['jenis']) ?>
      · Lokasi: <?= e($a['lokasi'] ?? '-') ?> · <?= $a['aktif'] ? 'Aktif' : 'Nonaktif' ?>
    </p>
    <?php if (!empty($a['keterangan'])): ?><p class="note" style="margin:6px 0 0"><?= e($a['keterangan']) ?></p><?php endif; ?>
    <p style="margin:12px 0 0">
      <a class="btn" href="aset_edit.php?id=<?= (int)$a['id'] ?>">Edit aset</a>
      <a class="btn" href="aset.php">Kembali ke daftar</a>
    </p>
  </section>

  <section class="panel" aria-labelledby="hRiwayat">
    <h2 id="hRiwayat">Riwayat tiket</h2>
    <div class="scroll">
      <table>
        <tr><th>No.</th><th>Tanggal</th><th>Judul</th><th>Kategori</th><th>Status</th><th>Ditangani oleh</th><th></th></tr>
        <?php if (!$rows): ?><tr><td colspan="7" class="muted">Belum ada tiket untuk aset ini.</td></tr><?php endif; ?>
        <?php foreach ($rows as $t): ?>
          <tr>
            <td class="code"><?= e(kode($t['id'])) ?></td>
            <td><?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></td>
            <td><?= e($t['judul']) ?></td>
            <td><?= e($t['kategori1']) ?></td>
            <td><span class="tag <?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
            <td><?= e($t['pj_nama'] ?: '-') ?></td>
            <td><a href="edit.php?id=<?= (int)$t['id'] ?>">Buka</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </section>
<?php kaki(); ?>