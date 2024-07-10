<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['sch_id'])) {
    $unt_nama = $data['sch_id'];

    $query = "SELECT act_id as id, act_nama as nama, act_foto, act_ketengaran, act_status as status, sch_id FROM mmo_action WHERE sch_id LIKE ?";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $sch_id . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    while ($row = $result->fetch_assoc()) {
        array_push($response, array(
            "act_id" => $row['id'],
            "act_nama" => $row['nama'],
            "act_foto" => $row['act_foto'],
            "act_ketengaran" => $row['act_ketengaran'],
            "act_status" => $row['status'],
            "sch_id" => $row['sch_id']
        ));
    }
    
    echo json_encode(array('result' => $response));
} else {
    echo json_encode(array('result' => 'Parameter sch_id tidak ditemukan dalam request body'));
}

$conn->close();
?>