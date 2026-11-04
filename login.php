<?php
require 'config.php';
if (!empty($_SESSION['uid'])) { header('Location: ' . halaman_utama()); exit; }

// Belum ada akun sama sekali: buat admin pertama dulu
if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    header('Location: daftar.php'); exit;
}

$info = $_SESSION['pesan_login'] ?? '';
unset($_SESSION['pesan_login']);
$err = '';
$pilih = 'pelapor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $pilih = (($_POST['peran'] ?? '') === 'it') ? 'it' : 'pelapor';

    $st = $db->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$u]);
    $r = $st->fetch();

if ($r && !empty($r['password_hash']) && password_verify($p, $r['password_hash'])) {
        if ($pilih === 'it' && $r['peran'] !== 'it') {
            // Belum punya akses Tim IT: masuk sebagai Pelapor, lalu tawarkan minta akses
            $r['peran'] = 'pelapor';
            masuk($r, 'minta_akses.php');
        }
        $r['peran'] = $pilih;   // Tim IT boleh masuk sebagai Pelapor
        masuk($r);
    } else {
        usleep(500000);
        $err = 'Username atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Helpdesk IT</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="login.css">
</head>
<body>
<main class="loginwrap">
  <div class="loginbox">
    <h1>Helpdesk IT</h1>
    <p class="note">Pilih peran, lalu masuk.</p>
    <?php if ($info): ?><p class="ok" role="status"><?= e($info) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="err" role="alert"><?= e($err) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

      <p class="judulpilih" id="lpilih">Masuk sebagai</p>
      <div class="peran" role="radiogroup" aria-labelledby="lpilih">
        <label>
          <input type="radio" name="peran" value="pelapor" <?= $pilih === 'pelapor' ? 'checked' : '' ?>>
          <span>Pelapor</span>
        </label>
        <label>
          <input type="radio" name="peran" value="it" <?= $pilih === 'it' ? 'checked' : '' ?>>
          <span>Tim IT</span>
        </label>
      </div>

      <p class="petunjuk">Belum punya akses Tim IT? Pilih Tim IT, lalu ajukan akses setelah masuk.</p>
      <label for="u">Username</label>
      <input id="u" name="username" required maxlength="40" autocomplete="username">
      <label for="p">Kata sandi</label>
      <input id="p" name="password" type="password" required autocomplete="current-password">
      <button class="primary" type="submit">Masuk</button>
    </form>
    <p class="lupa"><a href="lupa.php">Lupa kata sandi?</a></p>
    <?php if (DAFTAR_TERBUKA): ?>
      <p class="lupa">Belum punya akun? <a href="daftar.php">Buat akun</a></p>
    <?php endif; ?>
  </div>
</main>
</body>
</html>