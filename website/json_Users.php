<?php

	// Include config file
	require_once 'config.php';
	// Include Logging Functions
	require_once 'Logging.php';
	// Set Debug constant
	$debug = false;
	// add the header line to specify that the content type is JSON
	header("Content-Type: application/json; charset=UTF-8");
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	// Initialize the session
	session_start();
	// get logged in user id
	$login = $_SESSION['user_id'];
	//ToDo: query the table to get all column names and create an array, restructure UPDATE and INSERT to use array

	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
		$id = "%";
		$active = "%";
		if (!empty($_GET["id"])) {$id = htmlspecialchars($_GET["id"]);} //Get id for SQL SELECT
		if (!empty($_GET["active"])) {$active = htmlspecialchars($_GET["active"]);} //Get active for SQL SELECT
		$sqlcmd = "SELECT id, name, login, badge_id, email, last_seen, ";
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
		if ($debug) {echo ("SQL Statement: ".$sqlcmd);}//DEBUGGING
		if ($debug) {echo ("SQL Error: ". mysqli_error($link));}//DEBUGGING
	}

	// handle a POST (SQL UPDATE statement)
	if ($verb == "POST") {

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
		if (!empty($_POST["name"])) {//Get name for SQL UPDATE
			$name = htmlspecialchars($_POST["name"]);
			$sqlcmd .= "name='".$name."' ";
		} 
		if (!empty($_POST["email"])) {//Get email for SQL UPDATE
			$email = htmlspecialchars($_POST["email"]);
			$sqlcmd .= ", email='".$email."' ";
		}
		if (!empty($_POST["badge_id"])) {//Get badge_id for SQL UPDATE
			$badge_id = htmlspecialchars($_POST["badge_id"]);
			$sqlcmd .= ", badge_id='".$badge_id."' ";
		} 	
		if (!empty($_POST["active"])) {
			$active = htmlspecialchars($_POST["active"]);
			$sqlcmd .= ", active=".$active." ";
		}
		if (!empty($_POST["login"])) {//Get login for SQL UPDATE
			$login = htmlspecialchars($_POST["login"]);
			$sqlcmd .= ", login='".$login."' ";	
		}
		if (!empty($_POST["hash"])) {//Get hash for SQL UPDATE
			$hash = htmlspecialchars($_POST["hash"]);
			if (strlen($hash) > 5)$sqlcmd .= ", hash='".md5($hash)."'";
		} /**/
		if (substr($sqlcmd,0,1) == ",") {$sqlcmd = trim($sqlcmd,",");} //Eliminates leading comma if Name is not provided
		$sqlcmd = "UPDATE user SET ".$sqlcmd;
		$sqlcmd .= " WHERE id = ".$id." ";
		if ($debug) {echo "\n\r SQL Statement: ".$sqlcmd;}//DEBUGGING
		$result = $link->query($sqlcmd);
		
		if ($debug) {echo ("\n\r SQL Error: ". mysqli_error($link));}//DEBUGGING
		
		if (!$result OR (mysqli_affected_rows($link)<>1)) {
			echo "\n\r User update failed. \n\r SQL: ".$sqlcmd;
			echo "\n\r Affected Rows: ".mysqli_affected_rows($link);
			echo ("\n\r SQL Error: ". mysqli_error($link));
			//Log the activity to the database
			logEntry("0",$id,"Update User - Failed",$login);
		}
		else {
			echo "{}";
			
			//Log the activity to the database
			logEntry($id,"0","Update User - Success",$login);
		}
	}
	// handle a PUT (SQL INSERT Statement)
	if ($verb == "PUT") {
		
		parse_str(file_get_contents("php://input"),$post_vars);
		$sqlcmd .= "('";	
		if (!empty($post_vars["name"])) {//Get name for SQL UPDATE
			$name = htmlspecialchars($post_vars["name"]);
			$sqlcmd .= $name;
		} 
		$sqlcmd .= "','";
		if (!empty($post_vars["email"])) {//Get email for SQL UPDATE
			$email = htmlspecialchars($post_vars["email"]);
			$sqlcmd .= $email;
		}
		$sqlcmd .= "','";
		if (!empty($post_vars["badge_id"])) {//Get badge_id for SQL UPDATE
			$badge_id = htmlspecialchars($post_vars["badge_id"]);
			$sqlcmd .= $badge_id;
		} 	
		$sqlcmd .= "',1,'"; //Since this is a new user, force status to active
		if (!empty($post_vars["login"])) {//Get login for SQL UPDATE
			$login = htmlspecialchars($post_vars["login"]);
			$sqlcmd .= $login;	
		}
		$sqlcmd .= "','";
		if (!empty($post_vars["hash"])) {//Get hash for SQL UPDATE
			$hash = htmlspecialchars($post_vars["hash"]);
			$sqlcmd .=md5($hash);
		}
		$sqlcmd .= "',0)"; 
		$sqlcmd = "INSERT INTO user (name,email,badge_id,active, login, hash, last_seen)VALUES ".$sqlcmd;
		if ($debug) {echo "\n\r SQL Statement: ".$sqlcmd;}//DEBUGGING
		$result = $link->query($sqlcmd);
		$id = mysqli_insert_id($link);
		if ($debug) {echo ("\n\r SQL Error: ". mysqli_error($link));}//DEBUGGING
		
		if (!$result OR (mysqli_affected_rows($link)<>1)) {
			echo "\n\r User insert failed. \n\r SQL: ".$sqlcmd;
			echo "\n\r Affected Rows: ".mysqli_affected_rows($link);
			echo ("\n\r SQL Error: ". mysqli_error($link));
			//Log the activity to the database
			logEntry("0","0","Insert User [".$name."] - Failed",$login);
		}
		else {
			//echo mysqli_insert_id();
			$sqlcmd = "SELECT id, name, login, badge_id, email, last_seen, ";
			$sqlcmd .= "CASE active WHEN 1 THEN 'true' ELSE 'false' END as active  "; 
			$sqlcmd .= " FROM user WHERE id like '".$id."'";
			$result = $link->query($sqlcmd);
			$outp = array();
			$outp = $result->fetch_all(MYSQLI_ASSOC);
			echo "Users(".json_encode($outp).")";
			//Log the activity to the database
			logEntry("0","0","Insert User [".$name."] - Success",$login);
		}
	}

	// handle a DELETE (SQL DELETE Statement)
	if ($verb == "DELETE") {
	$id = htmlspecialchars($_DELETE["id"]); //Get id for SQL query
	if ($id == "") {exit("No id provided, DELETE failed.");}
	//$result = $link->query("DELETE FROM user WHERE id=".$id);
	echo "Users(".json_encode($outp).")";
	}
	
	
		//echo "\n\r SQL: ".$sqlcmd;
?>
