<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (
    isset($data['unt_id']) &&
    isset($data['pgn_keterangan']) &&
    isset($data['pgn_creaby'])
) {
    $unt_id = $data['unt_id'];
    $pgn_keterangan = $data['pgn_keterangan'];
    $pgn_status = 3; 
    $pgn_creaby = $data['pgn_creaby'];
    $pgn_creadate = date("Y-m-d H:i:s");
    $pgn_tanggal = NULL;
    $pgn_jam_awal = NULL;
    $pgn_jam_akhir = NULL;
    $pgn_hours_meter_akhir = NULL; 

    // Ambil hours_meter_awal dari mmo_unit
    $query_unit = "SELECT unt_hours_meter FROM mmo_unit WHERE unt_id = ?";
    $stmt_unit = $conn->prepare($query_unit);
    $stmt_unit->bind_param("i", $unt_id);

    if ($stmt_unit->execute()) {
        $result_unit = $stmt_unit->get_result();
        $unit_data = $result_unit->fetch_assoc();

        if ($unit_data) {
            // Ambil hours_meter dari unit
            $pgn_hours_meter_awal = $unit_data['unt_hours_meter'];

            // Masukkan data ke tabel mmo_penggunaan
            $query = "INSERT INTO `mmo_penggunaan` (
                        unt_id, pgn_tanggal, pgn_jam_awal, pgn_jam_akhir, 
                        pgn_hours_meter_awal, pgn_hours_meter_akhir, 
                        pgn_keterangan, pgn_status, pgn_creaby, pgn_creadate
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssisssis", $unt_id, $pgn_tanggal, $pgn_jam_awal, $pgn_jam_akhir, $pgn_hours_meter_awal, $pgn_hours_meter_akhir, $pgn_keterangan, $pgn_status, $pgn_creaby, $pgn_creadate);
            
            if ($stmt->execute()) {
                // Update unt_status menjadi 3
                $query_update_status = "UPDATE mmo_unit SET unt_status = 3 WHERE unt_id = ?";
                $stmt_update_status = $conn->prepare($query_update_status);
                $stmt_update_status->bind_param("i", $unt_id);

                if ($stmt_update_status->execute()) {
                    echo json_encode(array('result' => 'Data berhasil disimpan dan status unit diperbarui'));
                } else {
                    echo json_encode(array('result' => 'Data berhasil disimpan tetapi gagal memperbarui status unit', 'error' => $stmt_update_status->error));
                }
            } else {
                echo json_encode(array('result' => 'Data gagal disimpan', 'error' => $stmt->error));
            }
        } else {
            echo json_encode(array('result' => 'Data unit tidak ditemukan'));
        }
    } else {
        echo json_encode(array('result' => 'Query gagal dieksekusi', 'error' => $stmt_unit->error));
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
