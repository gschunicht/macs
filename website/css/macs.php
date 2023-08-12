
<?php 
  header ("Content-type: text/css");
  $bg = array('bg1.jpg', 'bg2.jpg', 'bg3.jpg', 'bg4.jpg', 'bg5.jpg', 'bg6.jpg' ); // array of filenames

  $i = rand(0, count($bg)-1); // generate random number size of the array
  $selectedBg = "$bg[$i]"; // set variable equal to which random filename was chosen
?>
body {
	background-image: url("../images/<?php echo $selectedBg ?>");
    background-repeat: repeat;
    background-size: cover;
	font-family: Arial;
	color:#ffffff;
}

div.Content {
	margin:auto;
	max-width:1200px;
	margin:auto;
}
h2 {margin-left:5px;}
#menu {
	margin:auto;
	height:50px;
}

.SelectedTitle {
	color:#00b0ff;
}
.k-grid-content>table>tbody>.k-alt
{
   background:rgba(63,193,192, 0.2);      
}
.k-grid-content>table>tbody>.k-alt:selected
{
   background:rgba(63,193,192, 1);      
}
.k-grid-content>table>tbody>.k-alt:hover
{
   background:rgba(63,193,192, 0.4);      
}
.k-grid table tr.k-state-selected
{
background: #00b0ff;
}
 
div.wrapper {
	background-color: rgba(0,0,0,0.5);
	width: 300px; 
	padding: 20px;
}

span.menuLeft {
	float:left;
}

span.menuRight {
	float:right;
}

span.menuRight em,
span.menuLeft em {
	margin:2px;
}
#AddAccess {
	margin:3px;
}
div.form-group {
	margin:5px;
}

.has-error input{
	border:1px solid red;
}
.help-block {
	color:#ff0000;
}
input.Valid{
	border:1px solid green;
}
input.Invalid{
	border:1px solid red;
}
/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.8); /* Black w/ opacity */
}

/* Modal Content */
.modal-content {
  background:rgb(63,193,192);
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 20%;
  left:40%;
}

/* The Close Button */
.close {
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}