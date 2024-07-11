<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['pgn_id']) && isset($data['unt_id']) && isset($data['pgn_modiby'])) {
    $pgn_id = $data['pgn_id'];
    $unt_id = $data['unt_id'];
    $pgn_modiby = $data['pgn_modiby'];
    $pgn_modidate = date("Y-m-d H:i:s");

    try {
        $conn->begin_transaction();

        // Mengubah status pengajuan menjadi disetujui
        $query_update_pengajuan = "UPDATE mmo_penggunaan SET pgn_status = 2, pgn_modiby = ?, pgn_modidate = ? WHERE pgn_id = ?";
        $stmt_update_pengajuan = $conn->prepare($query_update_pengajuan);
        $stmt_update_pengajuan->bind_param("isi", $pgn_modiby, $pgn_modidate, $pgn_id);
        $stmt_update_pengajuan->execute();

        // Mengubah status unit menjadi tersedia
        $query_update_unit = "UPDATE mmo_unit SET unt_status = 2 WHERE unt_id = ?";
        $stmt_update_unit = $conn->prepare($query_update_unit);
        $stmt_update_unit->bind_param("i", $unt_id);
        $stmt_update_unit->execute();

        $conn->commit();
        echo json_encode(array('result' => 'Pengajuan disetujui dan status unit diperbarui.'));
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
        $conn->close();
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}
?>
