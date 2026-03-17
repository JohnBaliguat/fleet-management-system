<?php
	include "../../config/config.php";
	
    $Id = $_POST['user_Id'];
	
    
    $msg = "";


	$sql = mysqli_query($conn,"DELETE FROM user WHERE user_id = '$Id'");

	if($sql){

		$msg = array("valid"=>true, "msg"=>"Delete Successfully");
		
	}else{
		$msg = array("valid"=>true, "msg"=>"Delete Unsuccessful");
		
	}
echo json_encode($msg)
?>
