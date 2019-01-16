<?php

	// Include config file
	require_once 'config.php';

	// add the header line to specify that the content type is JSON
	header("Content-Type: application/json; charset=UTF-8");
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	//ToDo: query the table to get all column names and create an array, restructure UPDATE and INSERT to use array

	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
		$reqdate = date("Ymd");
		if (!empty($_GET["reqdate"])) {$reqdate = htmlspecialchars($_GET["reqdate"]);} //Get reqdate for SQL SELECT
		$sqlcmd = "SELECT DISTINCT userName FROM view_EventLog WHERE event = 'Unlocked' and date(logDateTime) = '".$reqdate."'";
		$result = $link->query($sqlcmd);
		$outp = array();
		$outp = $result->fetch_all(MYSQLI_ASSOC);
		echo "DailyUsers(".json_encode($outp).")";
	}

	
	
	
		//echo "\n\r SQL: ".$sqlcmd;
?>
