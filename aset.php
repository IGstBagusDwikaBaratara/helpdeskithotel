<?php
require 'config.php';
wajib_it();

$pesan = ''; $galat = '';
if (!empty($_SESSION['flash'])) { $pesan = $_SESSION['flash']; unset($_SESSION['flash']); }
$v = ['kode' => '', 'nama' => '', 'jenis' => JENIS_ASET[0], 'lokasi' => '', 'keterangan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $d = isian_aset();
        if (is_string($d)) {
            $galat = $d;
            $v = array_merge($v, array_intersect_key($_POST, $v));
        } else {
            try {
                $db->prepare('INSERT INTO aset (kode, nama, jenis, lokasi, keterangan) VALUES (?,?,?,?,?)')
                   ->execute([$d['kode'], $d['nama'], $d['jenis'], $d['lokasi'] ?: null, $d['keterangan'] ?: null]);
                $pesan = 'Aset ' . $d['kode'] . ' ditambahkan.';
            } catch (PDOException $x) {
                $galat = ($x->getCode() == 23000) ? 'Kode aset sudah dipakai.' : 'Gagal menyimpan aset.';
                $v = $d;
            }
        }
    } elseif ($aksi === 'aktif' || $aksi === 'nonaktif') {
        $db->prepare('UPDATE aset SET aktif = ? WHERE id = ?')->execute([$aksi === 'aktif' ? 1 : 0, (int)($_POST['id'] ?? 0)]);
        $pesan = $aksi === 'aktif' ? 'Aset diaktifkan.' : 'Aset dinonaktifkan. Riwayat tiketnya tetap tersimpan.';
    }
}

$semua = isset($_GET['semua']);
$q = trim($_GET['q'] ?? '');
$w = []; $p = [];
if (!$semua) $w[] = 'a.aktif = 1';
if ($q !== '') {
    $w[] = '(a.kode LIKE ? OR a.nama LIKE ? OR a.lokasi LIKE ?)';
    array_push($p, "%$q%", "%$q%", "%$q%");
}
$st = $db->prepare('SELECT a.*, COUNT(t.id) AS jml, MAX(t.tanggal) AS terakhir
    FROM aset a LEFT JOIN tiket t ON t.aset_id = a.id'
    . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
    . ' GROUP BY a.id ORDER BY jml DESC, a.kode');
$st->execute($p);
$daftar = $st->fetchAll();

kepala('Daftar Aset', 'aset', 'Perangkat dan inventaris yang bisa dikaitkan ke tiket');
?>
  <?php if ($pesan): ?><p class="flash" role="status"><?= e($pesan) ?></p><?php endif; ?>
  <?php if ($galat): ?><p class="err" role="alert"><?= e($galat) ?></p><?php endif; ?>

  <div class="grid">
    <section class="panel" aria-labelledby="hTambah">
      <h2 id="hTambah">Tambah aset</h2>
      <?php form_aset($v, 'tambah', 'Simpan aset'); ?>
    </section>

    <section class="panel" aria-labelledby="hDaftar">
      <h2 id="hDaftar">Daftar aset (<?= count($daftar) ?>)</h2>
      <form class="tools" method="get" action="aset.php">
        <?php if ($semua): ?><input type="hidden" name="semua" value="1"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kode, nama, atau lokasi" aria-label="Cari aset">
      </form>
      <p class="muted" style="margin:0 0 10px">
        <?php if ($semua): ?>
          Menampilkan semua, termasuk yang nonaktif. <a href="aset.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">Hanya yang aktif</a>
        <?php else: ?>
          Hanya aset aktif. <a href="aset.php?semua=1<?= $q !== '' ? '&amp;q=' . urlencode($q) : '' ?>">Tampilkan juga yang nonaktif</a>
        <?php endif; ?>
        · Urut dari yang paling sering bermasalah.
      </p>

      <div class="scroll">
        <table>
          <tr><th>Kode</th><th>Aset</th><th>Lokasi</th><th class="angka">Tiket</th><th>Terakhir</th><th>Aksi</th></tr>
          <?php if (!$daftar): ?><tr><td colspan="6" class="muted">Belum ada aset.</td></tr><?php endif; ?>
          <?php foreach ($daftar as $a): ?>
            <tr<?= $a['aktif'] ? '' : ' class="mati"' ?>>
              <td class="code"><?= e($a['kode']) ?></td>
              <td><?= e($a['nama']) ?><br><span class="muted"><?= e($a['jenis']) ?></span></td>
              <td><?= e($a['lokasi'] ?? '-') ?></td>
              <td class="angka"><a href="aset_detail.php?id=<?= (int)$a['id'] ?>"><?= (int)$a['jml'] ?></a></td>
              <td><?= $a['terakhir'] ? e(date('d/m/Y', strtotime($a['terakhir']))) : '-' ?></td>
              <td>
                <div class="actions">
                  <a class="btn-edit" href="aset_edit.php?id=<?= (int)$a['id'] ?>">Edit</a>
                  <form method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="aksi" value="<?= $a['aktif'] ? 'nonaktif' : 'aktif' ?>">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="<?= $a['aktif'] ? 'btn-lepas' : 'btn-ambil' ?>" type="submit"><?= $a['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </section>
  </div>
<?php kaki(); ?>