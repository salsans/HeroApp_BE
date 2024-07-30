<?php
header('Content-Type: application/json');

// Database connection
include 'koneksi.php';

// Retrieve GET parameters
$unt_id = isset($_GET['unt_id']) ? $_GET['unt_id'] : '';

// Check if unt_id is provided
if (empty($unt_id)) {
    echo json_encode(array('status' => 'error', 'message' => 'unt_id is required'));
    exit();
}

// Prepare SQL query with parameters
$query = "SELECT * FROM mmo_perbaikan WHERE unt_id = ? AND pbk_jenis = 1 ORDER BY pbk_id DESC LIMIT 1";
$stmt = $conn->prepare($query);

// Check if prepare() returns false
if ($stmt === false) {
    echo json_encode(array('status' => 'error', 'message' => 'Failed to prepare SQL statement'));
    exit();
}

$stmt->bind_param("s", $unt_id);
$stmt->execute();
$result = $stmt->get_result();

$perbaikan_list = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $perbaikan_list[] = $row;
    }
    echo json_encode(array('result' => $perbaikan_list, 'message' => 'Berhasil'));
} else {
    echo json_encode(array('result' => null, 'message' => 'No Perbaikan found for unt_id: ' . $unt_id));
}

$stmt->close();
$conn->close();
?>
