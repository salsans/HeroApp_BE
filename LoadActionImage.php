<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mendapatkan act_id dari URL
if (isset($_GET['act_id'])) {
    $act_id = $_GET['act_id'];
} elseif (isset($_SERVER['PATH_INFO'])) {
    $path_info = $_SERVER['PATH_INFO'];
    $path_parts = explode('/', $path_info);
    $act_id = isset($path_parts[1]) ? intval($path_parts[1]) : null;
}

if ($act_id !== null) {
    try {
        // Query untuk mengambil foto berdasarkan act_id
        $sql = "SELECT act_foto FROM mmo_action WHERE act_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $act_id);
        $stmt->execute();
        $stmt->bind_result($act_foto);
        $stmt->fetch();

        // Menutup koneksi statement dan connection
        $stmt->close();
        $conn->close();

        // Menampilkan gambar jika ditemukan
        if ($act_foto) {
            header("Content-Type: image/jpeg");
            echo $act_foto;
        } else {
            echo "Foto tidak ditemukan.";
        }
    } catch (Exception $e) {
        echo json_encode(array('result' => 'Terjadi kesalahan: ' . $e->getMessage()));
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo json_encode(array('result' => 'Parameter act_id tidak ditemukan dalam URL'));
}
?>
