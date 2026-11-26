<?php
require 'config/koneksi.php';
require 'config/auth.php';
require 'config/master.php';
require 'config/laporan.php';
wajib_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$d  = ambil_data_khs($koneksi, $id);

if (!$d) {
    http_response_code(404);
    echo "Data mahasiswa tidak ditemukan.";
    exit;
}

kirim_pdf(html_khs($d), 'KHS_' . nama_file_aman($d['mhs']['nim']) . '.pdf',
          'A4', 'portrait', isset($_GET['unduh']));
