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

        // Mengambil data perbaikan berdasarkan unt_id
        $query_get_perbaikan = "SELECT * FROM mmo_perbaikan WHERE unt_id = ? ORDER BY pbk_id DESC LIMIT 1";
        $stmt_get_perbaikan = $conn->prepare($query_get_perbaikan);
        $stmt_get_perbaikan->bind_param("i", $unt_id);
        $stmt_get_perbaikan->execute();
        $result = $stmt_get_perbaikan->get_result();
        
        $perbaikan_data = array();
        while ($row = $result->fetch_assoc()) {
            $perbaikan_data[] = $row;
        }
        
        if (!empty($perbaikan_data)) {
            echo json_encode(array(
                'result' => $perbaikan_data,
                'message' => 'Data perbaikan berhasil diambil.'
            ));
        } else {
            echo json_encode(array(
                'result' => array(),
                'message' => 'Perbaikan tidak ditemukan untuk unit ini.'
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
        if (isset($stmt_get_perbaikan)) {
            $stmt_get_perbaikan->close();
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
