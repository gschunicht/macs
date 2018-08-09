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
		<!-- 
		
		This page is a user-centric page that will allow the user to search, filter, sort and edit MACS users.  Machine access can also be modified here, with the ability to add/remove machine-user links stored in the ACCCESS table.  A read only view of the most recent log entries is also included.  
		
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
				getUserDataSource(); //Build the Kendo datasource object
				makeUserGrid (); //Build the main Kendo grid of users.
				makeAccessGrid (); //Build the sub-grid of authorized machines.
				getMachList(); //Populate the drop down list for adding machine access records
				$("#btnAddMachAccess").kendoButton({
					icon: "plus-circle",
					click: function () {
						$.ajax({
							url: "json_Access.php",
							type: "PUT",
							data:{
								user_id: $("#titleSelected").prop('title'), 
								mach_id: $("#MachList").val() 
							},
							success: function(result){ //the AJAX returns the updated user_id so we can update the displayed machines
								showUserAccess (result);//TODO: investigate returning the whole JSON dataset and binding results (saves a round trip)
							}
						});
					}
				});
				
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
							type: "POST"
						},
						create: {
							url: "json_Users.php",	
							type: "PUT"
						}
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
					dataSource: UserDataSource,
					sortable: true,
					editable:"inline",
					beforeEdit:function(e){$("#AddAccess").show();},
					cancel:function(e){	$("#AddAccess").hide();},
					save:function(e){$("#AddAccess").hide();},
					edit: function() {
                          $("#gridUser").data("kendoGrid").select(".k-grid-edit-row");
                        },
					//toolbar: ["create", "save", "cancel"], //TODO: add new users and set active status requires separate form/template
					selectable: "row",
					filterable: {mode: "row"},
					resizable: false,
					reorderable: true,
					pageable: {
						refresh: true,
						pageSizes: true,
						buttonCount: 5
					},
					columns: [
						{ 
						command: [{name:"edit",text:"Edit"}], 
						title: " ", 
						width:10
						},{
						field: "id",
						title: "ID",
						width: 5
						},{
						field: "name",
						title: "User Name",
						sortable: {
							initialDirection: "asc"  
						},
						filterable: {cell: {operator: "contains"}},
						width: 25
						},{
						field: "login",
						title: "Log In",
						filterable: false ,
						width: 20
						},{
						field: "badge_id",
						title: "Badge #",
						width: 20
						},{
						field: "email",
						title: "Email Address",
						filterable: false ,
						width: 25
						},{ //TODO: use graphic or checkbox to indicate value. 
						template: "#= templateUserActive(active) #", 
						editor: customBoolEditor,
						field: "active",
						title: "Active",
						filterable: false ,
						width: 10
					}],
					change: function() {
						var gview = $("#gridUser").data("kendoGrid");
						var selectedItem = gview.dataItem(gview.select()); //get the currently selected user in main user grid
						showUserAccess(selectedItem.id); //Show selected user's access records
						showUserLog(selectedItem.id);// Show selected user's log records
						titleSelected.innerHTML = " - " + selectedItem.name; //Set the selected user name into the title of the grid
						$("#titleSelected").prop('title',selectedItem.id); //set the title attribute of the selected user to the user_id - used as a temp variable for the adding of machine access and for debugging (visible when hovering)
						
					}
				});
				$("[date-text-field='name'] ").focus(); //set initial focus on the name search/filter box
			}
		
			function makeAccessGrid () {
				$("#gridUserAccess").kendoGrid({
					//dataSource: UserAccessDataSource,
					width: 300,
					sortable: true,
					reorderable: true,
					editable: { //disables the update functionality, only allows deletion
						update: false,
						mode: "inline",
						//confirmation: false,
						destroy: true
					},
					columns: [
						{command: [{name:"destroy",text:"Remove"}], title: " ", width:25 },
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
						]
				});
			}
			function customBoolEditor(container, options) {
				var guid = kendo.guid();
				$('<input class="k-checkbox" id="' + guid + '" type="checkbox" name="active" data-type="boolean" data-bind="checked:active">').appendTo(container);
				$('<label class="k-checkbox-label" for="' + guid + '">&#8203;</label>').appendTo(container);
			}
			function showUserAccess (User_ID) {
				var UserAccessDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Access.php?user_id=" + User_ID,	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: "Access"
						},
						destroy: {
							url: "json_Access.php",	
							type: "DELETE"
						}
					},
					schema: {
						model: {
							id: "id",
							fields: {
								machName: {								
									editable: false, //this field will not be editable (default value is true)
									nullable: true   // a defaultValue will not be assigned (default value is false)
								},
								machDesc: {
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
				var grid = $('#gridUserAccess').data("kendoGrid");
				grid.setDataSource(UserAccessDataSource);
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
			
			function getMachList(){  //populates the drop down list for adding machine access.
		
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
						
			function templateUserActive(active) {
				if (active == 1) {
					return "<span class='k-icon k-i-checkmark-circle'></span>";
				} else {
					return "<span class='k-icon k-i-x-circle' style='color:red;'></span>";
				}
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
    <div id="gridUser"><h2>MACS Users<a id="titleSelected" class="SelectedTitle" title=""></a></h2></div>
    <div id="gridUserLog" style="display:none;"><h2>Recent Log Entries</h2></div>
    <div id="gridUserAccess" style="display:none;">
		<h2>Authorized Machines</h2>
		<div id="AddAccess"><select id="MachList"></select><em id="btnAddMachAccess">Add</em> </div>
	</div>
</div>
</body>
</html>
