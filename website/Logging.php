<?php
	// Include config file
	//require_once 'config.php';

	function logEntry($user_id = 0, $machine_id = 0, $eventTxt = "undefined", $login_id = 0){
	
		/* Attempt to connect to MySQL database */
		$linkLog = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
		$sqlcmd = "INSERT INTO log (timestamp,user_id,machine_id,event,login_id, `usage`) ";
		$sqlcmd .= " VALUES (UNIX_TIMESTAMP(NOW()), ";		
		$sqlcmd .= $user_id.", ".$machine_id.", '".$eventTxt."', ".$login_id.", 0)";
		
		$result = $linkLog->query($sqlcmd); //Execute the SQL command

	}
?>