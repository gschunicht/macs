<?php
	header("Content-Type: application/json; charset=UTF-8");
	$mach_id = "%";
	$user_id = "%";
	if (!empty($_GET["mach_id"])) {$mach_id = htmlspecialchars($_GET["mach_id"]);} //Get mach_id for SQL query
	if (!empty($_GET["user_id"])) {$user_id = htmlspecialchars($_GET["user_id"]);} //Get user_id for SQL query

	// Include config file
	require_once 'config.php';
	$result = $link->query("SELECT * FROM view_Access WHERE user_id like '".$user_id."' AND mach_id like '".$mach_id."'");

	echo "Access([";	
	WHILE ($row =  mysqli_fetch_array($result, MYSQLI_ASSOC)){
		echo json_encode($row).",";
	}
	echo "])";
?>