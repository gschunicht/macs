<?php
	// Include config file
	require_once 'config.php';

	// add the header line to specify that the content type is JSON
	header("Content-Type: application/json; charset=UTF-8");
	
	// determine the request type
	$verb = $_SERVER["REQUEST_METHOD"];
	
	// get URI parameters


	if ($verb == "GET") {
		$mach_id = "%";
		$user_id = "%";
		if (!empty($_GET["mach_id"])) {$mach_id = htmlspecialchars($_GET["mach_id"]);} //Get mach_id for SQL SELECT
		if (!empty($_GET["user_id"])) {$user_id = htmlspecialchars($_GET["user_id"]);} //Get user_id for SQL SELECT
		$result = $link->query("SELECT * FROM view_Access WHERE user_id like '".$user_id."' AND mach_id like '".$mach_id."' ORDER BY userName, machName ");
		$outp = array();
		$outp = $result->fetch_all(MYSQLI_ASSOC);
		echo "Access(".json_encode($outp).")";
	}
	if ($verb == "DELETE") {	// handle a DELETE (SQL DELETE statement)
		parse_str(file_get_contents("php://input"),$post_vars);
		if (!empty($post_vars["user_id"])) {
			$id = htmlspecialchars($post_vars["id"]);//Get id for SQL UPDATE
			$result = $link->query("DELETE FROM access WHERE id = ".$id);
			echo $result;
		} 
		else{
			header("HTTP/1.1 500 Internal Server Error");
			echo "No id provided, UPDATE failed.";
			echo " id: ".$id;
		}
	}
	if ($verb == "PUT") {	// handle an INSERT (SQL INSERT statement)
		parse_str(file_get_contents("php://input"),$post_vars);
		if (!empty($post_vars["user_id"])) {
			if (!empty($post_vars["mach_id"])) {
				$user_id = htmlspecialchars($post_vars["user_id"]);//Get user_id for SQL UPDATE
				$mach_id = htmlspecialchars($post_vars["mach_id"]);//Get mach_id for SQL UPDATE
				$result = $link->query("INSERT INTO access (user_id, mach_id) VALUES(".$user_id.", ".$mach_id.") ");
				echo $user_id;
			}
		} 
		else{
			header("HTTP/1.1 500 Internal Server Error");
			echo "No id provided, UPDATE failed.";
			echo " id: ".$id;
		}
	}
?>