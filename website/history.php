<?php
	// Include config file
	require_once 'config.php';
	// Include Logging Functions
	require_once 'Logging.php';
	//Set DEBUG constant
	$debug = false;
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	// handle a GET (SQL SELECT Statement)
	if ($verb == "GET") {
		if(isset($_GET["logme"]) && !empty($_GET["event"]) && (isset($_GET["mach_nr"]) || isset($_GET["badge"]))){
			$event=$_GET["event"];
			if(isset($_GET["timeopen"])){
				$time=$_GET["timeopen"];
			} else {
				$time="0";
			};
			if(!isset($_GET["mach_nr"])){
				$mach="0";
			} else {
				$result = $link->query("SELECT `id` FROM  `macs`.`mach` WHERE mach_nr=".$_GET["mach_nr"]);
				foreach($result as $row){
					$mach=$row["id"];
				};
				if($mach==""){
					$event.=" (mach_nr:".$_GET["mach_nr"]." is unknown)";
					$mach="0";
				};
			}
			if(!isset($_GET["badge"])){
				$user="0";
			} else {
				$user="";
				$result = $link->query("SELECT `id` FROM  `macs`.`user` WHERE `badge_id` != '' AND CAST(badge_id as DECIMAL)=".$_GET["badge"]);
				foreach($result as $row){
					$user=$row["id"];
				};
				if($user==""){
					$event.=" (badge#:".$_GET["badge"]." is unknown)";
					$user="0";
				} else {
					$sqlcmd = "UPDATE `macs`.`user` set `last_seen` = UNIX_TIMESTAMP(NOW()) where `id` =".$row["id"] ;
				
					$result = $link->query($sqlcmd); //Execute the SQL command
				};
			}
			//Log the activity to the database
			logEntry($user,$mach,$event,$time);
		}
		else {echo "Invalid query";}
	}
	

?>