<?php
require 'config.php';
wajib_it();

function umur($waktu) {
    $s = max(0, time() - strtotime($waktu));
    if ($s < 3600)  return max(1, (int)floor($s / 60)) . ' menit';
    if ($s < 86400) return (int)floor($s / 3600) . ' jam';
    return (int)floor($s / 86400) . ' hari';
}

function panel_batang($id, $judul, array $baris, $kosong) {
    echo '<section class="panel" aria-labelledby="' . e($id) . '"><h2 id="' . e($id) . '">' . e($judul) . '</h2>';
    if (!$baris) { echo '<p class="muted">' . e($kosong) . '</p></section>'; return; }
    $maks = max(1, max(array_map('intval', array_column($baris, 'n'))));
    foreach ($baris as $b) {
        echo '<div class="brs"><span class="nm" title="' . e($b['nama']) . '">' . e($b['nama']) . '</span>'
           . '<div class="track"><div class="fill" style="width:' . round($b['n'] / $maks * 100) . '%"></div></div>'
           . '<em>' . (int)$b['n'] . '</em></div>';
    }
    echo '</section>';
}

// Ringkasan
$hit = array_fill_keys(STATUS, 0);
foreach ($db->query('SELECT status, COUNT(*) n FROM tiket GROUP BY status') as $r) {
    $hit[$r['status']] = (int)$r['n'];
}
$total  = array_sum($hit);
$persen = $total ? round($hit['Selesai'] / $total * 100) : 0;

$st = $db->prepare('SELECT COUNT(*) FROM tiket WHERE tanggal = ?');
$st->execute([date('Y-m-d')]);
$hariIni = (int)$st->fetchColumn();

$st = $db->prepare('SELECT COUNT(*) FROM tiket WHERE tanggal >= ?');
$st->execute([date('Y-m-01')]);
$bulan = (int)$st->fetchColumn();

$tanpaPJ = (int)$db->query("SELECT COUNT(*) FROM tiket WHERE penanggung_id IS NULL AND status <> 'Selesai'")->fetchColumn();

// Tren 14 hari terakhir
$tren = [];
for ($i = 13; $i >= 0; $i--) $tren[date('Y-m-d', strtotime("-$i days"))] = 0;
$st = $db->prepare('SELECT tanggal, COUNT(*) n FROM tiket WHERE tanggal >= ? GROUP BY tanggal');
$st->execute([date('Y-m-d', strtotime('-13 days'))]);
foreach ($st as $r) {
    if (isset($tren[$r['tanggal']])) $tren[$r['tanggal']] = (int)$r['n'];
}
$maksTren = max(1, max($tren));

// Komposisi status (derajat untuk diagram donat)
$a = $total ? round($hit['Baru'] / $total * 100, 2) : 0;
$b = $total ? round($a + $hit['Diproses'] / $total * 100, 2) : 0;

// Daftar-daftar
$perhatian = $db->query("SELECT id, judul, divisi, dibuat FROM tiket WHERE penanggung_id IS NULL AND status <> 'Selesai' ORDER BY dibuat ASC, id ASC LIMIT 5")->fetchAll();
$terbaru   = $db->query('SELECT id, judul, divisi, status FROM tiket ORDER BY dibuat DESC, id DESC LIMIT 5')->fetchAll();
$kat       = $db->query('SELECT kategori1 AS nama, COUNT(*) AS n FROM tiket GROUP BY kategori1 ORDER BY n DESC, nama LIMIT 6')->fetchAll();
$div       = $db->query('SELECT divisi AS nama, COUNT(*) AS n FROM tiket GROUP BY divisi ORDER BY n DESC, nama LIMIT 6')->fetchAll();

try {
    $lok = $db->query("SELECT lokasi AS nama, COUNT(*) AS n FROM tiket WHERE lokasi IS NOT NULL AND lokasi <> '' GROUP BY lokasi ORDER BY n DESC, nama LIMIT 6")->fetchAll();
} catch (PDOException $x) { $lok = []; }

