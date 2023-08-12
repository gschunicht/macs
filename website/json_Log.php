<?php
	header("Content-Type: application/json; charset=UTF-8");

	// Include config file
	require_once 'config.php';
	//Set DEBUG constant
	$debug = false;
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	$mach_id = "%";
	$user_id = "%";
	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
		if (!empty($_GET["mach_id"])) {$mach_id = htmlspecialchars($_GET["mach_id"]);} //Get mach_id for SQL query
		if (!empty($_GET["user_id"])) {$user_id = htmlspecialchars($_GET["user_id"]);} //Get user_id for SQL query
		// Include config file
		require_once 'config.php';
		if ($mach_id == 'LastUsers') {
			$result = $link->query("SELECT b.*, a.maxID FROM view_MachLastUse AS a LEFT JOIN view_EventLog AS b ON a.maxID = b.id");
		} else {
			$result = $link->query("SELECT * FROM view_EventLog WHERE machine_id like '".$mach_id."' AND user_id like '".$user_id."' ORDER BY timestamp DESC");
		}
		$outp = array();
		$outp = $result->fetch_all(MYSQLI_ASSOC);

		echo "Log(".json_encode($outp).")";
	}
	// handle a PUT (SQL INSERT Statement)
	if ($verb == "PUT") {
		parse_str(file_get_contents("php://input"),$post_vars);
		
		$sqlcmd = "INSERT INTO log (timestamp,user_id,machine_id,event,login_id, `usage`) VALUES (UNIX_TIMESTAMP(NOW()), ";// Start the SQL insert statement, set the timestampt to now.
		if (!empty($post_vars["user_id"])) {//Get user_id for SQL UPDATE
			$sqlcmd .= htmlspecialchars($post_vars["user_id"]);
		}else{	$sqlcmd .= "0";}
		$sqlcmd .= ", ";// SQL field separator
		if (!empty($post_vars["machine_id"])) {//Get machine_id for SQL UPDATE
			$sqlcmd .= htmlspecialchars($post_vars["machine_id"]);
		}else{	$sqlcmd .= "0";}
		$sqlcmd .= ", ";// SQL field separator
		if (!empty($post_vars["event"])) {//Get event for SQL UPDATE
			$sqlcmd .= "'".htmlspecialchars($post_vars["event"])."'";
		}else{	$sqlcmd .= "''";}
		$sqlcmd .= ", ";// SQL field separator
		if (!empty($post_vars["login_id"])) {//Get login_id for SQL UPDATE
			$sqlcmd .= htmlspecialchars($post_vars["login_id"]);
		}else{	$sqlcmd .= "0";}
		$sqlcmd .= ", 0)";// Set 0 for usage field
		
		if ($debug) {echo ("\n\r SQL Statement: ".$sqlcmd);}//DEBUGGING
		$result = $link->query($sqlcmd); //Execute the SQL command
		if ($debug) {echo ("\n\r SQL Error: ". mysqli_error($link));}//DEBUGGING
		
		if (!$result OR (mysqli_affected_rows($link)<>1)) {
			echo "\n\r User insert failed. \n\r SQL: ".$sqlcmd;
			echo "\n\r Affected Rows: ".mysqli_affected_rows($link);
			echo ("\n\r SQL Error: ". mysqli_error($link));
		}
		else {echo "{}";}
		
	}
?>

