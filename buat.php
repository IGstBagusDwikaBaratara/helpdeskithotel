<?php
require 'config.php';
wajib_it();

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
        $idBaru = (int)$db->lastInsertId();
        notif_tiket_baru($idBaru, $d);
        $_SESSION['flash'] = 'Tiket ' . kode($idBaru) . ' terkirim.';
        header('Location: index.php'); exit;
    }
}

kepala('Buat Tiket', 'buat', 'Catat kendala baru');
banner_keamanan($db);
?>
  <section class="panel lebar" aria-labelledby="hForm">
    <h2 id="hForm">Formulir tiket baru</h2>
    <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
    <form method="post" action="buat.php">
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
      <input id="judul" name="judul" required maxlength="90" placeholder="Contoh: Wi-Fi lantai 2 terputus" value="<?= e($f['judul']) ?>">

      <label for="lokasi">Lokasi</label>
      <input id="lokasi" name="lokasi" required maxlength="60" placeholder="Contoh: Kamar 214 atau Lobby" value="<?= e($f['lokasi']) ?>">

      <label for="aset_kode">Kode aset (opsional)</label>
      <input id="aset_kode" name="aset_kode" maxlength="30" placeholder="Contoh: PC-FO-01 (lihat stiker di perangkat)" value="<?= e($f['aset_kode']) ?>" style="text-transform:uppercase">

      <label for="deskripsi">Description</label>
      <textarea id="deskripsi" name="deskripsi" required maxlength="600" placeholder="Apa yang terjadi, sejak kapan, dan pesan error yang muncul"><?= e($f['deskripsi']) ?></textarea>

      <button class="primary" type="submit">Kirim tiket</button>
    </form>
  </section>
<?php kaki(); ?>