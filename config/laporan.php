<?php
// Helper laporan (export PDF dan Excel), dipanggil dengan require 'config/laporan.php';

require_once __DIR__ . '/../vendor/autoload.php';

// Zona waktu agar tanggal dan jam cetak sesuai waktu setempat
date_default_timezone_set('Asia/Jakarta');

function tanggal_indonesia(?int $timestamp = null): string
{
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = $timestamp ?? time();
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function nama_file_aman(string $teks): string
{
    return preg_replace('/[^A-Za-z0-9_-]+/', '_', $teks);
}

// Membuang output tertahan di buffer sebelum mengirim file biner agar file tidak rusak
function bersihkan_buffer(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// $unduh = false: dibuka di tab browser (inline); true: langsung diunduh (attachment)
function kirim_pdf(string $html, string $nama_file, string $kertas = 'A4',
                   string $orientasi = 'portrait', bool $unduh = false): void
{
    $opsi = new \Dompdf\Options();
    $opsi->set('defaultFont', 'DejaVu Sans');
    $opsi->set('isRemoteEnabled', false); // cegah pemuatan sumber dari internet

    $dompdf = new \Dompdf\Dompdf($opsi);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper($kertas, $orientasi);
    $dompdf->render();

    $kanvas = $dompdf->getCanvas();
    $font   = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
    $kanvas->page_text(
        $kanvas->get_width() - 120, $kanvas->get_height() - 28,
        'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]
    );

    bersihkan_buffer();
    $dompdf->stream($nama_file, ['Attachment' => $unduh]);
    exit;
}

// Mengambil biodata, daftar nilai, total SKS, dan rata-rata berbobot satu mahasiswa (null jika tidak ada)
function ambil_data_khs(mysqli $koneksi, int $mahasiswa_id): ?array
{
    $mhs = ambil_baris($koneksi,
        "SELECT m.nim, m.nama, m.email, j.nama_jurusan
         FROM mahasiswa m
         INNER JOIN jurusan j ON j.id = m.jurusan_id
         WHERE m.id = ?", "i", $mahasiswa_id);
    if (!$mhs) {
        return null;
    }

    $nilai = ambil_semua($koneksi,
        "SELECT mk.kode_mk, mk.nama_mk, mk.sks, n.nilai_angka
         FROM nilai n
         INNER JOIN mata_kuliah mk ON mk.id = n.mata_kuliah_id
         WHERE n.mahasiswa_id = ?
         ORDER BY mk.kode_mk", "i", $mahasiswa_id);

    $total_sks   = 0;
    $total_bobot = 0;
    foreach ($nilai as $n) {
        $total_sks   += $n['sks'];
        $total_bobot += $n['nilai_angka'] * $n['sks'];
    }

    return [
        'mhs'       => $mhs,
        'nilai'     => $nilai,
        'total_sks' => $total_sks,
        'rata'      => $total_sks > 0 ? $total_bobot / $total_sks : null,
    ];
}

function html_khs(array $d): string
{
    $m = $d['mhs'];
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 2cm 2cm 2.2cm 2cm; }
    body  { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
    h1    { font-size: 16px; text-align: center; margin: 0; }
    .sub  { text-align: center; font-size: 10px; color: #666; margin: 2px 0 10px; }
    hr    { border: 0; border-top: 2px solid #2c3e50; margin: 8px 0 14px; }
    table.bio td { padding: 2px 4px; }
    table.nilai { width: 100%; border-collapse: collapse; margin-top: 14px; }
    table.nilai th, table.nilai td { border: 1px solid #999; padding: 5px 6px; }
    table.nilai th { background: #2c3e50; color: #fff; }
    .tengah { text-align: center; }
    .kanan  { text-align: right; }
    .ringkas { margin-top: 12px; }
    table.ttd { width: 100%; margin-top: 36px; }
</style>
</head>
<body>
    <h1>KARTU HASIL STUDI (KHS)</h1>
    <div class="sub">SIMAHASISWA &middot; Praktikum Web Programming</div>
    <hr>

    <table class="bio">
        <tr><td width="70">NIM</td><td width="10">:</td><td><?= htmlspecialchars($m['nim']) ?></td></tr>
        <tr><td>Nama</td><td>:</td><td><?= htmlspecialchars($m['nama']) ?></td></tr>
        <tr><td>Jurusan</td><td>:</td><td><?= htmlspecialchars($m['nama_jurusan']) ?></td></tr>
        <tr><td>Email</td><td>:</td><td><?= htmlspecialchars($m['email']) ?></td></tr>
    </table>

    <table class="nilai">
        <thead>
            <tr><th width="28">No</th><th width="55">Kode</th><th>Mata Kuliah</th>
                <th width="35">SKS</th><th width="50">Nilai</th><th width="40">Mutu</th></tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($d['nilai'] as $n) : ?>
                <tr>
                    <td class="tengah"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($n['kode_mk']) ?></td>
                    <td><?= htmlspecialchars($n['nama_mk']) ?></td>
                    <td class="tengah"><?= $n['sks'] ?></td>
                    <td class="kanan"><?= number_format($n['nilai_angka'], 2, ',', '.') ?></td>
                    <td class="tengah"><?= huruf_mutu($n['nilai_angka']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($d['nilai']) === 0) : ?>
                <tr><td colspan="6" class="tengah">Belum ada nilai.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($d['rata'] !== null) : ?>
        <div class="ringkas">
            Total SKS: <strong><?= $d['total_sks'] ?></strong><br>
            Rata-rata berbobot: <strong><?= number_format($d['rata'], 2, ',', '.') ?></strong>
                (<?= huruf_mutu($d['rata']) ?>)
        </div>
    <?php endif; ?>

    <table class="ttd">
        <tr>
            <td width="60%">Dicetak pada <?= tanggal_indonesia() ?></td>
            <td class="tengah">Mengetahui,<br><br><br><br><strong>Administrator</strong></td>
        </tr>
    </table>
</body>
</html>
<?php
    return ob_get_clean();
}
