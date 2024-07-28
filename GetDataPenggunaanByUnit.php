<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id'])) {
    $unt_id = $data['unt_id'];

    try {
        $conn->begin_transaction();

        // Mengambil pgn_id dari unit terakhir berdasarkan unt_id
        $query_get_last_pengajuan = "SELECT * FROM mmo_penggunaan WHERE unt_id = ? ORDER BY pgn_id DESC LIMIT 1";
        $stmt_get_last_pengajuan = $conn->prepare($query_get_last_pengajuan);
        $stmt_get_last_pengajuan->bind_param("i", $unt_id);
        $stmt_get_last_pengajuan->execute();
        $result = $stmt_get_last_pengajuan->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(array(
                'result' => array($row),
                'message' => 'Data penggunaan berhasil diambil.'
            ));
        } else {
            echo json_encode(array(
                'result' => array(),
                'message' => 'Penggunaan tidak ditemukan untuk unit ini.'
            ));
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array(
            'result' => array(),
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ));
    } finally {
        if (isset($stmt_get_last_pengajuan)) {
            $stmt_get_last_pengajuan->close();
        }
        $conn->close();
    }
} else {
    echo json_encode(array(
        'result' => array(),
        'message' => 'Parameter tidak lengkap'
    ));
}
?>
