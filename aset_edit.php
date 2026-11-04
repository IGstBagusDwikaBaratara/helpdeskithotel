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

$galat = '';
$v = array_intersect_key($a, array_flip(['kode', 'nama', 'jenis', 'lokasi', 'keterangan']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $d = isian_aset();
    if (is_string($d)) {
        $galat = $d;
        $v = array_merge($v, array_intersect_key($_POST, $v));
    } else {
        try {
            $db->prepare('UPDATE aset SET kode=?, nama=?, jenis=?, lokasi=?, keterangan=? WHERE id=?')
               ->execute([$d['kode'], $d['nama'], $d['jenis'], $d['lokasi'] ?: null, $d['keterangan'] ?: null, $id]);
            $_SESSION['flash'] = 'Aset ' . $d['kode'] . ' diperbarui.';
            header('Location: aset.php'); exit;
        } catch (PDOException $x) {
            $galat = ($x->getCode() == 23000) ? 'Kode aset sudah dipakai.' : 'Gagal menyimpan.';
            $v = $d;
        }
    }
}

kepala('Edit Aset', 'aset', $a['kode'] . ' · ' . $a['nama']);
?>
  <section class="panel lebar" aria-labelledby="hEdit">
    <h2 id="hEdit">Edit aset <?= e($a['kode']) ?></h2>
    <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
    <p class="note">Kalau kode diubah, ganti juga stiker di perangkatnya. Tiket lama tetap terkait ke aset ini.</p>
    <?php form_aset($v, 'simpan', 'Simpan perubahan', 'aset_edit.php?id=' . $id); ?>
    <a class="batal" href="aset.php">Batal</a>
  </section>
<?php kaki(); ?>