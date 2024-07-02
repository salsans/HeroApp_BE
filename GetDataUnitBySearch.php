<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_nama'])) {
    $unt_nama = $data['unt_nama'];

    $query = "SELECT unt_id as id, unt_nama as nama, unt_hours_meter as hoursmeter, unt_foto as foto, unt_status as status FROM `mmo_unit` WHERE unt_nama LIKE ?";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $unt_nama . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    while ($row = $result->fetch_assoc()) {
        array_push($response, array(
            "unt_id" => $row['id'],
            "unt_nama" => $row['nama'],
            "unt_hours_meter" => $row['hoursmeter'],
            "unt_foto" => $row['foto'],
            "unt_status" => $row['status']
        ));
    }
    
    echo json_encode(array('result' => $response));
} else {
    echo json_encode(array('result' => 'Parameter unt_nama tidak ditemukan dalam request body'));
}

$conn->close();
?>
