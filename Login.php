<?php
require_once('koneksi.php');

// Mengatur timezone jika belum disetel
date_default_timezone_set('Asia/Jakarta');

// Mengambil input dari request body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['username']) && isset($data['password'])) {
    $username = $data['username'];
    $password = $data['password'];

    try {
        $conn->begin_transaction();

        // Step 1: Verify password in sso_mahasiswa
        $query_mhs = "SELECT * FROM sso_mahasiswa WHERE mhs_username = ?";
        $stmt_mhs = $conn->prepare($query_mhs);
        $stmt_mhs->bind_param("s", $username);
        $stmt_mhs->execute();
        $result_mhs = $stmt_mhs->get_result();
        
        if ($result_mhs->num_rows > 0) {
            $mahasiswa = $result_mhs->fetch_assoc();

            // Step 2: Verify password
            if (password_verify($password, $mahasiswa['mhs_password'])) {
                $mhs_id = $mahasiswa['mhs_id'];

                // Step 3: Select from sso_user where mhs_id
                $query_user = "SELECT apk_id, rol_id, mhs_id, kry_id FROM sso_user WHERE mhs_id = ?";
                $stmt_user = $conn->prepare($query_user);
                $stmt_user->bind_param("i", $mhs_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();

                if ($result_user->num_rows > 0) {
                    $row_user = $result_user->fetch_assoc();
                    $apk_id = $row_user['apk_id'];
                    $rol_id = $row_user['rol_id'];

                    if ($apk_id == 3) {
                        // Step 4: Get role name from sso_role
                        $query_role = "SELECT rol_nama FROM sso_role WHERE rol_id = ?";
                        $stmt_role = $conn->prepare($query_role);
                        $stmt_role->bind_param("i", $rol_id);
                        $stmt_role->execute();
                        $result_role = $stmt_role->get_result();

                        if ($result_role->num_rows > 0) {
                            $role = $result_role->fetch_assoc();
                            $rol_nama = $role['rol_nama'];

                            $response = array(
                                'result' => 'Login berhasil',
                                'sso_user' => array(
                                    'nama' => $mahasiswa['mhs_nama'],
                                    'nim' => $mahasiswa['nim'],
                                    'role' => $rol_nama
                                )
                            );
                            echo json_encode($response);
                        } else {
                            echo json_encode(array('result' => 'Login gagal, role tidak ditemukan'));
                        }
                    } else {
                        echo json_encode(array('result' => 'Login gagal, aplikasi tidak diizinkan'));
                    }
                } else {
                    echo json_encode(array('result' => 'Login gagal, user tidak ditemukan'));
                }
            } else {
                echo json_encode(array('result' => 'Login gagal, username atau password salah'));
            }
        } else {
            // Step 1b: Verify password in sso_karyawan
            $query_kry = "SELECT * FROM sso_karyawan WHERE kry_username = ?";
            $stmt_kry = $conn->prepare($query_kry);
            $stmt_kry->bind_param("s", $username);
            $stmt_kry->execute();
            $result_kry = $stmt_kry->get_result();

            if ($result_kry->num_rows > 0) {
                $karyawan = $result_kry->fetch_assoc();

                // Step 2b: Verify password
                if (password_verify($password, $karyawan['kry_password'])) {
                    $kry_id = $karyawan['kry_id'];

                    // Step 3b: Select from sso_user where kry_id
                    $query_user = "SELECT apk_id, rol_id, mhs_id, kry_id FROM sso_user WHERE kry_id = ?";
                    $stmt_user = $conn->prepare($query_user);
                    $stmt_user->bind_param("i", $kry_id);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();

                    if ($result_user->num_rows > 0) {
                        $row_user = $result_user->fetch_assoc();
                        $apk_id = $row_user['apk_id'];
                        $rol_id = $row_user['rol_id'];

                        if ($apk_id == 3) {
                            // Step 4: Get role name from sso_role
                            $query_role = "SELECT rol_nama FROM sso_role WHERE rol_id = ?";
                            $stmt_role = $conn->prepare($query_role);
                            $stmt_role->bind_param("i", $rol_id);
                            $stmt_role->execute();
                            $result_role = $stmt_role->get_result();

                            if ($result_role->num_rows > 0) {
                                $role = $result_role->fetch_assoc();
                                $rol_nama = $role['rol_nama'];

                                $response = array(
                                    'result' => 'Login berhasil',
                                    'sso_user' => array(
                                        'nama' => $karyawan['kry_nama'],
                                        'npk' => $karyawan['npk'],
                                        'role' => $role_nama
                                    )
                                );
                                echo json_encode($response);
                            } else {
                                echo json_encode(array('result' => 'Login gagal, role tidak ditemukan'));
                            }
                        } else {
                            echo json_encode(array('result' => 'Login gagal, aplikasi tidak diizinkan'));
                        }
                    } else {
                        echo json_encode(array('result' => 'Login gagal, user tidak ditemukan'));
                    }
                } else {
                    echo json_encode(array('result' => 'Login gagal, username atau password salah'));
                }
            } else {
                echo json_encode(array('result' => 'Login gagal, user tidak ditemukan'));
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array('result' => 'Terjadi kesalahan: ' . $e->getMessage()));
    } finally {
        if (isset($stmt_mhs)) {
            $stmt_mhs->close();
        }
        if (isset($stmt_kry)) {
            $stmt_kry->close();
        }
        if (isset($stmt_user)) {
            $stmt_user->close();
        }
        if (isset($stmt_role)) {
            $stmt_role->close();
        }
        $conn->close();
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}
?>
