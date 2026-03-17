<?php
	include "../../config/config.php";
	
    $Id = $_POST['location_Id'];
	
    
    $msg = "";


	$sql = mysqli_query($conn,"DELETE FROM location WHERE location_id = '$Id'");

	if($sql){

		$msg = array("valid"=>true, "msg"=>"Delete Successfully");
		
	}else{
		$msg = array("valid"=>true, "msg"=>"Delete Unsuccessful");
		
	}
echo json_encode($msg)
?>