try {
    $asetTop = $db->query("SELECT a.id, a.kode, a.nama, a.lokasi, COUNT(t.id) AS jml, MAX(t.tanggal) AS terakhir
        FROM aset a JOIN tiket t ON t.aset_id = a.id
        GROUP BY a.id ORDER BY jml DESC, terakhir DESC LIMIT 5")->fetchAll();
} catch (PDOException $x) { $asetTop = []; }

$beban = $db->query("SELECT u.nama,
        COALESCE(SUM(t.status <> 'Selesai'), 0) AS aktif,
        COALESCE(SUM(t.status = 'Selesai'), 0) AS selesai
    FROM users u LEFT JOIN tiket t ON t.penanggung_id = u.id
    WHERE u.peran = 'it'
    GROUP BY u.id, u.nama
    ORDER BY aktif DESC, u.nama")->fetchAll();

$jam = (int)date('G');
$salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));

kepala('Dashboard', 'dashboard', $salam . ', ' . $_SESSION['nama'] . '. Ini ringkasan helpdesk Anda.');
banner_keamanan($db);
?>
  <div class="aksi-cepat">
    <a class="btn utama" href="buat.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Buat tiket</a>
    <a class="btn" href="index.php">Antrean tiket</a>
    <a class="btn" href="laporan.php">Laporan</a>
    <a class="btn" href="aset.php">Aset</a>
  </div>

  <section class="stats" aria-label="Ringkasan tiket">
    <div class="stat total"><span>Total tiket</span><b><?= $total ?></b><small><?= $hariIni ?> hari ini · <?= $bulan ?> bulan ini</small></div>
    <div class="stat baru"><span>Baru</span><b><?= $hit['Baru'] ?></b><small>menunggu ditangani</small></div>
    <div class="stat proses"><span>Diproses</span><b><?= $hit['Diproses'] ?></b><small>sedang dikerjakan</small></div>
    <div class="stat selesai"><span>Selesai</span><b><?= $hit['Selesai'] ?></b><small><?= $persen ?>% dari total</small></div>
    <a class="stat kosong" href="index.php?pj=kosong"><span>Belum ada penanggung</span><b><?= $tanpaPJ ?></b><small>tiket belum diambil</small></a>
  </section>

  <div class="dua-kol">
    <section class="panel" aria-labelledby="hTren">
      <h2 id="hTren">Tiket 14 hari terakhir</h2>
      <?php if (array_sum($tren) === 0): ?>
        <p class="muted">Belum ada tiket dalam 14 hari terakhir. Grafik muncul begitu ada tiket masuk.</p>
      <?php else: ?>
        <div class="tren" role="img" aria-label="Grafik jumlah tiket per hari selama 14 hari terakhir">
          <?php foreach ($tren as $tgl => $n): ?>
            <div class="kol" title="<?= e(date('d/m/Y', strtotime($tgl))) ?>: <?= $n ?> tiket">
              <span class="n"><?= $n ?: '' ?></span>
              <div class="bar-t<?= $n ? '' : ' nol' ?>" style="height:<?= $n ? max(6, round($n / $maksTren * 80)) : 3 ?>%"></div>
              <span class="tgl"><?= e(date('j', strtotime($tgl))) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="hStatus">
      <h2 id="hStatus">Komposisi status</h2>
      <div class="donat-wrap">
        <div class="donat" style="background:<?= $total ? 'conic-gradient(var(--baru) 0 ' . $a . '%, var(--proses) ' . $a . '% ' . $b . '%, var(--selesai) ' . $b . '% 100%)' : 'var(--line)' ?>">
          <b><?= $total ?><small> Tiket</small></b>
        </div>
        <ul class="legenda">
          <li><span class="kotak l-baru"></span>Baru <span class="n"><?= $hit['Baru'] ?></span></li>
          <li><span class="kotak l-proses"></span>Diproses <span class="n"><?= $hit['Diproses'] ?></span></li>
          <li><span class="kotak l-selesai"></span>Selesai <span class="n"><?= $hit['Selesai'] ?></span></li>
        </ul>
      </div>
    </section>
  </div>

  <div class="sama">
    <section class="panel" aria-labelledby="hPerhatian">
      <h2 id="hPerhatian">Butuh perhatian</h2>
      <?php if (!$perhatian): ?>
        <p class="muted">Semua tiket aktif sudah ada penanggung jawabnya.</p>
      <?php else: ?>
        <div class="scroll">
          <table>
            <tr><th>No.</th><th>Judul</th><th>Divisi</th><th>Menunggu</th></tr>
            <?php foreach ($perhatian as $t): ?>
              <tr>
                <td class="code"><?= e(kode($t['id'])) ?></td>
                <td><?= e($t['judul']) ?></td>
                <td><?= e($t['divisi']) ?></td>
                <td class="umur"><?= e(umur($t['dibuat'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <a class="lihat" href="index.php?pj=kosong">Lihat semua yang belum diambil</a>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="hBaru">
      <h2 id="hBaru">Tiket terbaru</h2>
      <?php if (!$terbaru): ?>
        <p class="muted">Belum ada tiket. <a href="buat.php">Buat tiket pertama</a>.</p>
      <?php else: ?>
        <div class="scroll">
          <table>
            <tr><th>No.</th><th>Judul</th><th>Divisi</th><th>Status</th></tr>
            <?php foreach ($terbaru as $t): ?>
              <tr>
                <td class="code"><?= e(kode($t['id'])) ?></td>
                <td><?= e($t['judul']) ?></td>
                <td><?= e($t['divisi']) ?></td>
                <td><span class="tag <?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <a class="lihat" href="index.php">Lihat semua tiket</a>
      <?php endif; ?>
    </section>
  </div>

  <div class="tigakol">
    <?php panel_batang('hKat', 'Per Category 1', $kat, 'Belum ada data.'); ?>
    <?php panel_batang('hDiv', 'Per divisi', $div, 'Belum ada data.'); ?>
    <?php panel_batang('hLok', 'Per lokasi', $lok, 'Belum ada tiket yang mengisi lokasi.'); ?>
  </div>

  <div class="sama">
    <section class="panel" aria-labelledby="hAset">
      <h2 id="hAset">Perangkat paling sering bermasalah</h2>
      <?php if (!$asetTop): ?>
        <p class="muted">Belum ada tiket yang dikaitkan ke aset. Isi Kode aset di formulir tiket.</p>
      <?php else: ?>
        <div class="scroll">
          <table>
            <tr><th>Kode</th><th>Aset</th><th class="angka">Tiket</th><th>Terakhir</th></tr>
            <?php foreach ($asetTop as $x): ?>
              <tr>
                <td class="code"><a href="aset_detail.php?id=<?= (int)$x['id'] ?>"><?= e($x['kode']) ?></a></td>
                <td><?= e($x['nama']) ?></td>
                <td class="angka"><?= (int)$x['jml'] ?></td>
                <td><?= e(date('d/m/Y', strtotime($x['terakhir']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
      <a class="lihat" href="aset.php">Lihat semua aset</a>
    </section>

    <section class="panel" aria-labelledby="hBeban">
      <h2 id="hBeban">Beban kerja teknisi</h2>
      <div class="scroll">
        <table>
          <tr><th>Teknisi</th><th class="angka">Sedang ditangani</th><th class="angka">Selesai</th></tr>
          <?php if (!$beban): ?><tr><td colspan="3" class="muted">Belum ada akun Tim IT.</td></tr><?php endif; ?>
          <?php foreach ($beban as $x): ?>
            <tr><td><?= e($x['nama']) ?></td><td class="angka"><?= (int)$x['aktif'] ?></td><td class="angka"><?= (int)$x['selesai'] ?></td></tr>
          <?php endforeach; ?>
        </table>
      </div>
    </section>
  </div>
<?php kaki(); ?>