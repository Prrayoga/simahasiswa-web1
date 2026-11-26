<?php
require 'config/koneksi.php';
require 'config/auth.php';
require 'config/master.php';
require 'config/laporan.php';
wajib_role('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$cari = trim($_GET['cari'] ?? '');
$data = ambil_mahasiswa_export($koneksi, $cari);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Mahasiswa');

// Judul laporan (baris 1 sampai 3)
$sheet->setCellValue('A1', 'DATA MAHASISWA');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$keterangan = 'Dicetak: ' . tanggal_indonesia();
if ($cari !== '') {
    $keterangan .= ' | Filter: ' . $cari;
}
$sheet->setCellValueExplicit('A2', $keterangan, DataType::TYPE_STRING);
$sheet->mergeCells('A2:E2');
$sheet->setCellValue('A3', 'Jumlah data: ' . count($data));
$sheet->mergeCells('A3:E3');

// Header tabel (baris 4)
$sheet->fromArray(['No', 'NIM', 'Nama', 'Jurusan', 'Email'], null, 'A4');
$sheet->getStyle('A4:E4')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER],
]);

// Setiap kolom teks ditulis sebagai STRING eksplisit untuk mencegah formula injection
$baris = 5;
$no    = 1;
foreach ($data as $m) {
    $sheet->setCellValue('A' . $baris, $no++);
    $sheet->setCellValueExplicit('B' . $baris, $m['nim'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $baris, $m['nama'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('D' . $baris, $m['nama_jurusan'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('E' . $baris, $m['email'], DataType::TYPE_STRING);
    $baris++;
}
$terakhir = max(4, $baris - 1);

$sheet->getStyle("A4:E{$terakhir}")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A5:A{$terakhir}")->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (['A' => 6, 'B' => 14, 'C' => 32, 'D' => 26, 'E' => 34] as $kolom => $lebar) {
    $sheet->getColumnDimension($kolom)->setWidth($lebar);
}
$sheet->freezePane('A5');
$sheet->setAutoFilter("A4:E{$terakhir}");

$nama_file = 'data_mahasiswa_' . date('Ymd_His') . '.xlsx';

bersihkan_buffer();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
header('Cache-Control: max-age=0');

$penulis = new Xlsx($spreadsheet);
$penulis->save('php://output');
exit;
