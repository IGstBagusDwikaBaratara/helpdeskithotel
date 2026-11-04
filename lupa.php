<?php
require 'config.php';
if (!empty($_SESSION['uid'])) { header('Location: ' . halaman_utama()); exit; }

const MAKS_SALAH = 5;
const KUNCI_MENIT = 15;

function terkunci(array $u) {
    return !empty($u['terkunci_sampai']) && strtotime($u['terkunci_sampai']) > time();
}
function ambil($db, $id) {
    $st = $db->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

$galat = '';
$aksi = $_POST['aksi'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();

    if ($aksi === 'cari') {
        unset($_SESSION['reset_uid']);
        $st = $db->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([trim($_POST['username'] ?? '')]);
        $r = $st->fetch();
        if (!$r || empty($r['pertanyaan']) || empty($r['jawaban_hash'])) {
            $galat = 'Username tidak ditemukan atau belum mengatur pertanyaan keamanan. Hubungi tim IT.';
        } elseif (terkunci($r)) {
            $galat = 'Terlalu banyak percobaan. Coba lagi nanti atau hubungi tim IT.';
        } else {
            $_SESSION['reset_uid'] = (int)$r['id'];
        }

    } elseif ($aksi === 'ganti') {
        $u = ambil($db, $_SESSION['reset_uid'] ?? 0);
        $jawab = normal_jawaban($_POST['jawaban'] ?? '');
        $p = $_POST['password'] ?? '';
        $p2 = $_POST['password2'] ?? '';

        if (!$u) {
            $galat = 'Sesi habis. Mulai lagi dari awal.';
        } elseif (terkunci($u)) {
            unset($_SESSION['reset_uid']);
            $galat = 'Terlalu banyak percobaan. Coba lagi nanti atau hubungi tim IT.';
        } elseif (strlen($p) < 8) {
            $galat = 'Kata sandi baru minimal 8 karakter.';
        } elseif ($p !== $p2) {
            $galat = 'Konfirmasi kata sandi tidak sama.';
        } elseif (!password_verify($jawab, $u['jawaban_hash'])) {
            $n = (int)$u['gagal_reset'] + 1;
            if ($n >= MAKS_SALAH) {
                $db->prepare('UPDATE users SET gagal_reset = 0, terkunci_sampai = ? WHERE id = ?')
                   ->execute([date('Y-m-d H:i:s', time() + KUNCI_MENIT * 60), $u['id']]);
                unset($_SESSION['reset_uid']);
                $galat = 'Terlalu banyak jawaban salah. Dikunci ' . KUNCI_MENIT . ' menit. Atau hubungi tim IT.';
            } else {
                $db->prepare('UPDATE users SET gagal_reset = ? WHERE id = ?')->execute([$n, $u['id']]);
                $galat = 'Jawaban salah. Sisa percobaan: ' . (MAKS_SALAH - $n) . '.';
            }
        } else {
            $db->prepare('UPDATE users SET password_hash = ?, gagal_reset = 0, terkunci_sampai = NULL WHERE id = ?')
               ->execute([password_hash($p, PASSWORD_DEFAULT), $u['id']]);
            unset($_SESSION['reset_uid']);
            $_SESSION['pesan_login'] = 'Kata sandi diganti. Silakan masuk dengan kata sandi baru.';
            header('Location: login.php'); exit;
        }
    }
}

$user = !empty($_SESSION['reset_uid']) ? ambil($db, $_SESSION['reset_uid']) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa kata sandi - Helpdesk IT</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="login.css">
</head>
<body>
<main class="loginwrap">
  <div class="loginbox">
    <h1>Lupa kata sandi</h1>
    <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>

    <?php if (!$user): ?>
      <p class="note">Masukkan username Anda untuk melihat pertanyaan keamanan.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="aksi" value="cari">
        <label for="u">Username</label>
        <input id="u" name="username" required maxlength="40" autocomplete="username">
        <button class="primary" type="submit">Lanjut</button>
      </form>
    <?php else: ?>
      <p class="note">Jawab pertanyaan ini, lalu buat kata sandi baru.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="aksi" value="ganti">
        <label for="a"><?= e($user['pertanyaan']) ?></label>
        <input id="a" name="jawaban" required maxlength="100" autocomplete="off">
        <label for="p">Kata sandi baru (minimal 8 karakter)</label>
        <input id="p" name="password" type="password" required autocomplete="new-password">
        <label for="p2">Ulangi kata sandi baru</label>
        <input id="p2" name="password2" type="password" required autocomplete="new-password">
        <button class="primary" type="submit">Ganti kata sandi</button>
      </form>
    <?php endif; ?>

    <a class="kembali" href="login.php">Kembali ke halaman masuk</a>
  </div>
</main>
</body>
</html>