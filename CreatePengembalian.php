<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (
    isset($data['unt_id']) &&
    isset($data['pgn_hours_meter_akhir']) &&
    isset($data['pgn_creaby'])
) {
    $unt_id = $data['unt_id'];
    $pgn_hours_meter_akhir = $data['pgn_hours_meter_akhir'];
    $pgn_status = 3; // Status pengembalian
    $pgn_modiby = $data['pgn_creaby'];
    $pgn_modidate = date("Y-m-d");
    $pgn_jam_akhir = date("H:i:s");

    // Log nilai waktu untuk debugging
    error_log("pgn_jam_akhir: " . $pgn_jam_akhir);

    // Ambil data penggunaan terakhir yang belum dikembalikan
    $query_last_usage = "SELECT pgn_id, pgn_tanggal, pgn_jam_awal, pgn_hours_meter_awal 
                         FROM mmo_penggunaan 
                         WHERE unt_id = ? AND pgn_status = 3 
                         ORDER BY pgn_tanggal DESC, pgn_jam_awal DESC 
                         LIMIT 1";
    $stmt_last_usage = $conn->prepare($query_last_usage);
    $stmt_last_usage->bind_param("s", $unt_id);

    if ($stmt_last_usage->execute()) {
        $result_last_usage = $stmt_last_usage->get_result();
        $last_usage = $result_last_usage->fetch_assoc();

        if ($last_usage) {
            $pgn_id = $last_usage['pgn_id'];
            $pgn_hours_meter_awal = $last_usage['pgn_hours_meter_awal'];
            $pgn_tanggal = $last_usage['pgn_tanggal'];
            $pgn_jam_awal = $last_usage['pgn_jam_awal'];

            // Log data terakhir untuk debugging
            error_log("Last usage data: " . json_encode($last_usage));

            // Update data penggunaan
            $query_update = "UPDATE mmo_penggunaan 
                             SET pgn_hours_meter_akhir = ?, pgn_jam_akhir = ?, pgn_modiby = ?, pgn_modidate = ?, pgn_status = ? 
                             WHERE pgn_id = ?";
            $stmt_update = $conn->prepare($query_update);
            $stmt_update->bind_param("ssssss", $pgn_hours_meter_akhir, $pgn_jam_akhir, $pgn_modiby, $pgn_modidate, $pgn_status, $pgn_id);

            if ($stmt_update->execute()) {
                // Update status unit (unt_status) menjadi 1 setelah pengembalian sukses
                $query_update_unit_status = "UPDATE mmo_unit SET unt_status = 1 WHERE unt_id = ?";
                $stmt_update_unit_status = $conn->prepare($query_update_unit_status);
                $stmt_update_unit_status->bind_param("s", $unt_id);
                $stmt_update_unit_status->execute();

                echo json_encode(array('result' => 'Data berhasil diperbarui'));
            } else {
                echo json_encode(array('result' => 'Data gagal diperbarui', 'error' => $stmt_update->error));
            }
        } else {
            echo json_encode(array('result' => 'Data penggunaan tidak ditemukan atau sudah dikembalikan'));
        }
    } else {
        error_log("Error executing query: " . $stmt_last_usage->error);
        echo json_encode(array('result' => 'Terjadi kesalahan pada saat pengambilan data terakhir'));
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
