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
		
		
		<link rel="icon" type="image/vnd.microsoft.icon" href="https://www.hpe.com/etc/designs/hpeweb/favicon.ico">
		<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="https://www.hpe.com/etc/designs/hpeweb/favicon.ico">
		<link rel="stylesheet" href="styles/kendo.common.min.css" />
		<link rel="stylesheet" href="styles/kendo.materialblack.min.css" />
		<link rel="stylesheet" href="styles/kendo.default.mobile.min.css" />
		<link rel="stylesheet" href="styles/macs.php" type="text/css"/>

		<script src="js/jquery.min.js"></script>
		<script src="js/kendo.all.min.js"></script>
		<script src="js/macs.js"></script>

		<script>
			$(document).ready(function () {
				// Get the modal popup
				var modal = document.getElementById('myModal');	
				// Get the <span> element that closes the modal
				var span = document.getElementsByClassName("close")[0];
				// When the user clicks on <span> (x), close the modal
				span.onclick = function() {modal.style.display = "none";}
				// When the user clicks anywhere outside of the modal, close it
				window.onclick = function(event) {if (event.target == modal) {modal.style.display = "none";}}
				//Create navigation buttons at top of page
				setupMenu(); 
				//Build the Kendo datasource object (queries the database)
				getUserDataSource(); 
				//Build the main Kendo grid of users.
				makeUserGrid (); 
				//Build the sub-grid of authorized machines.
				makeAccessGrid (); 
				//Populate the drop down list for adding machine access records
				getMachList(); 
				//Handle the clicking of the "Add" button
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
							//the AJAX returns the user_id that was updated, # of affected rows, and any SQL error
							//TODO: investigate returning the whole JSON dataset and binding results (saves a round trip)
							success: function(result){ 
								//Set the modal box to visible
								modal.style.display = "block"; 
								//Set the message of the modal box depending on the results
								if (result.AffectedRows==1) {$("#txtModal").text("User added to Machine successfully.");}
								else {$("#txtModal").text("Update Failed: "+result.SQLError);}
								//Update the User's access grid
								showUserAccess (result.UserID);
							},
							//If the AJAX fails, display the error in a popup
							error: function(XMLHttpRequest, textStatus, errorThrown) {
								 alert("XMLHttpRequest: "+XMLHttpRequest+"|textStatus: "+textStatus+"|errorThrown: "+errorThrown);
							}
						});
					}
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
							type: "PUT",
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Users'
						}
					},
					schema: {
                        model: {
							id: "id",
                            fields: {
                                id: {
									type: "string",
									editable: false	
								},
                                active: { type: "boolean",
									editable: true,
									nullable: true		
								 },
                                name: { 
									type: "string",
									validation: { required: true, pattern:'.{2,50}'}
								 },
                                login: { type: "string",
									nullable: true,
									validation: {pattern:'.{4,25}'}		
								 },
                                hash: { type: "string",
									editable: true,
									nullable: true,
									validation: {pattern:'.{8,20}'}		
								 },
                                badge_id: { type: "string",
									editable: true,
									nullable: true,
									validation: {pattern:'.{6,20}'}			
								 },
                                email: { type: "string",
									editable: true,
									nullable: true,
									validation: {email: true}
								 },
                                last_seen: { type: "string", editable: false },
								lastDateTime : { type: "date" }
                            }
                        }
                    },
					pageSize: 5
				});	
			}
			function toDate(value) {
				if (value === '' )
				{
					value = 0;
				}
				return new Date(value);
			}

			function makeUserGrid (){
				//Define the  user list data source
				 $("#gridUser").kendoGrid({
					dataSource: UserDataSource,
					sortable: true,
					editable:{mode: "popup"},
					beforeEdit:function(e){$("#AddAccess").hide();},
					cancel:function(e){	$("#AddAccess").show();},
					save:function(e){$("#AddAccess").show();},
					toolbar: ["create"], 
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
						template: "#= templateActive(active) #",
						field: "active",
						title: "Active",
						filterable: true,
						width: 7
						},{
						field: "name",
						title: "User Name",
						sortable: {initialDirection: "asc"},
						filterable: {cell: {operator: "contains"}},
						width: 25
						},{
						field: "login",
						title: "Log In",
						width: 15
						},{
						field: "hash",
						hidden: true,
						title : "Password",
						editor: function (container, options) {
							$('<input data-text-field="' + options.field + '" ' +
									'class="k-input k-textbox" ' +
									'type="password" ' +
									'data-value-field="' + options.field + '" ' +
									'data-bind="value:' + options.field + '"/># alert("test"); #')
									.appendTo(container)
						}
						},{
						field: "badge_id",
						title: "Badge #",
						width: 20
						},{
						field: "email",
						title: "Email Address",
						width: 25
						},{
						field: "lastDateTime",
						title: "Last Access",
						format: "{0: MM/dd/yy h:mm tt}",
						width: 15
					}],
					//When the selected user changes, do these things
					change: function() {
						var gview = $("#gridUser").data("kendoGrid");
						var selectedItem = gview.dataItem(gview.select()); //get the currently selected user in main user grid
						showUserAccess(selectedItem.id); //Show selected user's access records
						showUserLog(selectedItem.id);// Show selected user's log records
						titleSelected.innerHTML = " - " + selectedItem.name; //Set the selected user name into the title of the grid
						$("#titleSelected").prop('title',selectedItem.id); //set the title attribute of the selected user to the user_id - used as a temp variable for the adding of machine access and for debugging (visible when hovering)
						
					}
				});
				//Set the initial filter to only show active users. 
				$("#gridUser").data("kendoGrid").dataSource.filter({
				field: "active",
				operator: "eq",
				value: "true"
				});
				//set initial focus on the name search/filter box
				$("[date-text-field='name'] ").focus(); 
			}
		
			function makeAccessGrid () {				
				//Define the selected user's access list grid
				$("#gridUserAccess").kendoGrid({
					width: 300,
					sortable: true,
					reorderable: true,
					editable: { //disables the update functionality, only allows deletion
						update: false,
						mode: "inline",
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
				//Define the selected user's access list data source
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
					}
				});	
				//Get the User Access grid
				var grid = $('#gridUserAccess').data("kendoGrid");
				//Set the data source for the user Access grid
				grid.setDataSource(UserAccessDataSource);
				//Show the User Access grid
				$("#gridUserAccess").show();
			}
			
			function showUserLog (User_ID) {
				//Define the selected user's log datasource
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
                    }
				});	
				//Define the selected user's log grid
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
				//Show the selected user's log grid
				$("#gridUserLog").show();
			}	
			
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
			table {margin:auto;}
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
<!-- The Modal -->
<div id="myModal" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close">&times;</span>
    <p id="txtModal">Some text in the Modal..</p>
  </div>

</div>
</body>
</html>
