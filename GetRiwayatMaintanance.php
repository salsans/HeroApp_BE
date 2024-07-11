<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['pbk_id'])) {
    $pbk_id = $data['pbk_id'];

    // Ambil data dari tabel mmo_perbaikan untuk mendapatkan pbk_creaby dan unt_id
    $query_perbaikan = "SELECT pbk_id, unt_id, pbk_creaby, pbk_creadate FROM mmo_perbaikan WHERE pbk_id = ?";
    $stmt_perbaikan = $conn->prepare($query_perbaikan);
    $stmt_perbaikan->bind_param("i", $pbk_id);

    if ($stmt_perbaikan->execute()) {
        $result_perbaikan = $stmt_perbaikan->get_result();
        $perbaikan_data = $result_perbaikan->fetch_assoc();

        if ($perbaikan_data) {
            $unt_id = $perbaikan_data['unt_id'];
            $pbk_creaby = $perbaikan_data['pbk_creaby'];
            $pbk_creadate = $perbaikan_data['pbk_creadate'];

            // Ambil data dari tabel mmo_unit untuk mendapatkan unt_nama, unt_hours_meter, dan unt_foto
            $query_unit = "SELECT unt_id, unt_nama, unt_hours_meter, unt_foto FROM mmo_unit WHERE unt_id = ?";
            $stmt_unit = $conn->prepare($query_unit);
            $stmt_unit->bind_param("i", $unt_id);

            if ($stmt_unit->execute()) {
                $result_unit = $stmt_unit->get_result();
                $unit_data = $result_unit->fetch_assoc();

                if ($unit_data) {
                    $unt_nama = $unit_data['unt_nama'];
                    $unt_hours_meter = $unit_data['unt_hours_meter'];
                    $unt_foto = $unit_data['unt_foto'];

                    // Format data yang akan dikirim sebagai respons JSON
                    $response_data = array(
                        'pbk_creaby' => $pbk_creaby,
                        'pbk_creadate' => $pbk_creadate,
                        'unt_id' => $unt_id,
                        'unt_nama' => $unt_nama,
                        'unt_hours_meter' => $unt_hours_meter,
                        'unt_foto' => $unt_foto
                    );

                    echo json_encode(array('result' => 'Data berhasil ditemukan', 'data' => $response_data));
                } else {
                    echo json_encode(array('result' => 'Data unit tidak ditemukan'));
                }
            } else {
                echo json_encode(array('result' => 'Query gagal dieksekusi', 'error' => $stmt_unit->error));
            }
        } else {
            echo json_encode(array('result' => 'Data perbaikan dengan pbk_id tersebut tidak ditemukan'));
        }
    } else {
        echo json_encode(array('result' => 'Query gagal dieksekusi', 'error' => $stmt_perbaikan->error));
    }
} else {
    echo json_encode(array('result' => 'Parameter pbk_id tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
