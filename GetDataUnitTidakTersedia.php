<?php
     
    require_once('koneksi.php');
    
    $select = mysqli_query($conn, "select unt_id as id, unt_nama as nama, unt_hours_meter as hoursmeter, unt_foto as foto, unt_status as status FROM `mmo_unit` where unt_status = '2' ");
    $result = array();
    while($row = mysqli_fetch_array($select)){
        //Pushing data in the blank array created
        array_push($result,array(
            "unt_id"=>$row['id'],
            "unt_nama"=>$row['nama'],
            "unt_hours_meter"=>$row['hoursmeter'],
            "unt_foto"=>$row['foto'],
            "unt_status"=>$row['status']
        ));
    }
    //Displaying the array in json format
    echo json_encode(array('result'=>$result));
    //mysqli_close($con);
    
?>