<?php
require 'config.php';

// Hanya bisa dibuka dari komputer server itu sendiri
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Halaman ini hanya bisa dibuka dari komputer server lewat http://localhost/helpdesk/reset_admin.php');
}

$pesan = ''; $galat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === '' || strlen($p) < 8) {
        $galat = 'Isi username dan kata sandi baru (minimal 8 karakter).';
    } else {
        $st = $db->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
        $st->execute([password_hash($p, PASSWORD_DEFAULT), $u]);
        if ($st->rowCount() > 0) {
            $pesan = 'Kata sandi untuk ' . $u . ' sudah diganti. Sekarang HAPUS file reset_admin.php dari folder helpdesk.';
        } else {
            $galat = 'Username tidak ditemukan.';
        }
    }
}
$daftar = $db->query("SELECT username, peran FROM users ORDER BY peran, username")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atur ulang kata sandi - Helpdesk IT</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="login.css">
</head>
<body>
<main class="loginwrap">
  <div class="loginbox">
    <h1>Atur ulang sandi</h1>
    <p class="note">Halaman darurat, hanya dari komputer server.</p>
    <?php if ($pesan): ?><p class="ok" role="status"><?= e($pesan) ?></p><?php endif; ?>
    <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label for="u">Username</label>
      <input id="u" name="username" required maxlength="40" autocomplete="off">
      <label for="p">Kata sandi baru (minimal 8 karakter)</label>
      <input id="p" name="password" type="password" required autocomplete="new-password">
      <button class="primary" type="submit">Ganti kata sandi</button>
    </form>
    <p class="note" style="margin-top:16px">Username yang ada:
      <?php foreach ($daftar as $d): ?>
        <br><?= e($d['username']) ?> (<?= $d['peran'] === 'it' ? 'Tim IT' : 'Pelapor' ?>)
      <?php endforeach; ?>
    </p>
  </div>
</main>
</body>
</html>