<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id']) && isset($data['pbk_jenis']) && isset($data['pbk_creaby'])) {
    $unt_id = $data['unt_id'];
    $pbk_jenis = $data['pbk_jenis'];
    $pbk_creaby = $data['pbk_creaby'];
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
            $query_insert_pbk = "INSERT INTO mmo_perbaikan (unt_id, pbk_jenis, pbk_tanggal_awal, pbk_jam_awal, pbk_tanggal_akhir, pbk_hours_meter, pbk_creaby, pbk_creadate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_pbk = $conn->prepare($query_insert_pbk);
            $pbk_tanggal_awal = date("Y-m-d"); // Menyimpan tanggal saat ini
            $pbk_jam_awal = date("H:i:s"); // Menyimpan waktu saat ini
            $pbk_tanggal_akhir = date("Y-m-d"); // Menyimpan tanggal saat ini juga

            $stmt_insert_pbk->bind_param("issssiss", $unt_id, $pbk_jenis, $pbk_tanggal_awal, $pbk_jam_awal, $pbk_tanggal_akhir, $pbk_hours_meter, $pbk_creaby, $pbk_creadate);

            if ($stmt_insert_pbk->execute()) {
                $jns = ($pbk_jenis == 1) ? 4 : 5;
                $query_update_unit = "UPDATE mmo_unit SET unt_status = ? WHERE unt_id = ?";
                $stmt_update_unit = $conn->prepare($query_update_unit);
                $stmt_update_unit->bind_param("ii", $jns, $unt_id);

                if ($stmt_update_unit->execute()) {
                    echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_perbaikan dan status unit berhasil diubah'));
                } else {
                    echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_perbaikan, tetapi gagal mengubah status unit', 'error' => $stmt_update_unit->error));
                }
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
