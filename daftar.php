<?php
require 'config.php';
if (!empty($_SESSION['uid'])) { header('Location: ' . halaman_utama()); exit; }

$kosong = ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0);
if (!$kosong && !DAFTAR_TERBUKA) { header('Location: login.php'); exit; }

$err = '';
$v = ['nama' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $v['nama'] = trim($_POST['nama'] ?? '');
    $v['username'] = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';

    if ($v['nama'] === '' || mb_strlen($v['nama']) > 60) {
        $err = 'Isi nama lengkap (maksimal 60 karakter).';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $v['username'])) {
        $err = 'Username 3 sampai 40 karakter: huruf, angka, titik, garis bawah, atau strip.';
    } elseif (strlen($p) < 8) {
        $err = 'Kata sandi minimal 8 karakter.';
    } elseif ($p !== $p2) {
        $err = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $peran = $kosong ? 'it' : 'pelapor';
        try {
            $db->prepare('INSERT INTO users (username, nama, password_hash, peran) VALUES (?,?,?,?)')
               ->execute([$v['username'], $v['nama'], password_hash($p, PASSWORD_DEFAULT), $peran]);
            masuk(['id' => $db->lastInsertId(), 'nama' => $v['nama'], 'peran' => $peran]);
        } catch (PDOException $x) {
            $err = ($x->getCode() == 23000) ? 'Username sudah dipakai.' : 'Gagal membuat akun. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buat akun - Helpdesk IT</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="login.css">
</head>
<body>
<main class="loginwrap">
  <div class="loginbox">
    <h1><?= $kosong ? 'Buat akun admin' : 'Buat akun' ?></h1>
    <p class="note"><?= $kosong
        ? 'Belum ada akun. Akun pertama ini menjadi admin (Tim IT).'
        : 'Akun baru bisa mengirim tiket dan memantau statusnya.' ?></p>
    <?php if ($err): ?><p class="err" role="alert"><?= e($err) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label for="n">Nama lengkap</label>
      <input id="n" name="nama" required maxlength="60" autocomplete="name" value="<?= e($v['nama']) ?>">
      <label for="u">Username</label>
      <input id="u" name="username" required maxlength="40" autocomplete="username" value="<?= e($v['username']) ?>">
      <label for="p">Kata sandi (minimal 8 karakter)</label>
      <input id="p" name="password" type="password" required autocomplete="new-password">
      <label for="p2">Ulangi kata sandi</label>
      <input id="p2" name="password2" type="password" required autocomplete="new-password">
      <button class="primary" type="submit"><?= $kosong ? 'Buat akun admin' : 'Buat akun dan masuk' ?></button>
    </form>
    <?php if (!$kosong): ?>
      <p class="lupa">Sudah punya akun? <a href="login.php">Masuk</a></p>
    <?php endif; ?>
  </div>
</main>
</body>
</html>