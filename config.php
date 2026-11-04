<?php
date_default_timezone_set('Asia/Makassar');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

try {
    $db = new PDO('mysql:host=localhost;dbname=helpdesk;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $x) {
    exit('Database tidak bisa dihubungi. Pastikan MySQL di XAMPP menyala dan database sudah dibuat.');
}

// true = pelapor melihat semua tiket. false = pelapor hanya melihat tiketnya sendiri.
const PELAPOR_LIHAT_SEMUA = true;
// true = karyawan boleh daftar sendiri (jadi Pelapor). false = akun hanya dibuat tim IT.
const DAFTAR_TERBUKA = true;

const STATUS = ['Baru', 'Diproses', 'Selesai'];
const DIVISI = ['Not a Request', 'Admin & General', 'Accounting', 'Information Technology',
    'Sales & Marketing', 'Human Resources', 'Front Office', 'Housekeeping', 'Engineering',
    'F&B Product', 'F&B Service', 'Guest'];
const KAT1 = ['Troubleshooting', 'User/Technical Request', 'Installation/Set Up', 'Configuration',
    'Preventive Maintenance', 'Monitoring & Inspection', 'Operational Support', 'Training',
    'Design', 'Documentation', 'Administration', 'Vendor Coordination', 'Asset Coordination', 'Other'];
const KAT2 = ['Workstation', 'Software Application', 'Account & Password', 'Telephony', 'Printing',
    'Network & Internet', 'Security Systems', 'Server & Infrastructure', 'Room Technologies',
    'Hotel Systems', 'Hotel Operations', 'Vendor & Services', 'Assets & Inventory', 'Other'];
const JENIS_ASET = ['Komputer', 'Laptop', 'Printer', 'Jaringan', 'Telepon',
    'TV / Perangkat Kamar', 'Server', 'CCTV / Keamanan', 'Lainnya']; 
const TELEGRAM_TOKEN = '8630427818:AAGQvY6mLXG6G7ohogj42B-vKoa_GOV0gw8';   
const TELEGRAM_CHAT  = '-1004475890645';   
const URL_APLIKASI   = 'http://172.16.2.163/helpdesk';  

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function cek_csrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman.');
    }
}

function wajib_login() {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}
function adalah_it() { return ($_SESSION['peran'] ?? '') === 'it'; }
function wajib_it() {
    wajib_login();
    if (!adalah_it()) { http_response_code(403); exit('Hanya untuk tim IT.'); }
}

function halaman_utama() { return adalah_it() ? 'dashboard.php' : 'lapor.php'; }

function masuk(array $u, $tujuan = null) {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['nama'] = $u['nama'];
    $_SESSION['peran'] = $u['peran'];
    header('Location: ' . ($tujuan ?: halaman_utama()));
    exit;
}

function opsi(array $daftar, string $pilih) {
    foreach ($daftar as $o) {
        echo '<option' . ($o === $pilih ? ' selected' : '') . '>' . e($o) . '</option>';
    }
}

