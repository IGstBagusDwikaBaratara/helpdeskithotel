<?php
require 'config.php';
wajib_it();

$f = filter_laporan();
$rows = tiket_laporan($db, $f);
$r = ringkas_laporan($rows);
$persen = $r['total'] ? round($r['status']['Selesai'] / $r['total'] * 100) : 0;

$qs = http_build_query(array_filter($f, 'strlen'));
$bulanIni  = ['dari' => date('Y-m-01'), 'sampai' => date('Y-m-d')];
$bulanLalu = ['dari' => date('Y-m-01', strtotime('first day of last month')),
              'sampai' => date('Y-m-t', strtotime('first day of last month'))];

kepala('Laporan', 'laporan', 'Rekap tiket per periode, siap diunduh atau dicetak');
?>
  <section class="panel" aria-labelledby="hFilter">
    <h2 id="hFilter">Periode laporan</h2>
    <form class="filter" method="get" action="laporan.php">
      <div>
        <label for="dari">Dari</label>
        <input id="dari" name="dari" type="date" required value="<?= e($f['dari']) ?>">
      </div>
      <div>
        <label for="sampai">Sampai</label>
        <input id="sampai" name="sampai" type="date" required value="<?= e($f['sampai']) ?>">
      </div>
      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="">Semua</option>
          <?php opsi(STATUS, $f['status']); ?>
        </select>
      </div>
      <button class="btn utama" type="submit">Tampilkan</button>
    </form>
    <div class="chips tools" style="margin:12px 0 0">
      <?php foreach (['Bulan ini' => $bulanIni, 'Bulan lalu' => $bulanLalu] as $lbl => $rg):
          $on = ($f['dari'] === $rg['dari'] && $f['sampai'] === $rg['sampai']);
          $href = 'laporan.php?' . http_build_query(array_filter($rg + ['status' => $f['status']], 'strlen'));
      ?>
        <a class="chip<?= $on ? ' on' : '' ?>" href="<?= e($href) ?>"><?= e($lbl) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($r['total'] === 0): ?>
    <p class="empty" style="margin-top:14px">Tidak ada tiket pada periode ini.</p>
  <?php else: ?>
    <section class="stats" style="margin-top:14px" aria-label="Ringkasan">
      <div class="stat total"><span>Total tiket</span><b><?= $r['total'] ?></b><small><?= e(date('d/m/Y', strtotime($f['dari']))) ?> - <?= e(date('d/m/Y', strtotime($f['sampai']))) ?></small></div>
      <div class="stat baru"><span>Baru</span><b><?= $r['status']['Baru'] ?></b></div>
      <div class="stat proses"><span>Diproses</span><b><?= $r['status']['Diproses'] ?></b></div>
      <div class="stat selesai"><span>Selesai</span><b><?= $r['status']['Selesai'] ?></b><small><?= $persen ?>% dari total</small></div>
    </section>

    <p>
      <a class="btn utama" href="laporan_csv.php?<?= e($qs) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Unduh CSV</a>
      <a class="btn" href="laporan_cetak.php?<?= e($qs) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-print" aria-hidden="true"></i> Cetak / PDF</a>
    </p>

    <div class="tigakol">
      <?php tabel_hitung('Per Category 1', $r['kat']); ?>
      <?php tabel_hitung('Per divisi', $r['divisi']); ?>
      <?php tabel_hitung('Per penanggung jawab', $r['teknisi']); ?>
    </div>

    <section class="panel" aria-labelledby="hRinci">
      <h2 id="hRinci">Rincian tiket</h2>
      <div class="scroll">
        <table>
          <tr><th>No.</th><th>Tanggal</th><th>Judul</th><th>Divisi</th><th>Status</th><th>Ditangani oleh</th></tr>
          <?php foreach (array_slice($rows, 0, 25) as $t): ?>
            <tr>
              <td class="code"><?= e(kode($t['id'])) ?></td>
              <td><?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></td>
              <td><?= e($t['judul']) ?></td>
              <td><?= e($t['divisi']) ?></td>
              <td><span class="tag <?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
              <td><?= e($t['pj_nama'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php if ($r['total'] > 25): ?>
        <p class="muted" style="margin:10px 0 0">Menampilkan 25 dari <?= $r['total'] ?> tiket. File CSV dan cetakan memuat semuanya.</p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php kaki(); ?>