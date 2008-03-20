<?

/*	Copyright Deakin University 2007,2008
 *	Written by Adam Zammit - adam.zammit@deakin.edu.au
 *	For the Deakin Computer Assisted Research Facility: http://www.deakin.edu.au/dcarf/
 *	
 *	This file is part of queXF
 *	
 *	queXF is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *	
 *	queXF is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	along with queXF; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */


include("config.inc.php");


/* Create a box group in the DB
 */
function updateboxgroup($bgid,$width,$varname,$btid)
{
	global $db;
	$db->StartTrans();

	$sql = "UPDATE boxgroupstype
		SET btid = '$btid', width = '$width', varname = '$varname'
		WHERE bgid = '$bgid'";

	$db->Execute($sql);

	$db->CompleteTrans();
}

/* Delete a box group in the DB
 */
function deleteboxgroup($bgid)
{

	global $db;
	$db->StartTrans();

	$sql = "SELECT bid 
		FROM boxgroups 
		WHERE bgid = '$bgid'";

	$rows = $db->GetAll($sql);

	foreach($rows as $row)
	{
		$sql = "DELETE
			FROM boxes
			WHERE bid = '{$row['bid']}'";

		$db->Execute($sql);
	}

	$sql = "DELETE 
		FROM boxgroups
		WHERE bgid = '$bgid'";

	$db->Execute($sql);

	$sql = "DELETE
		FROM boxgroupstype
		WHERE bgid = '$bgid'";

	$db->Execute($sql);

	$db->CompleteTrans();

	return $bgid;
}

if (isset($_GET['deletebgid']))
{
	deleteboxgroup(intval($_GET['deletebgid']));
	exit();
}


if (isset($_POST['submit']))
{
	$bgid = $_POST['bgid'];
	$width = $_POST['width'];
	$varname = $_POST['varname'];
	$btid = $_POST['btid'];
	updateboxgroup($bgid,$width,$varname,$btid);
}



if (isset($_GET['bgid']) || isset($_GET['bid']))
{
	global $db;

	if (isset($_GET['bid'])){
		$bid = intval($_GET['bid']);
		$sql = "SELECT bgid 
			FROM boxgroups
			WHERE bid = '$bid'";
		$row = $db->GetRow($sql);
		$bgid = $row['bgid'];
	}else
		$bgid = intval($_GET['bgid']);

	
	$sql = "SELECT btid,varname,width
		FROM boxgroupstype
		WHERE bgid = '$bgid'";

	$row = $db->GetRow($sql);
	$btid = $row['btid'];
	$varname = $row['varname'];
	$width = $row['width'];

	//display the cropped boxes
	print "<img src=\"showpage.php?bgid=$bgid\"/>";

	?><form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?bgid=$bgid";?>"><?

	//display group selection
	$sql = "SELECT description,btid
		FROM boxgrouptypes";

	$rs = $db->Execute($sql);

	print "Group type:";
	print $rs->GetMenu2("btid",$btid);

	//display variable name
	?><br/>Variable name: <input type="text" size="12" value="<? echo $varname; ?>" name="varname"><br/><?

	//display width
	?>Width: <input type="text" size="12" value="<? echo $width; ?>" name="width"><br/><?

	?><input  TYPE="hidden" VALUE="<? echo $bgid; ?>" NAME="bgid"><br/><input type="submit" value="Submit" name="submit"/></form><?

	?><a href="<?php echo $_SERVER['PHP_SELF'] . "?deletebgid=$bgid";?>">Delete this group</a><?
}




?>