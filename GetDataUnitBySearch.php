<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_nama'])) {
    $unt_nama = $data['unt_nama'];
    $unt_status = isset($data['unt_status']) ? $data['unt_status'] : null;
    
    $query = "SELECT unt_id as id, unt_nama as nama, unt_hours_meter as hoursmeter, unt_foto as foto, unt_status as status FROM `mmo_unit` WHERE unt_nama LIKE ?";
    $search_param = "%" . $unt_nama . "%";
    $params = array($search_param);

    if ($unt_status !== null) {
        if (is_array($unt_status)) {
            $placeholders = implode(',', array_fill(0, count($unt_status), '?'));
            $query .= " AND unt_status IN ($placeholders)";
            $params = array_merge($params, $unt_status);
        } else {
            $query .= " AND unt_status = ?";
            $params[] = $unt_status;
        }
    }

    $stmt = $conn->prepare($query);
    
    // Dynamically build the types string
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
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
