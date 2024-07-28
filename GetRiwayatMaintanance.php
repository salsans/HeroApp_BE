<?php
require_once('koneksi.php');
session_start(); // Pastikan session dimulai

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id'])) {
    $unt_id = $data['unt_id'];

    // Ambil data dari tabel mmo_perbaikan untuk mendapatkan pbk_id, pbk_creaby, pbk_tanggal_akhir, dan pbk_jam_akhir
    $query_perbaikan = "SELECT pbk_id, pbk_creaby, pbk_tanggal_akhir, pbk_jam_akhir FROM mmo_perbaikan WHERE unt_id = ? AND pbk_jenis = 2";
    $stmt_perbaikan = $conn->prepare($query_perbaikan);
    $stmt_perbaikan->bind_param("i", $unt_id);

    if ($stmt_perbaikan->execute()) {
        $result_perbaikan = $stmt_perbaikan->get_result();
        $perbaikan_data = $result_perbaikan->fetch_all(MYSQLI_ASSOC);

        if ($perbaikan_data) {
            // Ambil data dari tabel mmo_unit untuk mendapatkan unt_nama, unt_hours_meter, dan unt_foto
            $query_unit = "SELECT unt_hours_meter FROM mmo_unit WHERE unt_id = ?";
            $stmt_unit = $conn->prepare($query_unit);
            $stmt_unit->bind_param("i", $unt_id);

            if ($stmt_unit->execute()) {
                $result_unit = $stmt_unit->get_result();
                $unit_data = $result_unit->fetch_assoc();

                if ($unit_data) {
                    // $unt_nama = $unit_data['unt_nama'];
                    $unt_hours_meter = $unit_data['unt_hours_meter'];
                    // $unt_foto = $unit_data['unt_foto'];

                    $response_data = array();

                    foreach ($perbaikan_data as $perbaikan) {
                        $pbk_creaby = $perbaikan['pbk_creaby'];
                        $nama_pembuat = '';
                        $foto_pembuat = '';

                        // Cek di tabel sso_user untuk mendapatkan role dan id user terkait
                        $query_user = "SELECT rol_id, kry_id, mhs_id FROM sso_user WHERE usr_id = ?";
                        $stmt_user = $conn->prepare($query_user);
                        $stmt_user->bind_param("i", $pbk_creaby);

                        if ($stmt_user->execute()) {
                            $result_user = $stmt_user->get_result();
                            $user_data = $result_user->fetch_assoc();

                            if ($user_data) {
                                if ($user_data['kry_id'] !== null) {
                                    // Ambil nama dan foto dari tabel sso_karyawan
                                    $query_karyawan = "SELECT kry_nama, kry_foto FROM sso_karyawan WHERE kry_id = ?";
                                    $stmt_karyawan = $conn->prepare($query_karyawan);
                                    $stmt_karyawan->bind_param("i", $user_data['kry_id']);
                                    if ($stmt_karyawan->execute()) {
                                        $result_karyawan = $stmt_karyawan->get_result();
                                        $karyawan_data = $result_karyawan->fetch_assoc();
                                        $nama_pembuat = $karyawan_data['kry_nama'];
                                        $foto_pembuat = $karyawan_data['kry_foto'];
                                    }
                                } elseif ($user_data['mhs_id'] !== null) {
                                    // Ambil nama dan foto dari tabel sso_mahasiswa
                                    $query_mahasiswa = "SELECT mhs_nama, mhs_foto FROM sso_mahasiswa WHERE mhs_id = ?";
                                    $stmt_mahasiswa = $conn->prepare($query_mahasiswa);
                                    $stmt_mahasiswa->bind_param("i", $user_data['mhs_id']);
                                    if ($stmt_mahasiswa->execute()) {
                                        $result_mahasiswa = $stmt_mahasiswa->get_result();
                                        $mahasiswa_data = $result_mahasiswa->fetch_assoc();
                                        $nama_pembuat = $mahasiswa_data['mhs_nama'];
                                        $foto_pembuat = $mahasiswa_data['mhs_foto'];
                                    }
                                }
                            }
                        }

                        $response_data[] = array(
                            'pbk_id' => $perbaikan['pbk_id'],
                            'pbk_creaby' => $pbk_creaby,
                            'nama_pembuat' => $nama_pembuat,
                            'foto_pembuat' => $foto_pembuat,
                            'pbk_tanggal_akhir' => $perbaikan['pbk_tanggal_akhir'],
                            'pbk_jam_akhir' => $perbaikan['pbk_jam_akhir'],
                            'unt_id' => $unt_id,
                            // 'unt_nama' => $unt_nama,
                            'unt_hours_meter' => $unt_hours_meter,
                            // 'unt_foto' => $unt_foto
                        );
                    }

                    echo json_encode(array('result' => 'Data berhasil ditemukan', 'data' => $response_data));
                } else {
                    echo json_encode(array('result' => 'Data unit tidak ditemukan'));
                }
            } else {
                echo json_encode(array('result' => 'Query unit gagal dieksekusi', 'error' => $stmt_unit->error));
            }
        } else {
            echo json_encode(array('result' => 'Data perbaikan dengan unt_id tersebut tidak ditemukan'));
        }
    } else {
        echo json_encode(array('result' => 'Query perbaikan gagal dieksekusi', 'error' => $stmt_perbaikan->error));
    }
} else {
    echo json_encode(array('result' => 'Parameter unt_id tidak lengkap'));
}

// Menutup koneksi
$conn->close();
?>
