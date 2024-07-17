<?php
require_once('koneksi.php');

// Ambil tanggal hari ini
date_default_timezone_set('Asia/Jakarta');
$tanggalHariIni = date('Y-m-d H:i:s');

// Query untuk mengambil data unit dengan status 2
$select = mysqli_query($conn, "SELECT u.unt_id AS id, u.unt_nama AS nama, u.unt_hours_meter AS hoursmeter, u.unt_foto AS foto, 
            u.unt_status AS status, p.pgn_creadate AS pgn_creadate, p.pgn_status AS pgn_status FROM mmo_unit u JOIN mmo_penggunaan p 
            ON u.unt_id = p.unt_id JOIN (SELECT unt_id, MAX(pgn_creadate) AS max_tanggal FROM mmo_penggunaan GROUP BY unt_id) AS latest 
            ON p.unt_id = latest.unt_id AND p.pgn_creadate = latest.max_tanggal WHERE u.unt_status = '2'");

$result = array();
while($row = mysqli_fetch_array($select)){
    // Cek apakah tanggal pengajuan sudah lewat sehari
    if (strtotime($row['pgn_creadate']) <= strtotime($tanggalHariIni . ' - 1 day')) {

        // Ubah status unit menjadi 1
        $updateUnit = mysqli_query($conn, "UPDATE mmo_unit SET unt_status = '1' WHERE unt_id = '".$row['id']."'");
        // Ubah status penggunaan menjadi 0
        $updatePenggunaan = mysqli_query($conn, "UPDATE mmo_penggunaan SET pgn_status = '0' WHERE unt_id = '".$row['id']."'");
        
        // Jika update berhasil, jangan masukkan data ke array hasil
        if ($updateUnit && $updatePenggunaan) {
            continue; // Lanjutkan ke iterasi berikutnya, sehingga data ini tidak dimasukkan ke array hasil
        }
    }

    // Masukkan data ke array hasil jika tidak perlu diupdate atau update gagal
    array_push($result, array(
        "unt_id" => $row['id'],
        "unt_nama" => $row['nama'],
        "unt_hours_meter" => $row['hoursmeter'],
        "unt_foto" => $row['foto'],
        "unt_status" => $row['status'],
        "pgn_creadate" => $row['pgn_creadate'],
        "pgn_status" => $row['pgn_status']
    ));
}

echo json_encode(array('result' => $result));
?>