function kode($id) { return 'TKT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }

function normal_jawaban($s) {
    return preg_replace('/\s+/u', ' ', mb_strtolower(trim((string)$s), 'UTF-8'));
}

function banner_keamanan($db) {
    $st = $db->prepare('SELECT pertanyaan FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    if (!$st->fetchColumn()) {
        echo '<p class="info" role="status">Atur <a href="keamanan.php">pertanyaan keamanan</a> supaya Anda bisa mengatur ulang kata sandi sendiri kalau lupa.</p>';
    }
    if (adalah_it()) {
        $n = (int)$db->query("SELECT COUNT(*) FROM users WHERE akses_it = 'diminta'")->fetchColumn();
        if ($n > 0) {
            echo '<p class="info" role="status">Ada ' . $n . ' permintaan akses Tim IT. <a href="pengguna.php">Tinjau sekarang</a>.</p>';
        }
    }
}

function isian_tiket($edit = false) {
    $d = [
        'nama' => trim($_POST['nama'] ?? ''),
        'tanggal' => $_POST['tanggal'] ?? '',
        'divisi' => $_POST['divisi'] ?? '',
        'kategori1' => $_POST['kategori1'] ?? '',
        'kategori2' => $_POST['kategori2'] ?? '',
        'judul' => trim($_POST['judul'] ?? ''),
        'lokasi' => trim($_POST['lokasi'] ?? ''),
        'aset_kode' => strtoupper(trim($_POST['aset_kode'] ?? '')),
        'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    ];
    if ($edit) $d['tindakan'] = trim($_POST['tindakan'] ?? '');

    if ($d['nama'] === '' || $d['judul'] === '' || $d['deskripsi'] === '') return 'Nama, judul, dan deskripsi wajib diisi.';
    if (!$edit && $d['lokasi'] === '') return 'Lokasi wajib diisi.';
    $tgl = DateTime::createFromFormat('Y-m-d', $d['tanggal']);
    if (!$tgl || $tgl->format('Y-m-d') !== $d['tanggal']) return 'Tanggal tidak valid.';
    if (!in_array($d['divisi'], DIVISI, true) || !in_array($d['kategori1'], KAT1, true) || !in_array($d['kategori2'], KAT2, true)) return 'Pilihan tidak valid.';
    if (mb_strlen($d['nama']) > 60 || mb_strlen($d['judul']) > 90 || mb_strlen($d['lokasi']) > 60 || mb_strlen($d['aset_kode']) > 30
        || mb_strlen($d['deskripsi']) > 600 || mb_strlen($d['tindakan'] ?? '') > 600) return 'Teks terlalu panjang.';
    return $d;
}

function daftar_it($db) {
    return $db->query("SELECT id, nama FROM users WHERE peran = 'it' ORDER BY nama")->fetchAll();
}

function notif_tiket_baru($id, array $d) {
    $teks = "🎫 Tiket baru " . kode($id) . "\n"
          . $d['judul'] . "\n\n"
          . "Lokasi: " . ($d['lokasi'] !== '' ? $d['lokasi'] : '-') . "\n"
          . "Pelapor: " . $d['nama'] . " (" . $d['divisi'] . ")\n"
          . "Kategori: " . $d['kategori1'] . " / " . $d['kategori2'] . "\n"
          . "Tanggal: " . date('d/m/Y', strtotime($d['tanggal'])) . "\n\n"
          . mb_strimwidth($d['deskripsi'], 0, 200, '...') . "\n\n"
          . "Buka antrean: " . URL_APLIKASI . "/index.php?status=Baru";
    kirim_telegram($teks);
}

function filter_laporan() {
    $hariIni = date('Y-m-d');
    $dari   = (string)($_GET['dari'] ?? date('Y-m-01'));
    $sampai = (string)($_GET['sampai'] ?? $hariIni);
    $valid = function ($s) {
        $d = DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    };
    if (!$valid($dari))   $dari = date('Y-m-01');
    if (!$valid($sampai)) $sampai = $hariIni;
    if ($dari > $sampai) { $tmp = $dari; $dari = $sampai; $sampai = $tmp; }
    $status = (string)($_GET['status'] ?? '');
    if (!in_array($status, STATUS, true)) $status = '';
    return ['dari' => $dari, 'sampai' => $sampai, 'status' => $status];
}

function tiket_laporan($db, array $f) {
    $w = ['t.tanggal BETWEEN ? AND ?'];
    $p = [$f['dari'], $f['sampai']];
    if ($f['status'] !== '') { $w[] = 't.status = ?'; $p[] = $f['status']; }
    $st = $db->prepare('SELECT t.*, u.nama AS pj_nama FROM tiket t
                        LEFT JOIN users u ON u.id = t.penanggung_id
                        WHERE ' . implode(' AND ', $w) . ' ORDER BY t.tanggal, t.id');
    $st->execute($p);
    return $st->fetchAll();
}

function ringkas_laporan(array $rows) {
    $r = ['total' => count($rows), 'status' => array_fill_keys(STATUS, 0), 'kat' => [], 'divisi' => [], 'teknisi' => []];
    foreach ($rows as $t) {
        $r['status'][$t['status']]++;
        $r['kat'][$t['kategori1']] = ($r['kat'][$t['kategori1']] ?? 0) + 1;
        $r['divisi'][$t['divisi']] = ($r['divisi'][$t['divisi']] ?? 0) + 1;
        $pj = $t['pj_nama'] ?: '(Belum ada penanggung)';
        $r['teknisi'][$pj] = ($r['teknisi'][$pj] ?? 0) + 1;
    }
    foreach (['kat', 'divisi', 'teknisi'] as $k) arsort($r[$k]);
    return $r;
}

function tabel_hitung($judul, array $data, $kelas = 'panel') {
    echo '<section class="' . e($kelas) . '"><h2>' . e($judul) . '</h2>';
    echo '<table><tr><th>Nama</th><th class="angka">Jumlah</th></tr>';
    if (!$data) echo '<tr><td colspan="2" class="muted">Tidak ada data.</td></tr>';
    foreach ($data as $nama => $n) {
        echo '<tr><td>' . e($nama) . '</td><td class="angka">' . (int)$n . '</td></tr>';
    }
    echo '</table></section>';
}
// Mengubah kode aset yang diketik menjadi id. Hasil: [id atau null, pesan galat]
function aset_dari_kode($db, $kode) {
    $kode = trim((string)$kode);
    if ($kode === '') return [null, ''];
    $st = $db->prepare('SELECT id FROM aset WHERE kode = ? AND aktif = 1');
    $st->execute([$kode]);
    $id = $st->fetchColumn();
    return $id ? [(int)$id, ''] : [null, 'Kode aset ' . $kode . ' tidak ditemukan. Cek stiker di perangkat, atau kosongkan kalau tidak tahu.'];
}

function isian_aset() {
    $d = [
        'kode' => strtoupper(trim($_POST['kode'] ?? '')),
        'nama' => trim($_POST['nama'] ?? ''),
        'jenis' => $_POST['jenis'] ?? '',
        'lokasi' => trim($_POST['lokasi'] ?? ''),
        'keterangan' => trim($_POST['keterangan'] ?? ''),
    ];
    if (!preg_match('/^[A-Z0-9._-]{2,30}$/', $d['kode'])) return 'Kode 2 sampai 30 karakter: huruf, angka, titik, garis bawah, atau strip (tanpa spasi).';
    if ($d['nama'] === '' || mb_strlen($d['nama']) > 80) return 'Isi nama aset (maksimal 80 karakter).';
    if (!in_array($d['jenis'], JENIS_ASET, true)) return 'Jenis aset tidak valid.';
    if (mb_strlen($d['lokasi']) > 60 || mb_strlen($d['keterangan']) > 200) return 'Lokasi maksimal 60 dan keterangan maksimal 200 karakter.';
    return $d;
}

function form_aset(array $v, $aksi, $tombol, $action = '') {
?>
    <form method="post" action="<?= e($action) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="aksi" value="<?= e($aksi) ?>">

      <label for="kode">Kode aset</label>
      <input id="kode" name="kode" required maxlength="30" placeholder="Contoh: PC-FO-01" value="<?= e($v['kode']) ?>" style="text-transform:uppercase">
      <p class="petunjuk">Tempel kode ini sebagai stiker di perangkat. Huruf, angka, titik, garis bawah, atau strip.</p>

      <label for="nama">Nama aset</label>
      <input id="nama" name="nama" required maxlength="80" placeholder="Contoh: PC Front Desk 1" value="<?= e($v['nama']) ?>">

      <label for="jenis">Jenis</label>
      <select id="jenis" name="jenis"><?php opsi(JENIS_ASET, (string)$v['jenis']); ?></select>

      <label for="lokasi">Lokasi</label>
      <input id="lokasi" name="lokasi" maxlength="60" placeholder="Contoh: Front Office" value="<?= e($v['lokasi']) ?>">

      <label for="keterangan">Keterangan</label>
      <input id="keterangan" name="keterangan" maxlength="200" placeholder="Merk, model, nomor seri (opsional)" value="<?= e($v['keterangan']) ?>">

      <button class="primary" type="submit"><?= e($tombol) ?></button>
    </form>
<?php
}
require __DIR__ . '/layout.php'; 