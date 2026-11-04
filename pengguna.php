<?php
require 'config.php';
wajib_it();
$pesan = ''; $galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';
    $p = $_POST['password'] ?? '';

    if ($aksi === 'tambah') {
        $u = trim($_POST['username'] ?? '');
        $n = trim($_POST['nama'] ?? '');
        $peran = (($_POST['peran'] ?? '') === 'it') ? 'it' : 'pelapor';
        if ($u === '' || $n === '' || strlen($p) < 8) {
            $galat = 'Isi semua kolom. Kata sandi minimal 8 karakter.';
        } else {
            try {
                $db->prepare('INSERT INTO users (username, nama, password_hash, peran) VALUES (?,?,?,?)')
                   ->execute([$u, $n, password_hash($p, PASSWORD_DEFAULT), $peran]);
                $pesan = 'Akun ' . $u . ' dibuat.';
            } catch (PDOException $x) {
                $galat = ($x->getCode() == 23000) ? 'Username sudah dipakai.' : 'Gagal membuat akun.';
            }
        }
    } elseif ($aksi === 'reset') {
        if (strlen($p) < 8) {
            $galat = 'Kata sandi minimal 8 karakter.';
        } else {
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
               ->execute([password_hash($p, PASSWORD_DEFAULT), (int)($_POST['id'] ?? 0)]);
            $pesan = 'Kata sandi diganti.';
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['uid']) {
            $galat = 'Anda tidak bisa menghapus akun yang sedang dipakai.';
        } else {
            try {
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                $pesan = 'Akun dihapus. Tiket miliknya tetap tersimpan.';
            } catch (PDOException $x) {
                $galat = 'Akun belum bisa dihapus. Pastikan perubahan database sudah dijalankan.';
            }
        }
    } elseif ($aksi === 'akses') {
        $id = (int)($_POST['id'] ?? 0);
        if (($_POST['keputusan'] ?? '') === 'setuju') {
            $db->prepare("UPDATE users SET peran = 'it', akses_it = 'tidak', diminta_pada = NULL WHERE id = ? AND akses_it = 'diminta'")->execute([$id]);
            $pesan = 'Akses Tim IT disetujui.';
        } elseif (($_POST['keputusan'] ?? '') === 'tolak') {
            $db->prepare("UPDATE users SET akses_it = 'ditolak' WHERE id = ? AND akses_it = 'diminta'")->execute([$id]);
            $pesan = 'Permintaan ditolak.';
        }
    }
}
$users = $db->query('SELECT id, username, nama, peran FROM users ORDER BY nama')->fetchAll();
$minta = $db->query("SELECT id, nama, username, diminta_pada FROM users WHERE akses_it = 'diminta' ORDER BY diminta_pada")->fetchAll();
?>

<?php kepala('Kelola Pengguna', 'pengguna', 'Tambah akun, ganti kata sandi, hapus akun'); ?>

  <?php if ($pesan): ?><p class="flash" role="status"><?= e($pesan) ?></p><?php endif; ?>
  <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
  <?php if ($minta): ?>
  <section class="panel" style="margin-bottom:14px" aria-labelledby="hMinta">
    <h2 id="hMinta">Permintaan akses Tim IT (<?= count($minta) ?>)</h2>
    <div class="scroll">
      <table>
        <tr><th>Nama</th><th>Username</th><th>Diminta</th><th>Keputusan</th></tr>
        <?php foreach ($minta as $m): ?>
          <tr>
            <td><?= e($m['nama']) ?></td>
            <td><?= e($m['username']) ?></td>
            <td><?= $m['diminta_pada'] ? e(date('d/m/Y H:i', strtotime($m['diminta_pada']))) : '-' ?></td>
            <td>
              <div class="actions">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="aksi" value="akses">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="keputusan" value="setuju">
                  <button class="del edit" type="submit">Setujui</button>
                </form>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="aksi" value="akses">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="keputusan" value="tolak">
                  <button class="del" type="submit">Tolak</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </section>
  <?php endif; ?>
  
  <div class="grid">
    <section class="panel">
      <h2>Tambah akun</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="aksi" value="tambah">
        <label for="n">Nama lengkap</label>
        <input id="n" name="nama" required maxlength="60">
        <label for="u">Username</label>
        <input id="u" name="username" required maxlength="40" autocomplete="off">
        <label for="p">Kata sandi (minimal 8 karakter)</label>
        <input id="p" name="password" type="password" required autocomplete="new-password">
        <label for="r">Peran</label>
        <select id="r" name="peran">
          <option value="pelapor">Pelapor (kirim tiket)</option>
          <option value="it">Tim IT (kelola tiket)</option>
        </select>
        <button class="primary" type="submit">Buat akun</button>
      </form>
    </section>

    <section class="panel">
      <h2>Daftar akun</h2>
      <div style="overflow-x:auto">
        <table>
          <tr><th>Nama</th><th>Username</th><th>Peran</th><th>Ganti kata sandi</th><th>Hapus</th></tr>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= e($u['nama']) ?></td>
              <td><?= e($u['username']) ?></td>
              <td><?= $u['peran'] === 'it' ? 'Tim IT' : 'Pelapor' ?></td>
              <td>
                <form method="post" style="display:flex;gap:6px">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="aksi" value="reset">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <input type="password" name="password" placeholder="Sandi baru" required autocomplete="new-password" aria-label="Sandi baru untuk <?= e($u['username']) ?>">
                  <button class="del edit" type="submit">Ganti</button>
                </form>
              </td>
              <td>
  <?php if ((int)$u['id'] !== (int)$_SESSION['uid']): ?>
    <form method="post" onsubmit="return confirm('Hapus akun ini?')">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="aksi" value="hapus">
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
      <button class="hapus" type="submit" title="Hapus akun" aria-label="Hapus akun <?= e($u['username']) ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </form>
  <?php else: ?>
    <span class="muted">Akun Anda</span>
  <?php endif; ?>
</td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </section>
  </div>
<?php kaki(); ?>