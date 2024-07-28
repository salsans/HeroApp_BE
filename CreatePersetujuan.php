<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id']) && isset($data['pgn_modiby'])) {
    $unt_id = $data['unt_id'];
    $pgn_tanggal = date("Y-m-d");
    $pgn_jam_awal = date("H:i:s");
    $pgn_modiby = $data['pgn_modiby'];
    $pgn_modidate = date("Y-m-d H:i:s");

    try {
        $conn->begin_transaction();

        // Mengambil pgn_id dari unit terakhir berdasarkan unt_id
        $query_get_last_pengajuan = "SELECT pgn_id FROM mmo_penggunaan WHERE unt_id = ? ORDER BY pgn_id DESC LIMIT 1";
        $stmt_get_last_pengajuan = $conn->prepare($query_get_last_pengajuan);
        $stmt_get_last_pengajuan->bind_param("i", $unt_id);
        $stmt_get_last_pengajuan->execute();
        $result = $stmt_get_last_pengajuan->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $pgn_id = $row['pgn_id'];

            // Mengubah status pengajuan menjadi disetujui
            $query_update_pengajuan = "UPDATE mmo_penggunaan SET pgn_status = 3, pgn_modiby = ?, pgn_tanggal = ?, pgn_jam_awal = ?, pgn_modidate = ? WHERE pgn_id = ?";
            $stmt_update_pengajuan = $conn->prepare($query_update_pengajuan);
            $stmt_update_pengajuan->bind_param("ssssi", $pgn_modiby, $pgn_tanggal, $pgn_jam_awal, $pgn_modidate, $pgn_id);
            $stmt_update_pengajuan->execute();

            // Mengubah status unit menjadi tersedia
            $query_update_unit = "UPDATE mmo_unit SET unt_status = 3 WHERE unt_id = ?";
            $stmt_update_unit = $conn->prepare($query_update_unit);
            $stmt_update_unit->bind_param("i", $unt_id);
            $stmt_update_unit->execute();

            $conn->commit();
            echo json_encode(array('result' => 'Pengajuan disetujui dan status unit diperbarui.'));
        } else {
            echo json_encode(array('result' => 'Pengajuan tidak ditemukan untuk unit ini.'));
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array('result' => 'Terjadi kesalahan: ' . $e->getMessage()));
    } finally {
        if (isset($stmt_update_unit)) {
            $stmt_update_unit->close();
        }
        if (isset($stmt_update_pengajuan)) {
            $stmt_update_pengajuan->close();
        }
        if (isset($stmt_get_last_pengajuan)) {
            $stmt_get_last_pengajuan->close();
        }
        $conn->close();
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}
?>
