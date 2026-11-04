<?php
require 'config.php';
wajib_login();

$st = $db->prepare('SELECT nama, peran, akses_it, diminta_pada FROM users WHERE id = ?');
$st->execute([$_SESSION['uid']]);
$u = $st->fetch();
if (!$u) { header('Location: logout.php'); exit; }

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    if (($_POST['aksi'] ?? '') === 'minta' && $u['peran'] !== 'it' && $u['akses_it'] !== 'diminta') {
        $db->prepare("UPDATE users SET akses_it = 'diminta', diminta_pada = NOW() WHERE id = ?")->execute([$_SESSION['uid']]);
        $u['akses_it'] = 'diminta';
        $u['diminta_pada'] = date('Y-m-d H:i:s');
        $pesan = 'Permintaan terkirim. Tim IT akan meninjaunya.';
    }
}
$punyaIT = ($u['peran'] === 'it');
$kapan = $u['diminta_pada'] ? date('d/m/Y H:i', strtotime($u['diminta_pada'])) : '';

kepala('Akses Tim IT', 'akses', 'Ajukan akses untuk mengelola tiket');
banner_keamanan($db);
?>
  <section class="panel lebar" aria-labelledby="hAkses">
    <h2 id="hAkses">Peran akun Anda</h2>
    <?php if ($pesan): ?><p class="flash" role="status"><?= e($pesan) ?></p><?php endif; ?>

    <?php if ($punyaIT): ?>
      <p class="note">Akun <b><?= e($u['nama']) ?></b> sudah punya akses Tim IT. Keluar, lalu masuk lagi dan pilih <b>Tim IT</b>.</p>
      <a class="btn utama" href="logout.php">Keluar</a>

    <?php elseif ($u['akses_it'] === 'diminta'): ?>
      <p class="note">Permintaan akses Tim IT dikirim <?= e($kapan) ?>. Menunggu persetujuan Tim IT.</p>
      <button class="btn" type="button" disabled>Menunggu persetujuan</button>
      <a class="btn" href="lapor.php">Lanjut sebagai Pelapor</a>

    <?php else: ?>
      <p class="note">
        Saat ini Anda masuk sebagai <b>Pelapor</b>.
        <?= $u['akses_it'] === 'ditolak' ? 'Permintaan sebelumnya ditolak, Anda bisa mengajukan lagi.' : 'Akun ini belum punya akses Tim IT.' ?>
      </p>
      <form method="post" style="display:inline">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="aksi" value="minta">
        <button class="btn utama" type="submit">Minta akses Tim IT</button>
      </form>
      <a class="btn" href="lapor.php">Lanjut sebagai Pelapor</a>
    <?php endif; ?>
  </section>
<?php kaki(); ?>