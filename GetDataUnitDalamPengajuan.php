<?php
require_once('koneksi.php');

// Set timezone to Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

try {
    $conn->begin_transaction();

    // Get current timestamp
    $now = time();

    // Calculate 24 hours ago
    $time_threshold = $now - 86400;

    // Query to select units with status 3 and pgn_creadate within 24 hours and pgn_status 1
    $query_select_units = "SELECT 
                            mmo_unit.unt_id AS id, 
                            mmo_unit.unt_nama AS nama, 
                            mmo_unit.unt_hours_meter AS hoursmeter, 
                            mmo_unit.unt_foto AS foto, 
                            mmo_unit.unt_status AS status, 
                            mmo_penggunaan.pgn_creadate AS pgn_creadate, 
                            mmo_penggunaan.pgn_status AS pgn_status 
                          FROM 
                            mmo_unit 
                          JOIN 
                            mmo_penggunaan ON mmo_unit.unt_id = mmo_penggunaan.unt_id 
                          WHERE 
                            mmo_unit.unt_status = '3' AND UNIX_TIMESTAMP(mmo_penggunaan.pgn_creadate) >= ? AND mmo_penggunaan.pgn_status = '1'";
    $stmt_select_units = $conn->prepare($query_select_units);
    $stmt_select_units->bind_param("i", $time_threshold);
    $stmt_select_units->execute();
    $result_units = $stmt_select_units->get_result();

    $result = array();

    while ($row = $result_units->fetch_assoc()) {
        $unt_id = $row['id'];
        $pgn_creadate = $row['pgn_creadate'];
    
        // Convert pgn_creadate to Unix timestamp
        $pgn_creadate_timestamp = strtotime($pgn_creadate);
    
        // Check if pgn_creadate is older than 24 hours
        if ($pgn_creadate_timestamp < $time_threshold) {
            // Update unit status to 1 and pgn_status to 0
            $query_update_unit = "UPDATE mmo_unit SET unt_status = '1' WHERE unt_id =?";
            $stmt_update_unit = $conn->prepare($query_update_unit);
            $stmt_update_unit->bind_param("i", $unt_id);
            $stmt_update_unit->execute();
            $stmt_update_unit->close();
    
            $query_update_penggunaan = "UPDATE mmo_penggunaan SET pgn_status = '0' WHERE unt_id =?";
            $stmt_update_penggunaan = $conn->prepare($query_update_penggunaan);
            $stmt_update_penggunaan->bind_param("i", $unt_id);
            $stmt_update_penggunaan->execute();
            $stmt_update_penggunaan->close();
        }
    
        // Add data to result array
        $result[] = array(
            "unt_id" => $row['id'],
            "unt_nama" => $row['nama'],
            "unt_hours_meter" => $row['hoursmeter'],
            "unt_foto" => $row['foto'],
            "unt_status" => $row['status'],
            "pgn_status" => $row['pgn_status']
        );
    }

    $stmt_select_units->close();
    $conn->commit();

    echo json_encode(array('result' => $result));
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(array('error' => 'Terjadi kesalahan: ' . $e->getMessage()));
} finally {
    $conn->close();
}
?>