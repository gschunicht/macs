<?php
	header("Content-Type: application/json; charset=UTF-8");
	$mach_id = "%";
	$user_id = "%";
	if (!empty($_GET["mach_id"])) {$mach_id = htmlspecialchars($_GET["mach_id"]);} //Get mach_id for SQL query
	if (!empty($_GET["user_id"])) {$user_id = htmlspecialchars($_GET["user_id"]);} //Get user_id for SQL query
	// Include config file
	require_once 'config.php';
	if ($mach_id == 'LastUsers') {
		$result = $link->query("SELECT b.*, a.maxID FROM view_MachLastUse AS a LEFT JOIN view_EventLog AS b ON a.maxID = b.id");
	} else {
		$result = $link->query("SELECT * FROM view_EventLog WHERE machine_id like '".$mach_id."' AND user_id like '".$user_id."' ORDER BY timestamp DESC");
	}
	//$outp = array();
	//$outp = $result->fetch_all(MYSQLI_ASSOC);

	echo "Log([";
	WHILE ($row =  mysqli_fetch_array($result, MYSQLI_ASSOC)){
		echo json_encode($row).",";
	}
	echo "])";
?>

