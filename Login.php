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
    
    $stmt_kry = null;
    $stmt_mhs = null;
    
    try {
        // Cek di tabel sso_karyawan
        $query_kry = "SELECT * FROM sso_karyawan WHERE kry_username = ?";
        $stmt_kry = $conn->prepare($query_kry);
        $stmt_kry->bind_param("s", $username);
        $stmt_kry->execute();
        $result_kry = $stmt_kry->get_result();
        
        if ($result_kry->num_rows > 0) {
            $karyawan = $result_kry->fetch_assoc();
            
            // Verifikasi password karyawan
            if (password_verify($password, $karyawan['kry_password'])) {
                // Mencari nama rol berdasarkan rol_id (misalnya, hardcode untuk rol_id=2)
                $role_name = "Karyawan"; // Anda dapat menyesuaikan ini dengan logika untuk mengambil nama rol dari tabel sso_role
                
                // Login berhasil untuk karyawan
                $response = array(
                    'result' => 'Login berhasil',
                    'sso_user' => array(
                        'nama' => $karyawan['kry_nama'],
                        'npk' => $karyawan['kry_npk'],
                        'role' => $role_name
                    )
                );
                echo json_encode($response);
            } else {
                // Login gagal untuk karyawan
                echo json_encode(array('result' => 'Login gagal, username atau password salah'));
            }
        } else {
            // Jika tidak ditemukan di sso_karyawan, cek di tabel sso_mahasiswa
            $query_mhs = "SELECT * FROM sso_mahasiswa WHERE mhs_username = ?";
            $stmt_mhs = $conn->prepare($query_mhs);
            $stmt_mhs->bind_param("s", $username);
            $stmt_mhs->execute();
            $result_mhs = $stmt_mhs->get_result();
            
            if ($result_mhs->num_rows > 0) {
                $mahasiswa = $result_mhs->fetch_assoc();
                
                // Verifikasi password mahasiswa
                if (password_verify($password, $mahasiswa['mhs_password'])) {
                    // Mencari nama rol berdasarkan rol_id (misalnya, hardcode untuk rol_id=8)
                    $role_name = "Mahasiswa"; // Anda dapat menyesuaikan ini dengan logika untuk mengambil nama rol dari tabel sso_role
                    
                    // Login berhasil untuk mahasiswa
                    $response = array(
                        'result' => 'Login berhasil',
                        'sso_user' => array(
                            'nama' => $mahasiswa['mhs_nama'],
                            'nim' => $mahasiswa['nim'],
                            'role' => $role_name
                        )
                    );
                    echo json_encode($response);
                } else {
                    // Login gagal untuk mahasiswa
                    echo json_encode(array('result' => 'Login gagal, username atau password salah'));
                }
            } else {
                // Jika tidak ditemukan di sso_mahasiswa juga, login gagal
                echo json_encode(array('result' => 'Login gagal, user tidak ditemukan'));
            }
        }
    } catch (Exception $e) {
        // Tangani kesalahan
        echo json_encode(array('result' => 'Terjadi kesalahan: ' . $e->getMessage()));
    } finally {
        // Menutup koneksi dan statement
        if ($stmt_kry) {
            $stmt_kry->close();
        }
        if ($stmt_mhs) {
            $stmt_mhs->close();
        }
        $conn->close();
    }
} else {
    echo json_encode(array('result' => 'Parameter tidak lengkap'));
}
?>
