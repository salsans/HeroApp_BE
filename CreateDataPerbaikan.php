<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id']) && isset($data['pbk_jenis'])) {
    $unt_id = $data['unt_id'];
    $pbk_jenis = $data['pbk_jenis'];
    $pbk_creadate = date("Y-m-d H:i:s");

    // Ambil data dari tabel mmo_unit
    $query_unit = "SELECT unt_id, unt_nama, unt_status, unt_foto, unt_hours_meter FROM mmo_unit WHERE unt_id = ?";
    $stmt_unit = $conn->prepare($query_unit);
    $stmt_unit->bind_param("i", $unt_id);

    if ($stmt_unit->execute()) {
        $result_unit = $stmt_unit->get_result();
        $unit_data = $result_unit->fetch_assoc();

        if ($unit_data) {
            // Ambil hours meter dari unit
            $pbk_hours_meter = $unit_data['unt_hours_meter'];

            // Masukkan data ke tabel mmo_perbaikan
            $query_insert_pbk = "INSERT INTO mmo_perbaikan (unt_id, pbk_jenis, pbk_tanggal_awal, pbk_tanggal_akhir, pbk_hours_meter, pbk_creaby, pbk_creadate) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_pbk = $conn->prepare($query_insert_pbk);
            $pbk_tanggal_awal = date("Y-m-d");
            $pbk_tanggal_akhir = NULL;

            $stmt_insert_pbk->bind_param("isssiss", $unit_data['unt_id'], $pbk_jenis, $pbk_tanggal_awal, $pbk_tanggal_akhir, $pbk_hours_meter, $pbk_creaby, $pbk_creadate);

            if ($stmt_insert_pbk->execute()) {
                echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_perbaikan'));
            } else {
                echo json_encode(array('result' => 'Data gagal disimpan di tabel mmo_perbaikan', 'error' => $stmt_insert_pbk->error));
            }
        } else {
            echo json_encode(array('result' => 'Data unit tidak ditemukan'));
        }
    } else {
        echo json_encode(array('result' => 'Query gagal dieksekusi', 'error' => $stmt_unit->error));
    }
} else {
    echo json_encode(array('result' => 'Parameter unt_id atau pbk_jenis tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
