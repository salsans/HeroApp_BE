<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id'])) {
    $unt_id = $data['unt_id'];

    $query = "select sch_id as id, sch_nama as nama, sch_hours_meter as hoursmeter, unt_id as unt_id, sch_status as status FROM `mmo_schedule` WHERE unt_id LIKE ?";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $unt_id . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    while ($row = $result->fetch_assoc()) {
        array_push($response, array(
            "sch_id" => $row['id'],
            "sch_nama" => $row['nama'],
            "sch_hours_meter" => $row['hoursmeter'],
            "unt_id" => $row['unt_id'],
            "sch_status" => $row['status']
        ));
    }
  
    echo json_encode(array('result' => $response));
} else {
    echo json_encode(array('result' => 'Parameter unt_id tidak ditemukan'));
}

$conn->close();
?>


