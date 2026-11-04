<?php
require 'config.php';
wajib_login();
if (adalah_it()) { header('Location: dashboard.php'); exit; }

$galat = '';
$f = ['nama' => $_SESSION['nama'], 'tanggal' => date('Y-m-d'), 'divisi' => DIVISI[0],
      'kategori1' => KAT1[0], 'kategori2' => KAT2[0], 'judul' => '', 'lokasi' => '', 'aset_kode' => '', 'deskripsi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $d = isian_tiket();
    $asetId = null;
    if (!is_string($d)) {
        [$asetId, $err] = aset_dari_kode($db, $d['aset_kode']);
        if ($err !== '') $d = $err;
    }
    if (is_string($d)) {
        $galat = $d;
        $f = array_merge($f, array_intersect_key($_POST, $f));
    } else {
        $db->prepare('INSERT INTO tiket (user_id, nama, tanggal, divisi, kategori1, kategori2, judul, lokasi, aset_id, deskripsi) VALUES (?,?,?,?,?,?,?,?,?,?)')
           ->execute([$_SESSION['uid'], $d['nama'], $d['tanggal'], $d['divisi'], $d['kategori1'], $d['kategori2'], $d['judul'], $d['lokasi'], $asetId, $d['deskripsi']]);
        if (function_exists('notif_tiket_baru')) notif_tiket_baru((int)$db->lastInsertId(), $d);
        $_SESSION['flash'] = 'Tiket terkirim. Tim IT akan segera menanganinya.';
        header('Location: lapor.php'); exit;
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$st = $db->prepare('SELECT t.*, u.nama AS pj_nama, a.kode AS aset_kode FROM tiket t
                    LEFT JOIN users u ON u.id = t.penanggung_id
                    LEFT JOIN aset a ON a.id = t.aset_id
                    WHERE t.user_id = ? ORDER BY t.dibuat DESC, t.id DESC LIMIT 100');
$st->execute([$_SESSION['uid']]);
$tiket = $st->fetchAll();

kepala('Tiket Saya', 'lapor', 'Pantau status laporan Anda');
?>
  <?php if ($flash): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
  <?php banner_keamanan($db); ?>

  <div class="grid">
    <section class="panel" aria-labelledby="hForm">
      <h2 id="hForm">Buat tiket baru</h2>
      <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
      <form method="post" action="lapor.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

        <label for="nama">Nama</label>
        <input id="nama" name="nama" required maxlength="60" value="<?= e($f['nama']) ?>">

        <label for="tanggal">Date</label>
        <input id="tanggal" name="tanggal" type="date" required value="<?= e($f['tanggal']) ?>">

        <label for="divisi">Division</label>
        <select id="divisi" name="divisi"><?php opsi(DIVISI, $f['divisi']); ?></select>

        <div class="row">
          <div>
            <label for="kategori1">Category 1</label>
            <select id="kategori1" name="kategori1"><?php opsi(KAT1, $f['kategori1']); ?></select>
          </div>
          <div>
            <label for="kategori2">Category 2</label>
            <select id="kategori2" name="kategori2"><?php opsi(KAT2, $f['kategori2']); ?></select>
          </div>
        </div>

        <label for="judul">Judul masalah</label>
        <input id="judul" name="judul" required maxlength="90" placeholder="Contoh: Wi-Fi terputus" value="<?= e($f['judul']) ?>">

        <label for="lokasi">Lokasi</label>
        <input id="lokasi" name="lokasi" required maxlength="60" placeholder="Contoh: Kamar 214 atau Lobby" value="<?= e($f['lokasi']) ?>">

        <label for="aset_kode">Kode aset (opsional)</label>
        <input id="aset_kode" name="aset_kode" maxlength="30" placeholder="Lihat stiker di perangkat, contoh: PC-FO-01" value="<?= e($f['aset_kode']) ?>" style="text-transform:uppercase">

        <label for="deskripsi">Description</label>
        <textarea id="deskripsi" name="deskripsi" required maxlength="600" placeholder="Apa yang terjadi, sejak kapan, dan pesan error yang muncul"><?= e($f['deskripsi']) ?></textarea>

        <button class="primary" type="submit">Kirim tiket</button>
      </form>
    </section>

    <section class="panel" aria-labelledby="hList">
      <h2 id="hList">Tiket saya</h2>
      <ul class="list">
        <?php if (!$tiket): ?>
          <li class="empty">Belum ada tiket. Isi formulir untuk membuat laporan pertama.</li>
        <?php endif; ?>
        <?php foreach ($tiket as $t): ?>
          <li class="ticket <?= e($t['status']) ?>">
            <div class="top">
              <h3><?= e($t['judul']) ?></h3>
              <span class="id"><?= e(kode($t['id'])) ?></span>
            </div>
            <p class="desc"><?= nl2br(e($t['deskripsi'])) ?></p>
            <?php if (!empty($t['tindakan'])): ?>
              <p class="aksi"><b>Action:</b> <?= nl2br(e($t['tindakan'])) ?></p>
            <?php endif; ?>
            <div class="meta">
              <span class="tag <?= e($t['status']) ?>"><?= e($t['status']) ?></span>
              <span class="tag"><?= e($t['kategori1']) ?></span>
              <span class="tag"><?= e($t['kategori2']) ?></span>
              <?php if (!empty($t['lokasi'])): ?><span class="tag lok"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= e($t['lokasi']) ?></span><?php endif; ?>
              <?php if (!empty($t['aset_kode'])): ?><span class="tag aset"><i class="fa-solid fa-box" aria-hidden="true"></i> <?= e($t['aset_kode']) ?></span><?php endif; ?>
              <span><?= e($t['divisi']) ?> · <?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></span>
            </div>
            <?php if ($t['pj_nama']): ?>
              <p class="pj">Ditangani oleh <b><?= e($t['pj_nama']) ?></b></p>
            <?php elseif ($t['status'] !== 'Selesai'): ?>
              <p class="pj">Menunggu teknisi</p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>
<?php kaki(); ?>