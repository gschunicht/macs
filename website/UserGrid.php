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
		
		<title>MACS-Users (<?php echo $_SESSION['username'] ?>)</title>
		<link rel="icon" href="images/MB_Favicon.png">
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.common.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.materialblack.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.default.mobile.min.css" />
		<link rel="stylesheet" href="css/macs.php" type="text/css"/>

		<script src="//kendo.cdn.telerik.com/2018.2.516/js/jquery.min.js"></script>
		<script src="//kendo.cdn.telerik.com/2018.2.516/js/kendo.all.min.js"></script>
		<script src="js/macs.js"></script>
		

		<script id="UserMachine" type="text/x-kendo-template">
				<tr data-uid="#= id # >
					<td class="k-command-cell">
						<a class="k-button k-button-icontext k-grid-delete" >
							<span class="k-icon k-i-delete"></span>
						</a>
					</td>
					<td>#= name #</td>
					<td>#= desc #</td>
				</tr>
		</script>
		<script>
			$(document).ready(function () {
				setupMenu();
				getUserDataSource();
				makeUserGrid ();
				getMachList();
				$("#btnAddMachAccess").kendoButton({
					icon: "plus-circle",
					click: function () {showUserLog();}
				});
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
			
			function getUserDataSource (){
				UserDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Users.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Users',
							type: "GET"
						},
						update: {
							url: "json_Users.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Users',
							type: "POST"
						},
						create: {
							url: "json_Users.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Users',
							type: "PUT"
						}//TODO: add schema to allow for writes
					},
					schema: {
                        model: {
							id: "id",
                            fields: {
                                id: {
									type: "string",
									editable: false,
									nullable: true		
								},
                                name: { type: "string",
									editable: true,
									nullable: true		
								 },
                                login: { type: "string",
									editable: false,
									nullable: true		
								 },
                                hash: { type: "string",
									editable: false,
									nullable: true		
								 },
                                badge_id: { type: "string",
									editable: true,
									nullable: true		
								 },
                                email: { type: "string",
									editable: true,
									nullable: true		
								 },
                                last_seen: { type: "string",
									editable: false,
									nullable: true		
								 },
                                active: { type: "boolean",
									editable: true,
									nullable: true		
								 }
                            }
                        }
                    },
					requestStart: function() {
						kendo.ui.progress($("#gridUser"), true);
					},
					requestEnd: function() {
						kendo.ui.progress($("#gridUser"), false);
					},
					pageSize: 5
				});	
			}
			
			function makeUserGrid (){
				
				 $("#gridUser").kendoGrid({
					//rowTemplate: kendo.template($("#template").html()),
					dataSource: UserDataSource,
					sortable: true,
					//detailTemplate: kendo.template($("#template").html()),
					editable:"inline",
					toolbar: ["create", "save", "cancel"],
					selectable: "row",
					filterable: {
									mode: "row"
								},
					resizable: false,
					reorderable: true,
					pageable: {
						refresh: true,
						pageSizes: true,
						buttonCount: 5
					},
					columns: [
						{ command: [{name:"edit",text:""}], title: " ", width:10},
						{
						field: "id",
						title: "ID",
						width: 5
						},
						{
						field: "name",
						title: "User Name",
						sortable: {
							initialDirection: "asc"  
						},
						filterable: {
							cell: {operator: "contains"	}
						},
						width: 25
					}, {
						field: "login",
						title: "Log In",
						filterable: false ,
						width: 20
					}, {
						field: "badge_id",
						title: "Badge #",
						width: 20
					}, {
						field: "email",
						title: "Email Address",
						filterable: false ,
						width: 25
					}, {
						//template: '<span  #= active ? \'class="k.i.checkbox-checked"\' : \'class="k.i.checkbox"\'# />', 
						field: "active",
						title: "Active",
						filterable: false ,
						width: 10
					}],
					change: function() {
						var gview = $("#gridUser").data("kendoGrid");
						var selectedItem = gview.dataItem(gview.select()); 
						showUserAccess(selectedItem.id);
						showUserLog(selectedItem.id);
						titleSelected.innerHTML = " - " + selectedItem.name;
						
					}
				});
				$("[date-text-field='name'] ").focus();
			}
			
			function showUserAccess (User_ID) {
				var UserAccessDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Access.php?user_id=" + User_ID,	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: "Access"
						}
					},
					schema: {
						model: {
							id: "id",
							fields: {
								name: {								
									editable: false, //this field will not be editable (default value is true)
									nullable: true   // a defaultValue will not be assigned (default value is false)
								},
								desc: {
									editable: false,
									nullable: true
								}
							}
						}
					},
					requestStart: function() {
						kendo.ui.progress($("#gridUserAccess"), true);
					},
					requestEnd: function() {
						kendo.ui.progress($("#gridUserAccess"), false);
					}
				});	
				
				$("#gridUserAccess").kendoGrid({
					dataSource: UserAccessDataSource,
					width: 300,
					sortable: true,
					reorderable: true,
					editable: { //disables the update functionality
						update: false,
						destroy: true
					},
					columns: [
						{command: [{name:"destroy",text:""}], title: " ", width:20 },
						{
						field: "machName",
						title: "Machine Name",
						sortable: {
							initialDirection: "asc"  
						},
						width: 30
						}, {
						field: "machDesc",
						title: "Description",
						width: 50
						}
						//{command: [{name:"destroy", text:""}], title: " ", width:10}
						]
				});
				$("#gridUserAccess").show();
			}
			
			function showUserLog (User_ID) {
				var UserLogDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Log.php?user_id=" + User_ID,	
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
					requestStart: function() {
						kendo.ui.progress($("#gridUserLog"), true);
					},
					requestEnd: function() {
						kendo.ui.progress($("#gridUserLog"), false);
					}
				});	
				
				$("#gridUserLog").kendoGrid({
					//rowTemplate: kendo.template($("#template").html()),
					dataSource: UserLogDataSource,
					width: 300,
					sortable: true,
					reorderable: true,
					columns: [{
						field: "logDateTime",
						title: "Date/Time",
						format: "{0:ddd MM/dd h:mm tt}",
						sortable: {
							initialDirection: "desc"  
						},
						width: 40
						}, {
						field: "machName",
						title: "Machine Name",
						width: 30
						}, {
						field: "event",
						title: "Event Description",
						width: 30
						}]
				});
			
				$("#gridUserLog").show();
			}	
			
			function getMachList(){  //TODO - get this list to populate the Machine selector
		
				$("#MachList").kendoDropDownList({
							dataTextField: "name",
							dataValueField: "id",
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
			
			function addMachAccess (user_id, machine_id){}  //TODO - write function to add selected machine access permission
			function removeMachAccess (id){}  //TODO - write function to remove selected machine access permission
			
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
	#gridUserLog {
		width:60%;
		height:800px;
		float:right;
	}
	#gridUserAccess {
		width:39%;
		height:800px;
		float:left;
	}
	.SelectedTitle {
		color:#00b0ff;
	}

	</style>
	</head>
<body>
<div id="master" class="Content">
	<div id="menu"></div>
    <div id="gridUser"><h2>MACS Users<a id="titleSelected" class="SelectedTitle"></a></h2></div>
    <div id="gridUserLog" style="display:none;"><h2>Recent Log Entries</h2></div>
    <div id="gridUserAccess" style="display:none;">
		<h2>Authorized Machines</h2>
		<div id="AddAccess"><select id="MachList"></select><em id="btnAddMachAccess">Add</em> </div>
	</div>
</div>
</body>
</html>
