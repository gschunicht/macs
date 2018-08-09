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
		
		<title>MACS-Machines (<?php echo $_SESSION['username'] ?>)</title>
		<link rel="icon" href="images/MB_Favicon.png">
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.common.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.materialblack.min.css" />
		<link rel="stylesheet" href="//kendo.cdn.telerik.com/2018.2.516/styles/kendo.default.mobile.min.css" />
		<link rel="stylesheet" href="css/macs.php" type="text/css"/>

		<script src="//kendo.cdn.telerik.com/2018.2.516/js/jquery.min.js"></script>
		<script src="//kendo.cdn.telerik.com/2018.2.516/js/kendo.all.min.js"></script>
		<script src="js/macs.js"></script>
		

		<script id="UserMachine" type="text/x-kendo-template">
				<tr data-uid="#= id #" >
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
				var mach_id = "<?php if ($_GET["mach_id"]) {echo htmlspecialchars($_GET["mach_id"]);}else {echo "%"; }?>";
				console.log("mach_id: " + mach_id);
				getMachDataSource(mach_id);
				getUserList();
				findDataItem();
				$("#btnAddMachAccess").kendoButton({
					icon: "plus-circle",
					click: function () {
						$.ajax({
							url: "json_Access.php",
							type: "PUT",
							data:{
								mach_id: $("#titleSelected").prop('title'), 
								user_id: $("#UserList").val() 
							},
							success: function(result){ //the AJAX returns the updated user_id so we can update the displayed machines
								showUserAccess ($("#titleSelected").prop('title'));//TODO: investigate returning the whole JSON dataset and binding results (saves a round trip)
							}
						});
					}
				});
				
				$("#btnClrFltr").kendoButton({
					click: function () {
						MachDataSource.filter({});//Clears ALL filters, not just ID column
						$("#btnClrFltr").hide();
					}
				});
				
			});
			
			function getMachDataSource (mach_id){
				MachDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Mach.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Machines',
							type: "GET"
						},
						update: {
							url: "json_Mach.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Machines',
							type: "POST"
						},
						create: {
							url: "json_Mach.php",	
							dataType: "jsonp", // "jsonp" is required for cross-domain requests; use "json" for same-domain requests,
							jsonpCallback: 'Machines',
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
                                mach_nr: { type: "string",
									editable: false,
									nullable: true		
								 },
                                desc: { type: "string",
									editable: false,
									nullable: true		
								 },
                                last_seen: { type: "string",
									editable: false,
									nullable: true		
								 },
                                active: { type: "boolean",
									editable: true,
									nullable: true		
								 },
                                version: { type: "string",
									editable: true,
									nullable: true		
								 }
                            }
                        }
                    },
					pageSize: 5
				});	
				makeMachGrid (mach_id);
				if (mach_id) {
					MachDataSource.filter({field:"id", operator: "eq", value: mach_id});
					$("tr.k-filter-row th:nth-of-type(2)").html('<button type="button" class="k-button k-button-icon" id="btnClrFltr" aria-label="Clear"><span class="k-icon k-i-filter-clear"></span></button>')
				}
			}
			
			function makeMachGrid (mach_id){
				
				 var gridMach = $("#gridMach").kendoGrid({
					//rowTemplate: kendo.template($("#template").html()),
					dataSource: MachDataSource,
					sortable: true,
					//detailTemplate: kendo.template($("#template").html()),
					editable:"inline",
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
						{ command: [{name:"edit",text:""}], title: " ", width:10},
						{
						field: "id",
						title: "ID",
						filterable: false ,
						width: 8
					},{
						field: "name",
						title: "Machine Name",
						width: 25
					}, {
						field: "desc",
						title: "Description",
						width: 20
					}, {
						field: "machine_nr",
						title: "Machine NR",
						width: 20
					}, {
						field: "last_seen",
						title: "Last Seen",
						filterable: false ,
						width: 25
					}, {
						field: "version",
						title: "Version",
						filterable: false ,
						width: 25
					}, {
						template: '<span  #= active ? \'class="k.i.checkbox-checked"\' : \'class="k.i.checkbox"\'# />', 
						field: "active",
						title: "Active",
						filterable: false ,
						width: 10
					}],
					change: function() {
						var gview = $("#gridMach").data("kendoGrid");
						var selectedItem = gview.dataItem(gview.select());  
						showMachAccess(selectedItem.id);
						showMachLog(selectedItem.id);
						titleSelected.innerHTML = " - " + selectedItem.name;
						$("#titleSelected").prop('title',selectedItem.id); //set the title attribute of the selected machine to the mach_id - used as a temp variable for the adding of mahine access and for debugging (visible when hovering)
						
					}
				});
				if (mach_id) {
					showMachAccess(mach_id);
					showMachLog(mach_id);
				} else {
					$("[date-text-field='name'] ").focus();
				}
			}
			
			function showMachAccess (Mach_ID) {
				var MachAccessDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Access.php?mach_id=" + Mach_ID,	
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
								userName: {								
									editable: false, //this field will not be editable (default value is true)
									nullable: true   // a defaultValue will not be assigned (default value is false)
								}
							}
						}
					},
					requestStart: function() {
						kendo.ui.progress($("#gridMachAccess"), true);
					},
					requestEnd: function() {
						kendo.ui.progress($("#gridMachAccess"), false);
					}
				});	
					//sort Grid's dataSource
					MachAccessDataSource.sort({field: "machName", dir: "asc"});
				
				$("#gridMachAccess").kendoGrid({
					dataSource: MachAccessDataSource,
					width: 300,
					sortable: true,
					reorderable: true,
					editable: { //disables the update functionality, only allows deletion
						update: false,
						mode: "inline",
						destroy: true
					},
					//rowTemplate: kendo.template($("#UserMachine").html()),
					columns: [
						{command: [{name:"destroy",text:""}], title: " ", width:20},
						{
							field: "userName",
							title: "User Name",
							sortable: true,
							width: 80
						}
						]
				});
				$("#gridMachAccess").show();
			}
			
			function showMachLog (Mach_ID) {
				var MachLogDataSource = new kendo.data.DataSource({
					transport: {
						read: {
							url: "json_Log.php?mach_id=" + Mach_ID,	
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
						kendo.ui.progress($("#gridMachLog"), true);
					},
					requestEnd: function() {
						kendo.ui.progress($("#gridMachLog"), false);
					}
				});	
				
				$("#gridMachLog").kendoGrid({
					//rowTemplate: kendo.template($("#template").html()),
					dataSource: MachLogDataSource,
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
						field: "userName",
						title: "User Name",
						width: 30
						}, {
						field: "event",
						title: "Event Description",
						width: 30
						}]
				});
			
				$("#gridMachLog").show();
			}	
			
			function getUserList(){  //TODO - get this list to populate the User selector
		
				$("#UserList").kendoDropDownList({
					dataTextField: "name",
					dataValueField: "id",
					dataSource: {
						transport: {
							read: {
								dataType: "jsonp",
								url: "json_Users.php?active=1",
								jsonpCallback: 'Users'
							}
						}
					},
					optionLabel: "Select User..."
				});
			}
			
		</script>
	<style type="text/css">

	.k-grid-content>table>tbody>.k-alt
	{
	   background:rgba(63,193,192, 0.2);      
	}

	button {
		height:20px;
		width:20px;
		
	}
	#gridMachLog {
		width:60%;
		height:800px;
		float:right;
	}
	#gridMachAccess {
		width:39%;
		height:800px;
		float:left;
	}

	</style>
	</head>
<body>

<div id="master" class="Content">
	<div id="menu"></div>
    <div id="gridMach"><h2>MACS Machines<a id="titleSelected" class="SelectedTitle"></a></h2></div>
    <div id="gridMachLog" style="display:none;"><h2>Recent Log Entries</h2></div>
    <div id="gridMachAccess" style="display:none;">
		<h2>Authorized Users</h2>
		<div id="AddAccess"><input id="UserList"></input><em id="btnAddMachAccess">Add</em> </div>
	</div>
</div>
</body>
</html>
