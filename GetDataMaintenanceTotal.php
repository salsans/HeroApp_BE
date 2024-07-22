<?php
     
require_once('koneksi.php');

// Query untuk menghitung total pbk_id dengan pbk_jenis 2
$query = "SELECT COUNT(pbk_id) as total FROM `mmo_perbaikan` WHERE pbk_jenis = 2";
$select = mysqli_query($conn, $query);

$result = array();
if ($row = mysqli_fetch_array($select)) {
    $result['total'] = $row['total'];
} else {
    $result['total'] = 0;
}

echo json_encode($result);

?>
