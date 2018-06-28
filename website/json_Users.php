<?php

	// Include config file
	require_once 'config.php';

	// add the header line to specify that the content type is JSON
	header("Content-Type: application/json; charset=UTF-8");
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	$user_id = "%";
	$active = "%";
	if (!empty($_GET["user_id"])) {$user_id = htmlspecialchars($_GET["user_id"]);} //Get user_id for SQL query
	if (!empty($_GET["active"])) {$active = htmlspecialchars($_GET["active"]);} //Get active for SQL query



	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
	if ($user_id == "") {$result = $link->query("SELECT * FROM user ");}
	else $result = $link->query("SELECT * FROM user WHERE id like '".$user_id."'");
	//$outp = array();
	//$outp = $result->fetch_all(MYSQLI_ASSOC);
	echo "Users([";
	WHILE ($row =  mysqli_fetch_array($result, MYSQLI_ASSOC)){
		echo json_encode($row).",";
	}
	echo "])";
	}

	// handle a POST (SQL UPDATE statement)
	if ($verb == "POST") {
	$user_id = htmlspecialchars($_POST["user_id"]); //Get user_id for SQL query
	if ($user_id == "") {exit("No user_id provided, UPDATE failed.");}
	$outp = array();
	$result = $link->query("SELECT * FROM user");
	$outp = $result->fetch_all(MYSQLI_ASSOC);
	echo "Users(".json_encode($outp).")";
	}

	// handle a PUT (SQL INSERT Statement)
	if ($verb == "PUT") {
	$result = $link->query("INSERT INTO user VALUES");
	echo "Users(".json_encode($outp).")";
	}

	// handle a DELETE (SQL DELETE Statement)
	if ($verb == "DELETE") {
	$user_id = htmlspecialchars($_DELETE["user_id"]); //Get user_id for SQL query
	if ($user_id == "") {exit("No user_id provided, DELETE failed.");}
	$result = $link->query("DELETE FROM user WHERE id=".$user_id);
	echo "Users(".json_encode($outp).")";
	}
  
  
?>
