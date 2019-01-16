<!DOCTYPE html>
<?php
// Initialize the session
session_start();
 
// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
}
?>
<html>
	<head>
		
		<title>MACS-Access(<?php echo $_SESSION['username'] ?>)</title>
		<!-- 
		
		This page is a access-centric page that will allow the user to search, filter, sort MACS users and associated machines.  
		References to kendo.cdn.telerik.com enable the Procress Telerik Kendo UI features under the Apache v2.0 License.  
		
		-->
		
		<link rel="icon" href="images/MB_Favicon.png">
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.common.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.materialblack.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.default.mobile.min.css" />
		<link rel="stylesheet" href="css/macs.php" type="text/css"/>

		<script src="//kendo.cdn.telerik.com/2018.2.516/js/jquery.min.js"></script>
		<script src="//kendo.cdn.telerik.com/2018.2.516/js/kendo.all.min.js"></script>
		<script src="js/macs.js"></script>

		<script>
			$(document).ready(function () {
				setupMenu(); //Creates navigation buttons at top of page
				getAccessDataSource(); //Build the Kendo datasource object
				makeAccessGrid (); //Build the main Kendo grid of access.
				
				//jQuery for menu buttons - TODO: move this to macs.js
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
				
				
			});
			
			function getAccessDataSource (){
				AccessDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Access.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Access',
							type: "GET"
						}
					},
					schema: {
                        model: {
							id: "id",
                            fields: {
                                id: {type: "string"},
                                userName: {type: "string"},
                                machName: {type: "string"},
                                machDesc: {type: "string"}
                            }
                        }
                    },
					group:{field:"userName"},
					pageSize: 50
				});	
			}
			
			function makeAccessGrid (){
				
				 $("#gridAccess").kendoGrid({
					dataSource: AccessDataSource,
					sortable: true,
					selectable: "row",
					filterable: {mode: "row"},
					reorderable: true,
					groupable: true,
					pageable: {
						refresh: true,
						pageSizes: true,
						buttonCount: 5
					},
					columns: [
						{
						field: "id",
						title: "ID",
						width: 5
						},{
						field: "userName",
						title: "User Name",
						sortable: {initialDirection: "asc"},
						filterable: {cell: {operator: "contains"}},
						width: 25
						},{
						field: "machName",
						title: "Machine Name",
						sortable: {initialDirection: "asc"},
						filterable: {cell: {operator: "contains"}},
						width: 25
						},{
						field: "machDesc",
						title: "Machine Description",
						sortable: {initialDirection: "asc"},
						filterable: {cell: {operator: "contains"}},
						width: 25
						
					}]
				});
				$("[date-text-field='name'] ").focus(); //set initial focus on the name search/filter box
			}
		</script>

		<style type="text/css">
			button {
				height:20px;
				width:40px;
				
			}
			#primary {
				max-width:1200px;
				margin:auto;
			}

			.SelectedTitle {
				color:#00b0ff;
			}
			table {margin:auto;}
		</style>
	</head>
<body>
<div id="master" class="Content">
	<div id="menu"></div>
    <div id="gridAccess"><h2>MACS Users-Machines<a id="titleSelected" class="SelectedTitle" title=""></a></h2></div>

</div>
</body>
</html>
