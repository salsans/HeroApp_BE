<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['pbk_id']) && isset($data['mtc_keterangan'])) {
    $pbk_id = $data['pbk_id'];
    $mtc_keterangan = $data['mtc_keterangan'];
    $mtc_creadate = date("Y-m-d H:i:s");
    $pbk_tanggal_akhir = date("Y-m-d");

    // Ambil data dari tabel mmo_perbaikan
    $query_perbaikan = "SELECT pbk_id, unt_id, pbk_jenis, pbk_tanggal_awal, pbk_tanggal_akhir, pbk_hours_meter, pbk_creaby, pbk_creadate, pbk_modiby, pbk_modidate FROM mmo_perbaikan WHERE pbk_id = ?";
    $stmt_perbaikan = $conn->prepare($query_perbaikan);
    $stmt_perbaikan->bind_param("i", $pbk_id);

    if ($stmt_perbaikan->execute()) {
        $result_perbaikan = $stmt_perbaikan->get_result();
        $perbaikan_data = $result_perbaikan->fetch_assoc();

        if ($perbaikan_data) {
            $unt_id = $perbaikan_data['unt_id'];

            // Masukkan data ke tabel mmo_save_perbaikan_action
            $query_insert_mtc = "INSERT INTO mmo_save_perbaikan_action (pbk_id, mtc_keterangan, mtc_creadate) VALUES (?, ?, ?)";
            $stmt_insert_mtc = $conn->prepare($query_insert_mtc);
            $stmt_insert_mtc->bind_param("iss", $pbk_id, $mtc_keterangan, $mtc_creadate);

            if ($stmt_insert_mtc->execute()) {
                // Ubah unt_status menjadi 1 di tabel mmo_unit
                $query_update_unit = "UPDATE mmo_unit SET unt_status = 1 WHERE unt_id = ?";
                $stmt_update_unit = $conn->prepare($query_update_unit);
                $stmt_update_unit->bind_param("i", $unt_id);

                if ($stmt_update_unit->execute()) {
                    // Ubah pbk_tanggal_akhir dan pbk_jam_akhir di tabel mmo_perbaikan
                    $query_update_perbaikan = "UPDATE mmo_perbaikan SET pbk_tanggal_akhir = ?, pbk_jam_akhir = CURRENT_TIME() WHERE pbk_id = ?";
                    $stmt_update_perbaikan = $conn->prepare($query_update_perbaikan);
                    $stmt_update_perbaikan->bind_param("si", $pbk_tanggal_akhir, $pbk_id);

                    if ($stmt_update_perbaikan->execute()) {
                        echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_save_perbaikan_action, unt_status berhasil diubah menjadi 1, dan pbk_tanggal_akhir serta pbk_jam_akhir berhasil diperbarui'));
                    } else {
                        echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_save_perbaikan_action dan unt_status berhasil diubah menjadi 1, tetapi gagal memperbarui pbk_tanggal_akhir dan pbk_jam_akhir', 'error' => $stmt_update_perbaikan->error));
                    }
                } else {
                    echo json_encode(array('result' => 'Data berhasil disimpan di tabel mmo_save_perbaikan_action, tetapi gagal mengubah unt_status', 'error' => $stmt_update_unit->error));
                }
            } else {
                echo json_encode(array('result' => 'Data gagal disimpan di tabel mmo_save_perbaikan_action', 'error' => $stmt_insert_mtc->error));
            }
        } else {
            echo json_encode(array('result' => 'Data perbaikan dengan pbk_id tersebut tidak ditemukan'));
        }
    } else {
        echo json_encode(array('result' => 'Query gagal dieksekusi', 'error' => $stmt_perbaikan->error));
    }
} else {
    echo json_encode(array('result' => 'Parameter pbk_id atau mtc_keterangan tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
