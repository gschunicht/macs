<?php
require_once('db_con.php');
if(isset($_GET["mach_nr"])){
	$machine_known=0;
	$updated=0;
	$stmt = $db->prepare("SELECT COUNT(*) FROM mach where mach_nr=:id");
	$stmt->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
	$stmt->execute();
        foreach($stmt as $row){
		if($row["COUNT(*)"]>0){
			$machine_known=1;
		}
	}

	if($machine_known==0) {
		// unknown machine
		if(!is_numeric($_GET["mach_nr"])){
			$_GET["mach_nr"]=999;
		};
		$stmt2 = $db->prepare("INSERT INTO `macs`.`log` (`id`, `timestamp`, `user_id`, `machine_id`, `event`) VALUES ('', '".time()."', '0', '', 'Unknown Station ".$_GET["mach_nr"]." connected')");
		$stmt2->execute();
	};

	if($machine_known==1 && isset($_GET['tag']) ){
		if($_GET['tag']>='1' ){
			//$stmt = $db->prepare("SELECT badge_id FROM `user` WHERE active=1 and badge_id=:badgeid and id in (select user_id from access where mach_id=(select id from mach where mach_nr=:id ))");
			$stmt = $db->prepare("SELECT DISTINCT CAST(badge_id as DECIMAL) as badge_num  FROM access   Join  user on access.user_id=user.id  Join mach on access.mach_id=mach.id  WHERE mach.mach_nr = :id and user.active=1 and CAST(badge_id as DECIMAL)=:badgeid order by badge_num");
			$stmt->bindParam(":badgeid",$_GET["tag"],PDO::PARAM_INT);
			$stmt->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);

			$stmt->execute();
			$csv="";
	        foreach($stmt as $row){
				$csv.=intval($row["badge_num"],10).",";
				$updated=1;
			};
			if($updated==0){
				echo "na";
			}
			else{
				echo $csv;
			};
		}
	}
	else if($machine_known==1 && $updated ==0){
		$stmt = $db->prepare("SELECT badge_id FROM `user` WHERE active=1 and id in (select user_id from access where mach_id=(select id from mach where mach_nr=:id))");
	//	$stmt = $db->prepare("SELECT DISTINCT CAST(badge_id as DECIMAL) as badge_num  FROM access   Join  user on access.user_id=user.id  Join mach on access.mach_id=mach.id  WHERE mach.mach_nr = :id and user.active=1 order by badge_num");
		$stmt->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
		$stmt->execute();
		$csv="";
	        foreach($stmt as $row){
			$csv.=intval($row["badge_id"],10).",";
		};
		//echo $csv;
		// update last seen
		$stmt = $db->prepare("UPDATE `mach` SET `last_seen`=".time().", `version`=:v WHERE mach_nr=:id");
		$stmt->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
		$stmt->bindParam(":v",$_GET["v"],PDO::PARAM_INT);
		$stmt->execute();
		// check if we should create a log entry for this
		$stmt = $db->prepare("SELECT COUNT(*) FROM `update_available` WHERE mach_id in (select id from mach where mach_nr=:id)");
		$stmt->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
		$stmt->execute();

	    foreach($stmt as $row){
			if($row["COUNT(*)"]>0){
				$stmt2 = $db->prepare("INSERT INTO `macs`.`log` (`id`, `timestamp`, `user_id`, `machine_id`, `event`) VALUES ('', '".time()."', '0', (SELECT `id` FROM `mach` WHERE mach_nr=:id), 'Station updated')");
				$stmt2->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
				$stmt2->execute();
				$stmt3 = $db->prepare("DELETE FROM `update_available` WHERE mach_id in (select id from mach where mach_nr=:id)");
				$stmt3->bindParam(":id",$_GET["mach_nr"],PDO::PARAM_INT);
				$stmt3->execute();
				echo $csv;
				$updated=1;
				break;
			};
		};
		if(isset($_GET['forced'])){
			if($_GET['forced']=='1'){
				echo $csv;
				$updated=1;
			}
		}
		if($updated==0){
			echo "nu";
		};
	} 
};
?>