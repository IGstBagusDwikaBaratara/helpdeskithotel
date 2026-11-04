<?php
require 'config.php';
wajib_it();

$f = filter_laporan();
$rows = tiket_laporan($db, $f);

// Cegah sel yang diawali = + - @ dibaca Excel sebagai rumus
function csv_aman($s) {
    $s = (string)$s;
    return preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan-tiket_' . $f['dari'] . '_sd_' . $f['sampai'] . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // penanda UTF-8 supaya Excel membaca huruf dengan benar
$sep = ';';                     // ganti ke ',' kalau Excel Anda memakai koma

fputcsv($out, ['No. Tiket', 'Tanggal', 'Nama', 'Divisi', 'Category 1', 'Category 2', 'Judul', 'Lokasi',
               'Deskripsi', 'Action', 'Status', 'Ditangani oleh', 'Dibuat'], $sep, '"', '\\');

foreach ($rows as $t) {
    fputcsv($out, [
        kode($t['id']),
        date('d/m/Y', strtotime($t['tanggal'])),
        csv_aman($t['nama']),
        csv_aman($t['divisi']),
        csv_aman($t['kategori1']),
        csv_aman($t['kategori2']),
        csv_aman($t['judul']),
        csv_aman($t['lokasi'] ?? ''),
        csv_aman($t['deskripsi']),
        csv_aman($t['tindakan'] ?? ''),
        $t['status'],
        csv_aman($t['pj_nama'] ?? ''),
        date('d/m/Y H:i', strtotime($t['dibuat'])),
    ], $sep, '"', '\\');
}
fclose($out);
exit;