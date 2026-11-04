<?php
function ikon($nama) {
    static $p = [
        'dash'  => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="11" width="8" height="10" rx="1.5"/><rect x="3" y="14" width="8" height="7" rx="1.5"/>',
        'list'  => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'plus'  => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.7a3.5 3.5 0 0 1 0 6.6M18 14.4c2.2.7 3.5 2.6 3.5 5.6"/>',
        'inbox' => '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1 2h6l1-2h5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>',
        'key'   => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'out'   => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'chev'  => '<path d="M6 15l6-6 6 6"/>',
        'menu'  => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'box'   => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8"/>',
    ];
    return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">' . ($p[$nama] ?? '') . '</svg>';
}

function m_item($href, $ikon, $label, $kunci, $aktif, $tingkat = '') {
    $on = ($kunci === $aktif);
    return '<a class="item' . ($tingkat ? ' ' . $tingkat : '') . ($on ? ' active' : '') . '" href="' . e($href) . '"'
         . ($on ? ' aria-current="page"' : '') . '>' . ikon($ikon) . e($label) . '</a>';
}

function m_grup($judul, $buka, $isi) {
    return '<details class="group"' . ($buka ? ' open' : '') . '><summary class="item grp">'
         . ikon('chev') . e($judul) . '</summary><div class="sub">' . $isi . '</div></details>';
}

function kepala($judul, $aktif, $sub = '') {
    $it = adalah_it();
    $nama = $_SESSION['nama'] ?? '';
    $inisial = mb_strtoupper(mb_substr($nama, 0, 1, 'UTF-8'), 'UTF-8');

    if ($it) {
        $menu = m_item('dashboard.php', 'dash', 'Dashboard', 'dashboard', $aktif)
              . m_item('laporan.php', 'chart', 'Laporan', 'laporan', $aktif)
              . m_item('buat.php', 'plus', 'Buat Tiket', 'buat', $aktif)
              . m_item('aset.php', 'box', 'Aset', 'aset', $aktif)
              . m_grup('Antrean Tiket', true,
                    m_item('index.php', 'list', 'Semua', 'semua', $aktif, 'l2')
                  . m_item('index.php?status=Baru', 'inbox', 'Baru', 'Baru', $aktif, 'l2')
                  . m_item('index.php?status=Diproses', 'clock', 'Diproses', 'Diproses', $aktif, 'l2')
                  . m_item('index.php?status=Selesai', 'check', 'Selesai', 'Selesai', $aktif, 'l2'))
              . m_grup('Pengaturan', in_array($aktif, ['pengguna', 'keamanan'], true),
                    m_item('pengguna.php', 'users', 'Kelola Pengguna', 'pengguna', $aktif, 'l2')
                  . m_item('keamanan.php', 'key', 'Pertanyaan Keamanan', 'keamanan', $aktif, 'l2'));
    } else {
        $menu = m_item('lapor.php', 'list', 'Tiket Saya', 'lapor', $aktif)
      . m_grup('Pengaturan', in_array($aktif, ['keamanan', 'akses'], true),
        m_item('minta_akses.php', 'check', 'Akses Tim IT', 'akses', $aktif, 'l2')
      . m_item('keamanan.php', 'key', 'Pertanyaan Keamanan', 'keamanan', $aktif, 'l2'));
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - Helpdesk IT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="app.css">
<script src="menu.js"></script>
</head>
<body>
<aside class="side" aria-label="Menu samping">
  <a class="logo" href="<?= e(halaman_utama()) ?>"><img src="logo.png" alt="Quest Vibe Dewi Sri Bali"></a>
  <nav aria-label="Menu utama"><?= $menu ?></nav>
  <a class="item keluar" href="logout.php"><?= ikon('out') ?>Keluar</a>
</aside>
<div class="ov" id="ov"></div>
<main class="main">
  <div class="bar">
    <button class="menu" id="menu" type="button" aria-label="Buka atau tutup menu" aria-expanded="false"><?= ikon('menu') ?></button>
    <div>
      <h1><?= e($judul) ?></h1>
      <?php if ($sub): ?><p class="sub-t"><?= e($sub) ?></p><?php endif; ?>
    </div>
    <div class="who">
      <span><?= e($nama) ?> · <?= $it ? 'Tim IT' : 'Pelapor' ?></span>
      <div class="av" aria-hidden="true"><?= e($inisial) ?></div>
    </div>
  </div>
<?php
}

function kaki() {
?>
</main>
</body>
</html>
<?php
}