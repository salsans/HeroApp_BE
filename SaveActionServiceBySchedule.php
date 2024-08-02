<?php
require_once('koneksi.php');

date_default_timezone_set('Asia/Jakarta');

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['sch_id']) && isset($data['actions']) && is_array($data['actions'])) {
    $sch_id = $data['sch_id'];
    
    // Fetch unt_id and pbk_id from mmo_perbaikan where sch_id and pbk_jenis = 2
    $perbaikanQuery = "
    SELECT unt_id, pbk_id 
    FROM mmo_perbaikan 
    WHERE unt_id IN (SELECT unt_id FROM mmo_schedule WHERE sch_id = ?) 
      AND pbk_jenis = 2 
    ORDER BY pbk_id DESC 
    LIMIT 1";
    $perbaikanStmt = $conn->prepare($perbaikanQuery);
    $perbaikanStmt->bind_param("s", $sch_id);
    $perbaikanStmt->execute();
    $perbaikanResult = $perbaikanStmt->get_result();

    if ($perbaikanResult->num_rows > 0) {
        $perbaikanRow = $perbaikanResult->fetch_assoc();
        $unt_id = $perbaikanRow['unt_id'];
        $pbk_id = $perbaikanRow['pbk_id'];

        $response = array();
        $query = "INSERT INTO mmo_save_service_action (act_id, sch_id, pbk_id, result_check) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        foreach ($data['actions'] as $action) {
            if (isset($action['act_id']) && isset($action['result_check'])) {
                $act_id = $action['act_id'];
                $result_check = $action['result_check'];

                $stmt->bind_param("ssis", $act_id, $sch_id, $pbk_id, $result_check);

                if ($stmt->execute()) {
                    // Fetch the related data
                    $fetchQuery = "
                        SELECT 
                            a.act_id, 
                            a.act_nama, 
                            s.sch_id, 
                            s.sch_nama, 
                            u.unt_id, 
                            u.unt_nama, 
                            CASE WHEN sa.result_check = 1 THEN 'check' ELSE 'uncheck' END AS result_check
                        FROM 
                            mmo_save_service_action sa
                            JOIN mmo_action a ON sa.act_id = a.act_id
                            JOIN mmo_schedule s ON sa.sch_id = s.sch_id
                            JOIN mmo_unit u ON s.unt_id = u.unt_id
                        WHERE 
                            sa.act_id = ? AND sa.sch_id = ? AND sa.pbk_id = ?
                        ORDER BY 
                            sa.dltsave_service_action_id DESC 
                        LIMIT 1";
                    
                    $fetchStmt = $conn->prepare($fetchQuery);
                    $fetchStmt->bind_param("sss", $act_id, $sch_id, $pbk_id);
                    $fetchStmt->execute();
                    $result = $fetchStmt->get_result();
                    $savedData = $result->fetch_assoc();

                    $savedData['message'] = 'Data berhasil disimpan';
                    array_push($response, $savedData);

                    $fetchStmt->close();
                } else {
                    array_push($response, array('result' => 'Gagal menyimpan data ' . $act_id));
                }
            } else {
                array_push($response, array('result' => 'Act_id atau result_check tidak lengkap'));
            }
        }

        // Update the status of the unit
        $updateUnitQuery = "UPDATE mmo_unit SET unt_status = 1 WHERE unt_id = ?";
        $updateStmt = $conn->prepare($updateUnitQuery);
        $updateStmt->bind_param("s", $unt_id);
        if ($updateStmt->execute()) {
            array_push($response, array('message' => 'Status unit kini kembali tersedia'));
        } else {
            array_push($response, array('result' => 'Gagal Mengubah Status Unit'));
        }

        // Update pbk_tanggal_akhir and pbk_jam_akhir in mmo_perbaikan
        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");
        $updatePerbaikanQuery = "
            UPDATE mmo_perbaikan 
            SET pbk_tanggal_akhir = ?, pbk_jam_akhir = ? 
            WHERE pbk_id = ?";
        $updatePerbaikanStmt = $conn->prepare($updatePerbaikanQuery);
        $updatePerbaikanStmt->bind_param("ssi", $currentDate, $currentTime, $pbk_id);
        if ($updatePerbaikanStmt->execute()) {
            array_push($response, array('message' => 'Tanggal dan jam akhir perbaikan berhasil diperbarui'));
        } else {
            array_push($response, array('result' => 'Gagal memperbarui tanggal dan jam akhir perbaikan'));
        }

        $stmt->close();
        $updateStmt->close();
        $updatePerbaikanStmt->close();
        echo json_encode(array('result' => 'Data processed', 'data' => $response));
    } else {
        echo json_encode(array('result' => 'Tidak Ditemukan Jadwal Service Untuk Unit Ini'));
    }

    $perbaikanStmt->close();
} else {
    echo json_encode(array('result' => 'Required parameters are missing'));
}

$conn->close();
?>
