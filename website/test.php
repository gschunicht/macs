<?php
// Initialize the session
session_start();
// prints e.g. 'Current PHP version: 4.1.1'
echo 'Current PHP version: ' . phpversion();

// prints e.g. '2.0' or nothing if the extension isn't enabled
echo phpversion('tidy')."  end Tidy PHP Version Info";

echo '<pre>';
var_dump($_SESSION);
echo '</pre>';

// Include config file
require_once 'config.php';
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Get mach_nr value
    $mach_nr = trim($_POST["mach_nr"]);
    // Get badge_id value
    $badge_id = trim($_POST["badge_id"]);
    // Get action value
    $action = trim($_POST["action"]);
     
 
    // Prepare a select statement
    $sql = "CALL sp_LogCardUse('".$badge_id."',".$action.",".$mach_nr.")";
	echo $sql;
    $result = $link->query($sql); 
    //mysqli_prepare($link, $sql)
	//mysqli_stmt_execute($sql)
	
	// Close statement
	//mysqli_stmt_close($sql);
     
    // Close connection
    //mysqli_close($link);
}
?>

<?php var_export($_SERVER)?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	<title>MACS-Test</title>
	<link rel="icon" href="images/MB_Favicon.png">
	<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.common.min.css" />
	<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.materialblack.min.css" />
	<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.default.mobile.min.css" />
	<link rel="stylesheet" href="css/macs.php" type="text/css"/>

	<script src="//kendo.cdn.telerik.com/2018.2.516/js/jquery.min.js"></script>
	<script src="//kendo.cdn.telerik.com/2018.2.516/js/kendo.all.min.js"></script>
	<script src="js/macs.js"></script>
	<script>
		$(document).ready(function () {getMachList();}); //Build the Kendo datasource object
		function getMachList(){  //populates the drop down list for adding machine access.
		
				$("#MachList").kendoDropDownList({
							dataTextField: "name", //What the user sees in the list
							dataValueField: "id", //What the software uses to actually link
							dataSource: {
								transport: {
									read: {
										dataType: "jsonp",
										url: "json_Mach.php?active=1",
										jsonpCallback: 'Machines'
									}
								}
							},
							optionLabel: "Select Machine..."
						});
							
			}
	</script>
</head>
<body>
    <div class="wrapper">
        <h2>Machine Access Simulator</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="text" name="mach_nr" placeholder="Machine NR" class="text" value="9">
			<!--<select id="MachList" name="mach_nr"></select>-->
            <input type="text" name="badge_id" placeholder="Badge number..." class="text">
            <div class="form-group">
				<ul class="fieldlist">
					<li>
					  <input type="radio" name="action" value="-1" id="cardDeny" class="k-radio" checked="checked">
					  <label class="k-radio-label" for="cardDeny">Deny (-1)</label>
					</li>
					<li>
					  <input type="radio" name="action" value="0" id="cardLock" class="k-radio">
					  <label class="k-radio-label" for="cardLock">Lock (0)</label>
					</li>
					<li>
					  <input type="radio" name="action" value="1" id="cardUnlock" class="k-radio">
					  <label class="k-radio-label" for="cardUnlock">Unlock (1)</label>
					</li>
				</ul>
			</div>
                <input type="submit" class="k-button " value="Submit">
            </div>
        </form>
    </div>    
    <style>
        .fieldlist {
            margin: 0 0 -1em;
            padding: 0;
        }

        .fieldlist li {
            list-style: none;
            padding-bottom: 1em;
        }
    </style>
</body>
</html>