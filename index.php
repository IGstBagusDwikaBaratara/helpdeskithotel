<?php
require 'config.php';
wajib_login();
if (!adalah_it()) { header('Location: lapor.php'); exit; }

$fs = $_GET['status'] ?? '';
if (!in_array($fs, STATUS, true)) $fs = '';
$pj = $_GET['pj'] ?? '';
if (!in_array($pj, ['saya', 'kosong'], true)) $pj = '';
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $w[] = '(t.judul LIKE ? OR t.nama LIKE ? OR t.lokasi LIKE ? OR a.kode LIKE ? OR a.nama LIKE ? OR t.id = ?)';
    array_push($p, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
    $p[] = preg_match('/^(tkt-?)?\d+$/i', $q) ? (int)preg_replace('/\D/', '', $q) : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $uid = (int)$_SESSION['uid'];

    if ($aksi === 'status') {
        $s = $_POST['status'] ?? '';
        if (in_array($s, STATUS, true)) {
            if ($s === 'Diproses' || $s === 'Selesai') {
                // Kalau belum ada penanggung jawab, yang mengubah status otomatis tercatat
                $db->prepare('UPDATE tiket SET status = ?,
                        diambil_pada = IF(penanggung_id IS NULL, NOW(), diambil_pada),
                        penanggung_id = IF(penanggung_id IS NULL, ?, penanggung_id)
                        WHERE id = ?')->execute([$s, $uid, $id]);
            } else {
                $db->prepare('UPDATE tiket SET status = ? WHERE id = ?')->execute([$s, $id]);
            }
            $_SESSION['flash'] = kode($id) . ' diubah ke ' . $s . '.';
        }

    } elseif ($aksi === 'ambil') {
        // Hanya berhasil kalau tiket belum ada penanggung jawab
        $st = $db->prepare("UPDATE tiket SET penanggung_id = ?, diambil_pada = NOW(),
                status = IF(status = 'Baru', 'Diproses', status)
                WHERE id = ? AND penanggung_id IS NULL");
        $st->execute([$uid, $id]);
        if ($st->rowCount() === 1) {
            $_SESSION['flash'] = kode($id) . ' sekarang Anda tangani.';
        } else {
            $n = $db->prepare('SELECT u.nama FROM tiket t LEFT JOIN users u ON u.id = t.penanggung_id WHERE t.id = ?');
            $n->execute([$id]);
            $_SESSION['flash'] = kode($id) . ' sudah diambil oleh ' . ($n->fetchColumn() ?: 'teknisi lain') . '.';
        }

    } elseif ($aksi === 'lepas') {
        $st = $db->prepare("UPDATE tiket SET penanggung_id = NULL, diambil_pada = NULL,
                status = IF(status = 'Diproses', 'Baru', status)
                WHERE id = ? AND penanggung_id = ?");
        $st->execute([$id, $uid]);
        if ($st->rowCount() === 1) $_SESSION['flash'] = kode($id) . ' dilepas dan kembali ke antrean.';

    } elseif ($aksi === 'hapus') {
        $db->prepare('DELETE FROM tiket WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = 'Tiket ' . kode($id) . ' dihapus.';
    }

    // Kembali ke daftar yang sama (filter dan pencarian dipertahankan)
    $param = array_filter(['status' => $fs, 'pj' => $pj, 'q' => $q], 'strlen');
    header('Location: index.php' . ($param ? '?' . http_build_query($param) : ''));
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Hitungan per status
$hit = array_fill_keys(STATUS, 0);
foreach ($db->query('SELECT status, COUNT(*) n FROM tiket GROUP BY status') as $r) {
    $hit[$r['status']] = (int)$r['n'];
}
$total = array_sum($hit);

// Hitungan penanggung jawab
$st = $db->prepare("SELECT COALESCE(SUM(penanggung_id = ?), 0) AS saya,
                           COALESCE(SUM(penanggung_id IS NULL AND status <> 'Selesai'), 0) AS kosong
                    FROM tiket");
$st->execute([$_SESSION['uid']]);
$jml = $st->fetch();

// Daftar tiket
$w = []; $p = [];
if ($fs !== '') { $w[] = 't.status = ?'; $p[] = $fs; }
if ($pj === 'saya') { $w[] = 't.penanggung_id = ?'; $p[] = $_SESSION['uid']; }
if ($pj === 'kosong') { $w[] = "t.penanggung_id IS NULL AND t.status <> 'Selesai'"; }
if ($q !== '') {
    $w[] = '(t.judul LIKE ? OR t.nama LIKE ? OR t.id = ?)';
    $p[] = "%$q%"; $p[] = "%$q%"; $p[] = (int)preg_replace('/\D/', '', $q);
}
$st = $db->prepare('SELECT t.*, u.nama AS pj_nama, a.kode AS aset_kode FROM tiket t
    LEFT JOIN users u ON u.id = t.penanggung_id
    LEFT JOIN aset a ON a.id = t.aset_id'
    . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY t.dibuat DESC, t.id DESC LIMIT 300');
$st->execute($p);
$tiket = $st->fetchAll();

kepala('Antrean Tiket', $fs === '' ? 'semua' : $fs, 'Ambil, kerjakan, dan selesaikan tiket');
?>
  <section class="stats" aria-label="Ringkasan tiket">
    <div class="stat baru"><span>Baru</span><b><?= $hit['Baru'] ?></b></div>
    <div class="stat proses"><span>Diproses</span><b><?= $hit['Diproses'] ?></b></div>
    <div class="stat selesai"><span>Selesai</span><b><?= $hit['Selesai'] ?></b></div>
  </section>

  <?php if ($flash): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
  <?php banner_keamanan($db); ?>

  <section class="panel" aria-labelledby="hList">
    <h2 id="hList">Antrean tiket</h2>
    <form class="tools" method="get" action="index.php">
      <?php if ($fs !== ''): ?><input type="hidden" name="status" value="<?= e($fs) ?>"><?php endif; ?>
      <?php if ($pj !== ''): ?><input type="hidden" name="pj" value="<?= e($pj) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul, nama, atau nomor tiket" aria-label="Cari tiket">
    </form>

    <div class="chips tools" role="group" aria-label="Filter status">
      <?php foreach (array_merge([''], STATUS) as $s):
          $n = $s === '' ? $total : $hit[$s];
          $href = 'index.php?' . http_build_query(array_filter(['status' => $s, 'pj' => $pj, 'q' => $q], 'strlen'));
      ?>
        <a class="chip<?= $fs === $s ? ' on' : '' ?>" href="<?= e($href) ?>"><?= e($s === '' ? 'Semua' : $s) ?> (<?= $n ?>)</a>
      <?php endforeach; ?>
    </div>

    <div class="chips tools" role="group" aria-label="Filter penanggung jawab">
      <?php foreach (['' => 'Semua penanggung', 'saya' => 'Tiket saya (' . (int)$jml['saya'] . ')', 'kosong' => 'Belum ada penanggung (' . (int)$jml['kosong'] . ')'] as $k => $lbl):
          $href = 'index.php?' . http_build_query(array_filter(['status' => $fs, 'pj' => $k, 'q' => $q], 'strlen'));
      ?>
        <a class="chip<?= $pj === $k ? ' on' : '' ?>" href="<?= e($href) ?>"><?= e($lbl) ?></a>
      <?php endforeach; ?>
    </div>

    <ul class="list">
      <?php if (!$tiket): ?>
        <li class="empty"><?= $total ? 'Tidak ada tiket yang cocok dengan filter ini.' : 'Belum ada tiket. Buat tiket lewat menu Buat Tiket.' ?></li>
      <?php endif; ?>
      <?php foreach ($tiket as $t):
          $punyaSaya = ((int)$t['penanggung_id'] === (int)$_SESSION['uid']);
          $selesai = ($t['status'] === 'Selesai');
      ?>
        <li class="ticket <?= e($t['status']) ?>">
          <div class="top">
            <h3><?= e($t['judul']) ?></h3>
            <span class="id"><?= e(kode($t['id'])) ?></span>
          </div>
          <p class="desc"><?= nl2br(e($t['deskripsi'])) ?></p>
          <?php if (!empty($t['tindakan'])): ?>
            <p class="aksi"><b>Action:</b> <?= nl2br(e($t['tindakan'])) ?></p>
          <?php endif; ?>
          <div class="meta">
            <span class="tag <?= e($t['status']) ?>"><?= e($t['status']) ?></span>
            <span class="tag"><?= e($t['kategori1']) ?></span>
            <span class="tag"><?= e($t['kategori2']) ?></span>
          <?php if (!empty($t['lokasi'])): ?>
            <span class="tag lok">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i> 
              <?= e($t['lokasi']) ?></span>
          <?php if (!empty($t['aset_kode'])): ?>
          <a class="tag aset" href="aset_detail.php?id=<?= (int)$t['aset_id'] ?>">
          <i class="fa-solid fa-box" aria-hidden="true"></i>
          <?= e($t['aset_kode']) ?></a><?php endif; ?>
          <?php endif; ?>
            <span><?= e($t['nama']) ?>, <?= e($t['divisi']) ?> · <?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></span>
          </div>
          <?php if ($t['penanggung_id']): ?>
            <p class="pj">Ditangani oleh <b><?= e($t['pj_nama']) ?></b><?= $punyaSaya ? ' (Anda)' : '' ?></p>
          <?php elseif (!$selesai): ?>
            <p class="pj kosong">Belum ada penanggung jawab</p>
          <?php endif; ?>

          <div class="actions">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
              <input type="hidden" name="aksi" value="status">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <select name="status" aria-label="Ubah status <?= e(kode($t['id'])) ?>" onchange="this.form.submit()">
                <?php opsi(STATUS, $t['status']); ?>
              </select>
            </form>

            <?php if (!$t['penanggung_id'] && !$selesai): ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="aksi" value="ambil">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button class="btn-ambil" type="submit">Ambil tiket</button>
              </form>
            <?php elseif ($punyaSaya && !$selesai): ?>
              <form method="post" onsubmit="return confirm('Lepas tiket <?= e(kode($t['id'])) ?>? Tiket kembali ke antrean.')">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="aksi" value="lepas">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button class="btn-lepas" type="submit">Lepas</button>
              </form>
            <?php endif; ?>

            <a class="btn-edit" href="edit.php?id=<?= (int)$t['id'] ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit</a>
            <form method="post" onsubmit="return confirm('Hapus tiket <?= e(kode($t['id'])) ?>?')">
              <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn-hapus" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php kaki(); ?>