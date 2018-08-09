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
		$id = "%";
		$active = "%";
		if (!empty($_GET["id"])) {$id = htmlspecialchars($_GET["id"]);} //Get id for SQL SELECT
		if (!empty($_GET["active"])) {$active = htmlspecialchars($_GET["active"]);} //Get active for SQL SELECT
		$sqlcmd = "SELECT id, name, login, hash, badge_id, email, last_seen, ";
		$sqlcmd .= "CASE active WHEN 1 THEN 'true' ELSE 'false' END as active  "; //"CASE active WHEN 1 THEN 'true' ELSE 'false' END as active  
		$sqlcmd .= " FROM user ";
		if (!$id == "") {
			$sqlcmd .= " WHERE id like '".$id."'";
		}
		$sqlcmd .= " ORDER BY name ";
		$result = $link->query($sqlcmd);
		$outp = array();
		$outp = $result->fetch_all(MYSQLI_ASSOC);
		echo "Users(".json_encode($outp).")";
	}

	// handle a POST (SQL UPDATE statement)
	if ($verb == "POST") {
		if (!empty($_POST["active"])) {$active = htmlspecialchars($_POST["active"]);}
		if (!empty($_POST["name"])) {$name = htmlspecialchars($_POST["name"]);} //Get name for SQL UPDATE
		if (!empty($_POST["badge_id"])) {$badge_id = htmlspecialchars($_POST["badge_id"]);} //Get badge_id for SQL UPDATE
		if (!empty($_POST["email"])) {$email = htmlspecialchars($_POST["email"]);} //Get email for SQL UPDATE

		if (!empty($_POST["id"])) {$id = htmlspecialchars($_POST["id"]);} //Get id for SQL UPDATE
		else{
			header("HTTP/1.1 500 Internal Server Error");
			echo "No id provided, UPDATE failed.";
			echo " id: ".$id;
			echo " name: ".$name;
			echo " badge_id: ".$badge_id;
			echo " email: ".$email;
			echo " active: ".$active;	
			exit();
		}
		$sqlcmd = "UPDATE user SET ";
		$sqlcmd .= "name='".$name."', ";
		$sqlcmd .= "badge_id='".$badge_id."', ";
		$sqlcmd .= "email='".$email."' ";
		$sqlcmd .= ", active=".$active." ";
		$sqlcmd .= " WHERE id = ".$id;
		$result = $link->query($sqlcmd);
		if ($result) {echo $result;} 
		else {		
			header("HTTP/1.1 500 Internal Server Error");
			echo "User update failed.  SQL: ".$sqlcmd;
		}
	}
	// handle a PUT (SQL INSERT Statement)
	if ($verb == "PUT") {
	$result = $link->query("INSERT INTO user VALUES");
	echo "Users SQL INSERT not yet supported";
	}

	// handle a DELETE (SQL DELETE Statement)
	if ($verb == "DELETE") {
	$id = htmlspecialchars($_DELETE["id"]); //Get id for SQL query
	if ($id == "") {exit("No id provided, DELETE failed.");}
	$result = $link->query("DELETE FROM user WHERE id=".$id);
	echo "Users(".json_encode($outp).")";
	}
	
	
		//echo "\n\r SQL: ".$sqlcmd;
?>
