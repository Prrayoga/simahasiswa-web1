<?php
require 'config/koneksi.php';
require 'config/auth.php';
require 'config/master.php';
require 'config/laporan.php';
wajib_role('admin');

$cari = trim($_GET['cari'] ?? '');
$data = ambil_mahasiswa_export($koneksi, $cari);

// Tangkap HTML ke dalam variabel agar tidak tercetak ke browser
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 2cm 1.8cm 2.2cm 1.8cm; }
    body  { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
    h1    { font-size: 16px; text-align: center; margin: 0; }
    .sub  { text-align: center; font-size: 10px; color: #666; margin: 2px 0 10px; }
    hr    { border: 0; border-top: 2px solid #2c3e50; margin: 8px 0 12px; }
    .info { font-size: 10px; margin-bottom: 8px; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th, table.data td { border: 1px solid #999; padding: 5px 6px; }
    table.data th { background: #2c3e50; color: #fff; }
    table.data tr.genap td { background: #f2f5f8; }
    .tengah { text-align: center; }
</style>
</head>
<body>
    <h1>DAFTAR MAHASISWA</h1>
    <div class="sub">SIMAHASISWA &middot; Praktikum Web Programming</div>
    <hr>

    <div class="info">
        Tanggal cetak: <?= tanggal_indonesia() ?><br>
        Jumlah data: <?= count($data) ?>
        <?php if ($cari !== '') : ?>
            <br>Filter pencarian: <?= htmlspecialchars($cari) ?>
        <?php endif; ?>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No</th><th width="80">NIM</th>
                <th>Nama</th><th>Jurusan</th><th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $m) : ?>
                <tr class="<?= $no % 2 === 0 ? 'genap' : '' ?>">
                    <td class="tengah"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($m['nim']) ?></td>
                    <td><?= htmlspecialchars($m['nama']) ?></td>
                    <td><?= htmlspecialchars($m['nama_jurusan']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($data) === 0) : ?>
                <tr><td colspan="5" class="tengah">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

kirim_pdf($html, 'data_mahasiswa_' . date('Ymd_His') . '.pdf', 'A4',
          'portrait', isset($_GET['unduh']));
