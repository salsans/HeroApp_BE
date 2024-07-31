<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['usr_id'])) {
    $usr_id = $data['usr_id'];
    
    $query = "SELECT usr_id, apk_id, rol_id, kry_id, mhs_id, usr_status, usr_creaby, usr_creadate, usr_modiby, usr_moidate 
              FROM `sso_user` 
              WHERE usr_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $usr_id); // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    if ($row = $result->fetch_assoc()) {
        $id = $row['usr_id'];
        $role = "";

        if (!is_null($row['kry_id'])) {
            // Ambil data karyawan
            $kry_query = "SELECT kry_nama as nama, kry_npk as npk, 'Admin' as role 
                          FROM `sso_karyawan` 
                          WHERE kry_id = ?";
            $kry_stmt = $conn->prepare($kry_query);
            $kry_stmt->bind_param('i', $row['kry_id']);
            $kry_stmt->execute();
            $kry_result = $kry_stmt->get_result();
            if ($kry_row = $kry_result->fetch_assoc()) {
                $role = $kry_row['role'];
                $response = array(
                    "id" => $id,
                    "nama" => $kry_row['nama'],
                    "npk" => $kry_row['npk'],
                    "role" => $role
                );
            }
        }

        if (!is_null($row['mhs_id'])) {
            // Ambil data mahasiswa
            $mhs_query = "SELECT mhs_nama as nama, nim as npk, 'Student' as role 
                          FROM `sso_mahasiswa` 
                          WHERE mhs_id = ?";
            $mhs_stmt = $conn->prepare($mhs_query);
            $mhs_stmt->bind_param('i', $row['mhs_id']);
            $mhs_stmt->execute();
            $mhs_result = $mhs_stmt->get_result();
            if ($mhs_row = $mhs_result->fetch_assoc()) {
                $role = $mhs_row['role'];
                $response = array(
                    "id" => $id,
                    "nama" => $mhs_row['nama'],
                    "npk" => $mhs_row['npk'],
                    "role" => $role
                );
            }
        }
    }

    if (!empty($response)) {
        echo json_encode(array('result' => array($response), 'message' => 'Login Berhasil'));
    } else {
        echo json_encode(array('result' => [], 'message' => 'User tidak ditemukan atau tidak memiliki role yang valid'));
    }
} else {
    echo json_encode(array('result' => [], 'message' => 'Parameter usr_id tidak ditemukan dalam request body'));
}

$conn->close();
?>
