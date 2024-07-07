<?php
require_once('koneksi.php');

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
    $pgn_status = 2; 
    $pgn_creaby = $data['pgn_creaby'];
    $pgn_creadate = date("Y-m-d H:i:s");
    $pgn_tanggal = date("Y-m-d");
    $pgn_jam_awal = date("H:i:s");
    $pgn_jam_akhir = NULL;
    $pgn_hours_meter_akhir = NULL; 

    // Log nilai waktu untuk debugging
    error_log("pgn_jam_awal: " . $pgn_jam_awal);
    error_log("pgn_jam_akhir: " . $pgn_jam_akhir);

    // Ambil hours_meter_awal dari hours_meter_akhir pengajuan sebelumnya
    $query_last_hours = "SELECT pgn_hours_meter_akhir FROM mmo_penggunaan WHERE unt_id = ? ORDER BY pgn_tanggal DESC, pgn_jam_akhir DESC LIMIT 1";
    $stmt_last_hours = $conn->prepare($query_last_hours);
    $stmt_last_hours->bind_param("i", $unt_id);

    if ($stmt_last_hours->execute()) {
        $result_last_hours = $stmt_last_hours->get_result();
        $last_hours = $result_last_hours->fetch_assoc();
        
        // Log data terakhir untuk debugging
        error_log("Last hours data: " . json_encode($last_hours));

        $pgn_hours_meter_awal = $last_hours ? $last_hours['pgn_hours_meter_akhir'] : 0;

        // Log nilai pgn_hours_meter_awal untuk debugging
        error_log("pgn_hours_meter_awal: " . $pgn_hours_meter_awal);
    } else {
        error_log("Error executing query: " . $stmt_last_hours->error);
    }

    $query = "INSERT INTO `mmo_penggunaan` (
                unt_id, pgn_tanggal, pgn_jam_awal, pgn_jam_akhir, 
                pgn_hours_meter_awal, pgn_hours_meter_akhir, 
                pgn_keterangan, pgn_status, pgn_creaby, pgn_creadate
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssisssis", $unt_id, $pgn_tanggal, $pgn_jam_awal, $pgn_jam_akhir, $pgn_hours_meter_awal, $pgn_hours_meter_akhir, $pgn_keterangan, $pgn_status, $pgn_creaby, $pgn_creadate);
    
    if ($stmt->execute()) {
        // Update unt_status menjadi 0
        $query_update_status = "UPDATE mmo_unit SET unt_status = 4 WHERE unt_id = ?";
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
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
