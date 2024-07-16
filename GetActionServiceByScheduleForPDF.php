<?php
require_once('koneksi.php');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['unt_id']) && isset($data['pbk_id'])) {
    $unt_id = $data['unt_id'];
    $pbk_id = $data['pbk_id'];

    // Fetch unit details
    $unitQuery = "
        SELECT unt_id, unt_nama
        FROM mmo_unit
        WHERE unt_id = ?";
    $unitStmt = $conn->prepare($unitQuery);
    $unitStmt->bind_param("i", $unt_id);
    $unitStmt->execute();
    $unitResult = $unitStmt->get_result();
    $unitData = $unitResult->fetch_assoc();

    // Fetch perbaikan details including pbk_jam_awal and pbk_jam_akhir
    $perbaikanQuery = "
        SELECT pbk_tanggal_awal, pbk_tanggal_akhir, pbk_hours_meter, pbk_creaby, pbk_jam_awal, pbk_jam_akhir
        FROM mmo_perbaikan
        WHERE pbk_id = ?";
    $perbaikanStmt = $conn->prepare($perbaikanQuery);
    $perbaikanStmt->bind_param("i", $pbk_id);
    $perbaikanStmt->execute();
    $perbaikanResult = $perbaikanStmt->get_result();
    $perbaikanData = $perbaikanResult->fetch_assoc();

    // Fetch pemohon name
    $PelaksanaName = '';
    $userQuery = "
        SELECT kry_id, mhs_id
        FROM sso_user
        WHERE usr_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $perbaikanData['pbk_creaby']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();

    if ($userData['kry_id']) {
        $karyawanQuery = "
            SELECT kry_nama
            FROM sso_karyawan
            WHERE kry_id = ?";
        $karyawanStmt = $conn->prepare($karyawanQuery);
        $karyawanStmt->bind_param("i", $userData['kry_id']);
        $karyawanStmt->execute();
        $karyawanResult = $karyawanStmt->get_result();
        $karyawanData = $karyawanResult->fetch_assoc();
        $PelaksanaName = $karyawanData['kry_nama'];
    } elseif ($userData['mhs_id']) {
        $mahasiswaQuery = "
            SELECT mhs_nama
            FROM sso_mahasiswa
            WHERE mhs_id = ?";
        $mahasiswaStmt = $conn->prepare($mahasiswaQuery);
        $mahasiswaStmt->bind_param("i", $userData['mhs_id']);
        $mahasiswaStmt->execute();
        $mahasiswaResult = $mahasiswaStmt->get_result();
        $mahasiswaData = $mahasiswaResult->fetch_assoc();
        $PelaksanaName = $mahasiswaData['mhs_nama'];
    }

    // Fetch schedule details
    $scheduleQuery = "
        SELECT sch.sch_id, sch.sch_nama
        FROM mmo_schedule sch
        JOIN mmo_action act ON sch.sch_id = act.sch_id
        WHERE sch.unt_id = ? AND act.act_id IN (SELECT act_id FROM mmo_save_service_action WHERE pbk_id = ?)";
    $scheduleStmt = $conn->prepare($scheduleQuery);
    $scheduleStmt->bind_param("ii", $unt_id, $pbk_id);
    $scheduleStmt->execute();
    $scheduleResult = $scheduleStmt->get_result();
    $scheduleData = $scheduleResult->fetch_assoc();

    // Fetch actions
    $actionsQuery = "
        SELECT 
            a.act_id, 
            a.act_nama, 
            a.act_foto, 
            a.act_keterangan, 
            CASE WHEN sa.result_check = 1 THEN 'check' ELSE 'uncheck' END AS result_check
        FROM 
            mmo_save_service_action sa
            JOIN mmo_action a ON sa.act_id = a.act_id
        WHERE 
            sa.pbk_id = ? AND a.sch_id = ?";
    $actionsStmt = $conn->prepare($actionsQuery);
    $actionsStmt->bind_param("ii", $pbk_id, $scheduleData['sch_id']);
    $actionsStmt->execute();
    $actionsResult = $actionsStmt->get_result();

    $actions = array();
    while ($actionRow = $actionsResult->fetch_assoc()) {
        array_push($actions, $actionRow);
    }

    $response = array(
        "unt_id" => $unitData['unt_id'],
        "unt_nama" => $unitData['unt_nama'],
        "pbk_tanggal_awal" => $perbaikanData['pbk_tanggal_awal'],
        "pbk_jam_awal" => $perbaikanData['pbk_jam_awal'],
        "pbk_tanggal_akhir" => $perbaikanData['pbk_tanggal_akhir'],
        "pbk_jam_akhir" => $perbaikanData['pbk_jam_akhir'],
        "pbk_hours_meter" => $perbaikanData['pbk_hours_meter'],
        "pbk_creaby" => $perbaikanData['pbk_creaby'],
        "nama_pelaksana" => $PelaksanaName,
        "sch_id" => $scheduleData['sch_id'],
        "sch_nama" => $scheduleData['sch_nama'],
        "actions" => $actions
    );

    echo json_encode(array('result' => 'Data found', 'data' => $response));
} else {
    echo json_encode(array('result' => 'Required parameters are missing'));
}

$conn->close();
?>
