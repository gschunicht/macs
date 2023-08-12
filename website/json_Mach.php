<?php
	header("Content-Type: application/json; charset=UTF-8");

	// Include SQL config file
	require_once 'config.php';
	// Include Logging Functions
	require_once 'Logging.php';
	//Set DEBUG constant
	$debug = false;
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	// Initialize the session
	session_start();
	// get logged in user id
	$login = $_SESSION['user_id'];
	//Initialize SQL command variable
	$sqlcmd = "";
	
	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
		$mach_id = "%";
		$active = "0";
		if (!empty($_GET["mach_id"])) {$mach_id = htmlspecialchars($_GET["mach_id"]);} //Get mach_id for SQL SELECT
		if (!empty($_GET["active"])) {$active = htmlspecialchars($_GET["active"]);} //Get active for SQL SELECT
		$sqlcmd = "SELECT `desc`,id, from_unixtime(last_seen) as last_seen,mach_nr, name, version, ";
		$sqlcmd .= "CASE active WHEN 1 THEN 'true' ELSE 'false' END as active  "; 
		$sqlcmd .= " FROM mach ";
		if (!$mach_id == "") {$sqlcmd .= " WHERE id like '".$mach_id."'";}
		if ($active == "1") {$sqlcmd .= "AND active = 1";		}
		$sqlcmd .= " ORDER BY name ";
		if ($debug) {echo ("\n\r SQL Statement: ".$sqlcmd);}//DEBUGGING
		$result = $link->query($sqlcmd);
		if ($debug) {
			echo ("\n\r SQL Info: ". mysqli_info($link));
			echo ("\n\r SQL Num Rows: ". mysqli_num_rows($link));
			echo ("\n\r SQL Affected Rows: ". mysqli_affected_rows($link));
			echo ("\n\r SQL Error: ". mysqli_error($link));
		}//DEBUGGING
		$outp = array();

		$outp = $result->fetch_all(MYSQLI_ASSOC);
		echo "Machines(".json_encode($outp).")";

	}

	// handle a POST (SQL UPDATE statement)
	if ($verb == "POST") {

		if (!empty($_POST["id"])) {$id = htmlspecialchars($_POST["id"]);} //Get id for SQL UPDATE
		else{
			header("HTTP/1.1 500 Internal Server Error");
			echo "No id provided, UPDATE failed.";
			echo " Desc: ".$desc;
			echo " ID: ".$id;
			echo " Last_Seen: ".$last_seen;
			echo " mach_nr: ".$mach_nr;
			echo " name: ".$name;
			echo " version: ".$version;
			echo " active: ".$active;	
			exit();
		}
		if (!empty($_POST["name"])) {//Get name for SQL UPDATE
			$name = htmlspecialchars($_POST["name"]);
			$sqlcmd .= "name='".$name."' ";
		} 
		if (!empty($_POST["desc"])) {//Get desc for SQL UPDATE
			$desc = htmlspecialchars($_POST["desc"]);
			$sqlcmd .= ", `desc`='".$desc."' ";
		}else {$sqlcmd .= ", `desc`= NULL ";}
		if (!empty($_POST["mach_nr"])) {//Get mach_nr for SQL UPDATE
			$mach_nr = htmlspecialchars($_POST["mach_nr"]);
			$sqlcmd .= ", mach_nr='".$mach_nr."' ";
		} 	
		if (!empty($_POST["active"])) {
			$active = htmlspecialchars($_POST["active"]);
			$sqlcmd .= ", active=".$active." ";
		}
		if (substr($sqlcmd,0,1) == ",") {$sqlcmd = trim($sqlcmd,",");} //Eliminates leading comma if Name is not provided
		$sqlcmd = "UPDATE mach SET ".$sqlcmd;
		$sqlcmd .= " WHERE id = ".$id." ";
		if ($debug) {echo "\n\r SQL Statement: ".$sqlcmd;}//DEBUGGING
		$result = $link->query($sqlcmd);
		
		if ($debug) {echo ("\n\r SQL Error: ". mysqli_error($link));}//DEBUGGING
		
		if (!$result OR (mysqli_affected_rows($link)<>1)) {
			echo "\n\r Machine update failed. \n\r SQL: ".$sqlcmd;
			echo "\n\r Affected Rows: ".mysqli_affected_rows($link);
			echo ("\n\r SQL Error: ". mysqli_error($link));
			//Log the activity to the database
			logEntry("0",$id,"Update Machine- Failed",$login);
		}
		else {
			echo "{}";
			//Log the activity to the database
			logEntry("0",$id,"Update Machine- Success",$login);
		}
	}
	// handle a PUT (SQL INSERT Statement)
	if ($verb == "PUT") {
		
		parse_str(file_get_contents("php://input"),$post_vars);
		$sqlcmd .= "(true,'";	//Set Active to TRUE since this is a new machine
		if (!empty($post_vars["desc"])) {//Get name for SQL UPDATE
			$desc = htmlspecialchars($post_vars["desc"]);
			$sqlcmd .= $desc;
		} 
		$sqlcmd .= "',";
		if (!empty($post_vars["mach_nr"])) {//Get machine_nr for SQL UPDATE
			$machine_nr = htmlspecialchars($post_vars["mach_nr"]);
			$sqlcmd .= $machine_nr;
		}
		$sqlcmd .= ",'";
		if (!empty($post_vars["name"])) {//Get name for SQL UPDATE
			$name = htmlspecialchars($post_vars["name"]);
			$sqlcmd .= $name;
		}
		$sqlcmd .= "',UNIX_TIMESTAMP(NOW()))"; 
		$sqlcmd = "INSERT INTO mach (active, `desc`, mach_nr, name, last_seen) VALUES ".$sqlcmd;
		if ($debug) {echo ("\n\r SQL Statement: ".$sqlcmd);}//DEBUGGING
		$result = $link->query($sqlcmd);
		$mach_id = mysqli_insert_id($link);
		if ($debug) {echo ("\n\r SQL Error: ". mysqli_error($link));}//DEBUGGING
		
		if (!$result OR (mysqli_affected_rows($link)<>1)) {
			echo "\n\r User insert failed. \n\r SQL: ".$sqlcmd;
			echo "\n\r Affected Rows: ".mysqli_affected_rows($link);
			echo ("\n\r SQL Error: ". mysqli_error($link));
			//Log the activity to the database
			logEntry("0","0","Insert Machine [".$name."] - Failed",$login);
		}
		else {
			$sqlcmd = "SELECT `desc`,id, from_unixtime(last_seen) as last_seen,mach_nr, name, version, ";
			$sqlcmd .= "CASE active WHEN 1 THEN 'true' ELSE 'false' END as active  "; 
			$sqlcmd .= " FROM mach  WHERE id like '".$mach_id."'";
			$result = $link->query($sqlcmd);
			$outp = array();

			$outp = $result->fetch_all(MYSQLI_ASSOC);
			echo "Machines(".json_encode($outp).")";
			
			//Log the activity to the database
			logEntry("0","0","Insert Machine [".$name."] - Success",$login);
		}
	}
?>