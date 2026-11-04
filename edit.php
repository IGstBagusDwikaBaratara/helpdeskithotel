<?php
require 'config.php';
wajib_it();

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare('SELECT * FROM tiket WHERE id = ?');
$st->execute([$id]);
$tiket = $st->fetch();
if (!$tiket) {
    $_SESSION['flash'] = 'Tiket tidak ditemukan.';
    header('Location: index.php'); exit;
}

$daftarIT = daftar_it($db);
$itNama = array_column($daftarIT, 'nama', 'id');

$galat = '';
$f = array_intersect_key($tiket, array_flip(['nama', 'tanggal', 'divisi', 'kategori1', 'kategori2', 'judul', 'lokasi', 'deskripsi', 'tindakan']));
$f['tindakan'] = (string)$f['tindakan'];
$f['lokasi'] = (string)$f['lokasi'];
$pjPilih = (int)$tiket['penanggung_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $pjPilih = (int)($_POST['penanggung_id'] ?? 0);
    if (!isset($itNama[$pjPilih])) $pjPilih = 0;   // hanya akun Tim IT yang boleh jadi penanggung jawab

    $d = isian_tiket(true);
    if (is_string($d)) {
        $galat = $d;
        $f = array_merge($f, array_intersect_key($_POST, $f));
    } else {
        $pjBaru = $pjPilih ?: null;
        if ($pjBaru === null) {
            $ambil = null;
        } elseif ((int)$tiket['penanggung_id'] === $pjBaru && $tiket['diambil_pada']) {
            $ambil = $tiket['diambil_pada'];            // penanggung jawab sama: waktu tetap
        } else {
            $ambil = date('Y-m-d H:i:s');               // dipindahkan: waktu dihitung ulang
        }
        $db->prepare('UPDATE tiket SET nama=?, tanggal=?, divisi=?, kategori1=?, kategori2=?, judul=?, lokasi=?, deskripsi=?, tindakan=?, penanggung_id=?, diambil_pada=? WHERE id=?')
           ->execute([$d['nama'], $d['tanggal'], $d['divisi'], $d['kategori1'], $d['kategori2'], $d['judul'], $d['lokasi'], $d['deskripsi'], $d['tindakan'], $pjBaru, $ambil, $id]);
        $_SESSION['flash'] = 'Tiket ' . kode($id) . ' diperbarui.';
        header('Location: index.php'); exit;
    }
}

kepala('Edit Tiket ' . kode($id), 'semua', 'Perbarui isi tiket, penanggung jawab, dan tindakan');
?>
  <section class="panel lebar" aria-labelledby="hForm">
    <h2 id="hForm">Edit tiket <?= e(kode($id)) ?></h2>
    <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
    <form method="post" action="edit.php?id=<?= $id ?>">
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
      <input id="judul" name="judul" required maxlength="90" value="<?= e($f['judul']) ?>">

      <label for="lokasi">Lokasi</label>
      <input id="lokasi" name="lokasi" maxlength="60" placeholder="Contoh: Kamar 214 atau Lobby" value="<?= e($f['lokasi']) ?>">

      <label for="deskripsi">Description</label>
      <textarea id="deskripsi" name="deskripsi" required maxlength="600"><?= e($f['deskripsi']) ?></textarea>

      <label for="penanggung_id">Ditangani oleh</label>
      <select id="penanggung_id" name="penanggung_id">
        <option value="0">(Belum ada)</option>
        <?php foreach ($daftarIT as $tek): ?>
          <option value="<?= (int)$tek['id'] ?>"<?= $pjPilih === (int)$tek['id'] ? ' selected' : '' ?>><?= e($tek['nama']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="tindakan">Action</label>
      <textarea id="tindakan" name="tindakan" maxlength="600" placeholder="Tindakan yang sudah dilakukan"><?= e($f['tindakan']) ?></textarea>

      <button class="primary" type="submit">Simpan perubahan</button>
      <a class="batal" href="index.php">Batal</a>
    </form>
  </section>
<?php kaki(); ?>