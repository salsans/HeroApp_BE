<?php
require_once('koneksi.php');

// Ambil tanggal hari ini
$tanggalHariIni = date('Y-m-d');

// Query untuk mengambil data unit dengan status 5 dan tanggal pengajuan yang sudah lewat sehari
$select = mysqli_query($conn, "SELECT mmo_unit.unt_id as id, mmo_unit.unt_nama as nama, mmo_unit.unt_hours_meter as hoursmeter, mmo_unit.unt_foto as foto, mmo_unit.unt_status as status, mmo_penggunaan.pgn_tanggal as pgn_tanggal, mmo_penggunaan.pgn_status as pgn_status FROM mmo_unit JOIN mmo_penggunaan ON mmo_unit.unt_id = mmo_penggunaan.unt_id WHERE mmo_unit.unt_status = '3'");

$result = array();
while($row = mysqli_fetch_array($select)){
    // Cek apakah tanggal pengajuan sudah lewat sehari
    if (strtotime($row['pgn_tanggal']) < strtotime($tanggalHariIni . ' - 1 day')) {
        // Ubah status unit menjadi 1
        $updateUnit = mysqli_query($conn, "UPDATE mmo_unit SET unt_status = '0' WHERE unt_id = '".$row['id']."'");
        // Ubah status penggunaan menjadi 0
        $updatePenggunaan = mysqli_query($conn, "UPDATE mmo_penggunaan SET pgn_status = '0' WHERE unt_id = '".$row['id']."'");
        // Perbarui status dalam hasil
        $row['status'] = '1';
        $row['pgn_status'] = '0';
    }
    array_push($result, array(
        "unt_id" => $row['id'],
        "unt_nama" => $row['nama'],
        "unt_hours_meter" => $row['hoursmeter'],
        "unt_foto" => $row['foto'],
        "unt_status" => $row['status'],
        "pgn_status" => $row['pgn_status']
    ));
}

echo json_encode(array('result' => $result));
?>
