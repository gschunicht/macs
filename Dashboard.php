<!DOCTYPE html>
<?php
// Initialize the session
session_start();
 
// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
}
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");
error_log( "Php Errors!" );
?>
<html>
	<head>
		<title>MACS-Floorplan (<?php echo $_SESSION['username'] ?>)</title>
		<link rel="icon" href="images/MB_Favicon.png">
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.common.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.materialblack.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.default.mobile.min.css" />
		<link rel="stylesheet" href="css/macs.php"></script>

		<script src="//kendo.cdn.telerik.com/2018.2.516/js/jquery.min.js"></script>
		<script src="//kendo.cdn.telerik.com/2018.2.516/js/kendo.all.min.js"></script>
		<script src="js/macs.js"></script>
		

		<script type="text/x-kendo-template" id="rowTemplate">
			#if(event == 'Unlocked'){#
				<tr class="Unlocked" data-uid="#: uid #">
			#}else{#
				<tr data-uid="#: uid #">
			#}#
				<td>#=kendo.format("{0:ddd MM/dd h:mm tt}",logDateTime)#</td>
				<td>#: userName #</td>
				<td>#: machName #</td>
				<td>#: event # for 
				#
				var a = new Date();
				var b = new Date(logDateTime);
				var e = sformat((a-b)*0.001);
				#
				#= e #
				</td> 
				
			</tr>
		</script>
		<script>
			var refRate = 5;
			$(document).ready(function () {
				setupMenu();
				showMachLog();
				var ds = $(".AutoRefresh").data("kendoGrid").dataSource;
								
								
				$("#btnRefRate").kendoButton({
					icon: "refresh",
					click: function () {showMachLog();}
				});
				$("#btnRefRate").show();
				$("#refRate").show();
	
				$("#btnUsers").kendoButton({
					icon: "user",
					click: function () {location.href = "UserGrid.php";}
				});
				$("#btnMachines").kendoButton({
					icon: "gears",
					click: function () {location.href = "MachGrid.php";}
				});
				$("#btnLog").kendoButton({
					icon: "clock",
					click: function () {location.href = "LogGrid.php";}
				});
				$("#btnLogOut").kendoButton({
					icon: "logout",
					click: function () {location.href = "logout.php";}
				});
				var interval = setInterval(function (){ds.read();},(refRate *1000));
				$("#refRate").kendoNumericTextBox({
					restrictDecimals: true,
					change:	 function () {
						refRate = this.value();
						clearInterval(interval);
						interval = setInterval(function (){ds.read();},(refRate *1000));
					}
				});
			});
			
			function sformat(s) {
				var eDays = Math.floor(s / 60 / 60 / 24);
				if (eDays > 1){
					return eDays + " days ";
				} else if (eDays == 1){
					return eDays + " day ";
				} else
				  var fm = [
						//Math.floor(s / 60 / 60 / 24), // DAYS
						Math.floor(s / 60 / 60) % 24, // HOURS
						Math.floor(s / 60) % 60, // MINUTES
						//Math.round (s % 60) // SECONDS
				  ];
				  return $.map(fm, function(v, i) { return ((v < 10) ? '0' : '') + v; }).join(':') + ' (hrs:mins)';
			}

			function showMachLog () {
				var MachLogDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Log.php?mach_id=LastUsers",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Log'
						}
					},
					schema: {
                        model: {
                            fields: {
                                logDateTime: { type: "date" },
                                userName: { type: "string" },
                                machName: { type: "string" },
                                event: { type: "string" },
                                usage: { type: "string" },
                                logonName: { type: "string" }
                            }
                        }
                    },
					change: function() {
						updateFloorplan(this.data());
					}
				});	
				
				
				$("#gridMachLog").kendoGrid({
					dataSource: MachLogDataSource,
					width: 300,
					sortable: true,
					reorderable: true,
					rowTemplate: kendo.template($("#rowTemplate").html()),
					columns: [{
						field: "logDateTime",
						title: "Date/Time",
						format: "{0:ddd MM/dd h:mm tt}",
						width: 40
						}, {
						field: "userName",
						title: "User Name",
						width: 30
						}, {
						field: "machName",
						title: "Machine Name",
						width: 30
						}, {
						field: "event",
						//field: function (){return this.event:},
						title: "Event",
						sortable: {
							initialDirection: "desc"  
						},
						width: 40
						}]
				});
			
				$("#gridMachLog").show();
				//get reference to the Grid widget
				var grid = $("#gridMachLog").data("kendoGrid");
				//sort Grid's dataSource
				grid.dataSource.sort({field: "event", dir: "desc"});
			}
		
			function updateFloorplan (machData){
				for (var i = 0; i < machData.length; i++) {
					var machineID = "#M" + machData[i].machine_id;
					$(machineID).prop('title',machData[i].userName);
					if (machData[i].event == 'Unlocked') {
						$(machineID).addClass('Unlocked');
					} else if (machData[i].event == 'Locked') {
						$(machineID).addClass('Locked');
					} else {
						$(machineID).addClass('Unknown');
					}
				}
			}
		</script>
		<style> 

			#Floorplan {
				position:relative;
				width: 800px;
				height:546px;
				background-color: rgba(255,255,255,0.25);
				background-image: url("images/floorplan.svg");
				background-repeat: no-repeat;
				margin: auto;
				margin-top:20px;
			}
			#Floorplan div {
				position: absolute;
				background-color: rgba(0,0,0,0.5);
				z-index: 2;
				cursor: pointer;
				font-family: Arial;
				-webkit-border-radius: 5;
				-moz-border-radius: 5;
				border-radius: 5px;
				text-shadow: 3px 3px 3px #000000;
				-webkit-box-shadow: 3px 3px 3px #000000;
				-moz-box-shadow: 3px 3px 3px #000000;
				box-shadow: 3px 3px 3px #000000;
			}
			#Floorplan div:hover, #Floorplan div.Locked:hover, #Floorplan div.Unlocked:hover   {
				background: #00b3ff;
				background-image: -webkit-linear-gradient(top, #00b3ff, #0099db);
				background-image: -moz-linear-gradient(top, #00b3ff, #0099db);
				background-image: -ms-linear-gradient(top, #00b3ff, #0099db);
				background-image: -o-linear-gradient(top, #00b3ff, #0099db);
				background-image: linear-gradient(to bottom, #00b3ff, #0099db);
				text-decoration: none;
			}
			#Floorplan div > a{
				position: absolute;
				top: 50%;
				left: 50%;
				font-size: 12px;
				white-space: nowrap;
				color: white;
				transform: translate(-50%,-50%);
				-ms-transform: translate(-50%,-50%);
			}
			#Floorplan div.rotate90{
				-ms-transform: rotate(90deg); /* IE 9 */
				-webkit-transform: rotate(90deg); /* Safari 3-8 */
				transform: rotate(90deg);
				text-shadow: 3px -3px 3px #000000;
				-webkit-box-shadow: 3px -3px 3px #000000;
				-moz-box-shadow: 3px -3px 3px #000000;
				box-shadow: 3px -3px 3px #000000;
			}
			#gridMachLog {
				margin:auto;
			}
			#Floorplan div.Locked {
				background: #008000;
				background-image: -webkit-linear-gradient(top, #008000, #004000);
				background-image: -moz-linear-gradient(top, #008000, #004000);
				background-image: -ms-linear-gradient(top, #008000, #004000);
				background-image: -o-linear-gradient(top, #008000, #004000);
				background-image: linear-gradient(to bottom, #008000, #004000);
			}
			#Floorplan div.Unlocked, tr.Unlocked {
				background: #800000;
				background-image: -webkit-linear-gradient(top, #800000, #400000);
				background-image: -moz-linear-gradient(top, #800000, #400000);
				background-image: -ms-linear-gradient(top, #800000, #400000);
				background-image: -o-linear-gradient(top, #800000, #400000);
				background-image: linear-gradient(to bottom, #800000, #400000);
			}
			#Floorplan div.Unknown {background-color: rgba(0,0,99,1);}
			

		</style>
		<style>
			#M33 { /* Laser 1 */
				bottom: 165px;
				left: 580px;
				width: 55px;
				height: 25px;
			}
			#M35 { /* Table Saw */
				bottom: 390px;
				left: 325px;
				width: 100px;
				height: 50px;
			}
			#M36 { /* Metal Lathe */
				bottom: 440px;
				left: 525px;
				width: 65px;
				height: 25px;
			}
			#M37 { /* Wood Lathe */
				bottom: 175px;
				left: 250px;
				width: 75px;
				height: 25px;
			}
			#M41 { /* Panel Saw */
				bottom: 480px;
				left: 245px;
				width: 75px;
				height: 25px;
			}
			#M42 { /* Planer */
				bottom: 310px;
				left: 275px;
				width: 50px;
				height: 50px;
			}
			#M43 { /* Jointer */
				bottom: 402px;
				left: 280px;
				width: 50px;
				height: 25px;
			}
			#M46 { /* Mitre Saw */
				bottom: 100px;
				left: 440px;
				width: 75px;
				height: 25px;
			}
			#M48 { /* Roll-in Saw */
				bottom: 210px;
				left: 135px;
				width: 50px;
				height: 50px;
			}
			#M50 { /* Sanders */
				bottom: 280px;
				left: 500px;
				width: 50px;
				height: 25px;
			}
			#M53 { /* Drillpress */
				bottom: 310px;
				left: 230px;
				width: 40px;
				height: 40px;
			}
			#M55 { /* Bandsaw */
				bottom: 280px;
				left: 450px;
				width: 40px;
				height: 40px;
			}
			#M56 { /* Demo */
				bottom: 215px;
				left: 200px;
				width: 35px;
				height: 15px;
			}
			#M57 { /* Laser 2 */
				bottom: 27px;
				left: 580px;
				width: 55px;
				height: 25px;
			}
			#M58 { /* Router Table */
				bottom: 335px;
				left:175px;
				width: 70px;
				height: 25px;
			}
			#M60 { /* Dado Saw */
				bottom: 210px;
				left: 325px;
				width: 50px;
				height: 50px;
			}
			#M61 { /* Mill */
				bottom: 315px;
				left: 525px;
				width: 50px;
				height: 50px;
			}
			#M62 { /* Drum Sander */
				bottom: 390px;
				left: 200px;
				width: 50px;
				height: 50px;
			}
			#M63 { /* Grinder */
				bottom: 460px;
				left: 580px;
				width: 50px;
				height: 25px;
			}
			#M64 { /* Welding Area */
				bottom: 310px;
				left: 35px;
				width: 150px;
				height: 190px;
			}
			#M65 { /* Test */
				bottom: 230px;
				left: 200px;
				width: 35px;
				height: 15px;
			}
			#M66 { /* Sign In */
				bottom: 230px;
				left: 275px;
				width: 40px;
				height: 25px;
			}
			#M67 { /* CNC Router */
				bottom: 60px;
				left: 250px;
				width: 75px;
				height: 75px;
			}
		</style>
	</head>
	<body>
		<div id="master" class="Content">
			<div id="menu"></div>
			<div id="Floorplan">
				<div id="M33" class="rotate90"><a>Laser 1</a></div>
				<div id="M35"><a>Saw Stop</a></div>
				<div id="M36" class="rotate90"><a>Metal Lathe</a></div>
				<div id="M37"><a>Wood Lathe</a></div>
				<div id="M41"><a>Panel Saw</a></div>
				<div id="M42"><a>Planer</a></div>
				<div id="M43" class="rotate90"><a>Jointer</a></div>
				<div id="M46" class="rotate90"><a>Mitre Saw</a></div>
				<div id="M48"><a>Roll-in</br>Saw</a></div>
				<div id="M50"><a>Sanders</a></div>
				<div id="M53"><a>Drill</br>press</a></div>
				<div id="M55"><a>Band</br>saw</a></div>
				<div id="M56"><a>Demo</a></div>
				<div id="M57" class="rotate90"><a>Laser 2</a></div>
				<div id="M58" class="rotate90"><a>Router Table</a></div>
				<div id="M60"><a>Dado</br>Saw</a></div>
				<div id="M61"><a>Mill</a></div>
				<div id="M62"><a>Drum</br>Sander</a></div>
				<div id="M63" class="rotate90"><a>Grinder</a></div>
				<div id="M64"><a>Welding Area</a></div>
				<div id="M65"><a>Test</a></div>
				<div id="M66"><a>Sign In</a></div>
				<div id="M67"><a>CNC Router</a></div>
			</div>
			<div id="gridMachLog" class="AutoRefresh"></div>
		</div>
		<script>
				$("#Floorplan div").click(function () {alert("Test");});
		</script>
	</body>
</html>