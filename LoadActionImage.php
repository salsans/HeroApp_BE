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
        // Query untuk mengambil data berdasarkan act_id
        $sql = "SELECT act_nama, act_keterangan, act_foto FROM mmo_action WHERE act_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $act_id);
        $stmt->execute();
        $stmt->bind_result($act_nama, $act_keterangan, $act_foto);
        $stmt->fetch();

        // Menutup koneksi statement
        $stmt->close();
        $conn->close();

        // Menampilkan data jika ditemukan
        if ($act_nama || $act_keterangan || $act_foto) {
            if (isset($_GET['get_foto']) && $_GET['get_foto'] == '1' && $act_foto) {
                $mime_type = '';

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->buffer($act_foto);

                // Memastikan hanya file gambar yang diizinkan
                if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/jpg'])) {
                    header("Content-Type: $mime_type");
                    echo $act_foto;
                    exit;
                } else {
                    echo json_encode(array('result' => 'Tipe file tidak didukung.'));
                    exit;
                }
            } else {
                header("Content-Type: application/json");
                echo json_encode(array(
                    'act_nama' => $act_nama,
                    'act_keterangan' => $act_keterangan,
                    'act_foto' => $act_foto ? true : false 
                ));
                exit;
            }
        } else {
            echo json_encode(array('result' => 'Data tidak ditemukan.'));
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
