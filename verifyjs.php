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


//verifier

include("config.inc.php");
include("functions/functions.image.php");
include("functions/functions.database.php");
				

function bgidtocss($zoom = 1,$fid,$pid)
{
	global $db;

	$sql = "SELECT MIN(`tlx`) as tlx,MIN(`tly`) as tly,MAX(`brx`) as brx,MAX(`bry`) as bry, pid as pid, btid as btid, bgid as bgid
		FROM `boxesgroupstypes`
		WHERE pid = '$pid'
		AND btid > 0
		GROUP BY bgid";

	$boxgroups = $db->GetAll($sql);

	$sql = "SELECT offx,offy 
		FROM formpages
		WHERE pid = $pid and fid = $fid";
	
	$row = $db->GetRow($sql);

	$sql = "SELECT bid
		FROM boxesgroupstypes
		WHERE pid = '$pid'
		AND btid > 0
		ORDER BY bid ASC";

	$boxes = $db->GetAll($sql);

	$vis = "visible";

	if (!isset($row['offx']) && !isset($row['offy']))
	{ 
		$row = array();
		$row['offx'] = 0;
		$row['offy'] = 0;
	}

	print "<form method=\"get\" action=\"{$_SERVER['PHP_SELF']}\">";

	foreach ($boxgroups as $boxgroup)
	{
		$crop = calcoffset($boxgroup,$row['offx'],$row['offy']);

		$bgid = $boxgroup['bgid'];

		//make box group higher by 40 pixels
		$ttop = ($crop['tly'] / $zoom) - 40;
		if ($ttop < 0) $ttop = 0;

		print "<div id=\"boxGroup_$bgid\" style=\"position:absolute; top:" . $ttop . "px; width:1px; height:1px; background-color: white;opacity:.0;\"></div>";


		print "<div id=\"boxGroupBox_$bgid\" onclick=\"groupChange('$bgid');\" style=\"position:absolute; top:" . $crop['tly'] / $zoom . "px; left:" . $crop['tlx'] / $zoom . "px; width:" . ($crop['brx'] - $crop['tlx'] ) / $zoom . "px; height:" . ($crop['bry'] - $crop['tly'] ) / $zoom . "px; background-color: orange;opacity:.40; visibility: $vis;\"></div>";


		print "<div><input type=\"checkbox\" name=\"bgid$bgid\" id=\"bgid$bgid\" style=\"opacity:0.0; \"/></div>";

		$vis = "hidden";
	}


	foreach($boxes as $bi)
	{
		$bid = $bi['bid'];

		//if (!isset($_SESSION['boxes'][$bid])) break;

		$box = $_SESSION['boxes'][$bid];

		$val = $_SESSION['boxes'][$bid]['val'];
		$bbgid = $_SESSION['boxes'][$bid]['bgid'];
		$btid = $_SESSION['boxes'][$bid]['btid'];

		$box = calcoffset($box,$row['offx'],$row['offy']);


		if ($btid == 1) //single
		{
				if ($val == 0) {$checked = ""; $colour = "white"; } else {$checked = "checked=\"checked\""; $colour = "green";}
				print "<div><input type=\"checkbox\" name=\"bid$bid\" id=\"checkBox$bid\" value=\"$bid\" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; opacity:0.0; \" onclick=\"radioUpdate('$bid','$bbgid'); \" $checked onkeypress=\"checkEnter(event,$bbgid,$bid)\"/></div>";
				print "<div id=\"checkImage$bid\" onkeypress=\"checkEnter(event,$bbgid,$bid)\" onclick=\"radioChange('$bid','$bbgid'); \" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; background-color: $colour;opacity:.25; \"></div>";
	
		}
		else if ($btid == 2) //multiple
		{
	
				if ($val == 0) {$checked = ""; $colour = "white"; } else {$checked = "checked=\"checked\""; $colour = "green";}
				print "<div><input type=\"checkbox\" name=\"bid$bid\" id=\"checkBox$bid\" value=\"$bid\" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; opacity:0.0; \" onclick=\"checkUpdate('$bid','$bbgid'); \" $checked onkeypress=\"checkEnter(event,$bbgid,$bid)\" /></div>";
				print "<div id=\"checkImage$bid\" onkeypress=\"checkEnter(event,$bbgid,$bid)\" onclick=\"checkChange('$bid','$bbgid'); \" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; background-color: $colour;opacity:.25;  \"></div>";

		}
		else if ($btid == 3 || $btid == 4) //text or number
		{
			$maxlength = "maxlength=\"1\"";
			$onkeypress = "onkeypress=\"textPress(this,event,$bbgid,$bid)\"";

			if ($btid == 4)
			{
				if (!is_numeric($val)) $val = "";
			}

			$val = htmlspecialchars($val);
	
			print "<div><input type=\"text\" name=\"bid$bid\" id=\"textBox$bid\" value=\"$val\" $maxlength style=\"z-index: 1; position:absolute; top:" . (($box['tly'] / $zoom) + (($box['bry'] - $box['tly'] ) / $zoom)) . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px;\" onclick=\"\" onfocus=\"select()\" $onkeypress /></div>";

		
			print "<div id=\"textImage$bid\" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; background-color: white; text-align:center; font-weight:bold;\" onclick=\"textClick('$bid','$bbgid');\">$val</div>";
		}
		else if ($btid == 6)
		{
			$val = htmlspecialchars($val);
	
			print "<div><textarea name=\"bid$bid\" id=\"textBox$bid\" style=\"z-index: 1; position:absolute; top:" . (($box['tly'] / $zoom) + (($box['bry'] - $box['tly'] ) / $zoom)) . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px;\" onclick=\"\" onfocus=\"select()\" rows=\"20\" cols=\"80\">$val</textarea></div>";

		
			print "<div id=\"textImage$bid\" style=\"position:absolute; top:" . $box['tly'] / $zoom . "px; left:" . $box['tlx'] / $zoom . "px; width:" . ($box['brx'] - $box['tlx'] ) / $zoom . "px; height:" . ($box['bry'] - $box['tly'] ) / $zoom . "px; background-color: white; text-align:center; font-weight:bold;\" onclick=\"textClick('$bid','$bbgid');\">$val</div>";


		}
	}
	print "<div><input type=\"hidden\" name=\"piddone\" value=\"$pid\"/></div>";
	print "</form>";


}

session_start();

$vid = get_vid();

if($vid == false){ print "Please log in"; exit;}

$fid = get_fid($vid);


if (isset($_GET['centre']) && isset($_GET['fid']) && isset($_GET['pid']) )
{
	$pid = $_GET['pid'];

	$sql = "UPDATE formpages
		SET offx = 0, offy = 0
		WHERE fid = '$fid'
		AND pid = '$pid'";

	$db->Execute($sql);
}


if (isset($_GET['complete']))
{

	
	foreach($_SESSION['boxes'] as $key => $box)
	{

		$sql = "";
		if ($box['btid'] == 1 || $box['btid'] == 2)
		{
			if ($box['val'] > 0) $box['val'] = 1; else $box['val'] = 0;
			$sql = "INSERT INTO formboxverifychar (`vid`,`bid`,`fid`,`val`) VALUES ('$vid','$key','$fid','{$box['val']}')";
		}
		if ($box['btid'] == 3 || $box['btid'] == 4)
		{
			if ($box['val'] == "" || $box['val'] == " ")
			{
				$sql = "INSERT INTO formboxverifychar (`vid`,`bid`,`fid`,`val`) VALUES ('$vid','$key','$fid',NULL)";
			}else
			{
				$sql = "INSERT INTO formboxverifychar (`vid`,`bid`,`fid`,`val`) VALUES ('$vid','$key','$fid','{$box['val']}')";
			}
		}
		if ($box['btid'] == 6)
		{
			if ($box['val'] == "" || $box['val'] == " ")
			{
				$sql = "INSERT INTO formboxverifytext (`vid`,`bid`,`fid`,`val`) VALUES ('$vid','$key','$fid',NULL)";
			}else
			{
				$sql = "INSERT INTO formboxverifytext (`vid`,`bid`,`fid`,`val`) VALUES ('$vid','$key','$fid','{$box['val']}')";
			}

		}
		$db->Execute($sql);
		//print "$sql</br>";
	}

	//make sure worklog and update occurs at the same time
	$db->StartTrans();

	$sql = "INSERT INTO
		worklog (`vid`,`fid`,`assigned`,`completed`) VALUES ('$vid','$fid',FROM_UNIXTIME({$_SESSION['assigned']}),NOW())";
	//print "$sql</br>";
	$db->Execute($sql);

	unset($_SESSION['boxgroups']);
	unset($_SESSION['pages']);
	unset($_SESSION['boxes']);
	session_unset();

	$sql = "UPDATE forms
		SET done = 1
		WHERE assigned_vid = '$vid'
		AND fid = '$fid'
		AND done = 0";

	$db->Execute($sql);

	$fid = false;

	$sql = "UPDATE verifiers
		SET currentfid = NULL
		WHERE vid = '$vid'";

	//print "$sql</br>";
	$db->Execute($sql);

	$db->CompleteTrans();

}


if (isset($_GET['review']))
{
	foreach($_SESSION['boxgroups'] as $key => $val)
	{
		$_SESSION['boxgroups'][$key]['done'] = 0;
	}
}

if (isset($_GET['clear']))
{
	unset($_SESSION['boxgroups']);
	unset($_SESSION['pages']);
	unset($_SESSION['boxes']);
	session_unset();
}

if (isset($_GET['assign']))
{
	session_unset();
	$fid = assign_to($vid);
	if ($fid == false) 
	{print "NO MORE WORK";
		print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?assign=assign\">Check for more work</a></p>";
		unset($_SESSION['boxgroups']);
		unset($_SESSION['boxes']);
	       	unset($_SESSION['pages']);	
		session_unset();
		exit();
	}
	//set assigned time session variable
	$_SESSION['assigned'] = time();
}

if ($fid == false)
{
	print "<div id=\"links\">";
	print "<p>There is no form currently assigned to you</p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?assign=assign\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Assign next form</a></p>";
	print "</div>";
	print "<div id=\"wait\" style=\"visibility: hidden;\">
<p>Assigning next form: Please wait...</p>
</div>";
	exit();
}

$qid_desc = get_qid_description($fid);
$qid = $qid_desc['qid'];
$description = $qid_desc['description'];



if (!isset($_SESSION['boxes'])) {
	//nothing yet known about this form

	/*
	$sql = "SELECT *
		FROM formboxestoverify2
		WHERE fid = '$fid'
		AND btid > 0";
	*/


	$sql = "SELECT b.bid as bid, b.tlx as tlx, b.tly as tly, b.brx as brx, b.bry as bry, b.pid as pid, b.btid as btid, b.bgid as bgid, $fid as fid, b.sortorder as sortorder, c.val as val
		FROM boxesgroupstypes AS b
		LEFT JOIN formboxverifychar AS c ON c.fid = '$fid'
		AND c.vid =0
		AND c.bid = b.bid
		WHERE b.btid > 0
		AND b.qid = '$qid'
		ORDER BY sortorder ASC";

	
	$sql2 = "SELECT bgid,0 as done,pid,varname,btid
		FROM boxesgroupstypes
		WHERE qid = '$qid' 
		AND btid > 0
		GROUP BY bgid
		ORDER BY sortorder ASC";

	$sql3 = "SELECT pid,bgid,0 as done
		FROM boxesgroupstypes
		WHERE qid = '$qid'
		GROUP BY pid
		ORDER BY sortorder ASC ";

	$a = $db->GetAssoc($sql);
	if (empty($a)) {print "NO MORE WORK";

		print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?assign=assign\">Check for more work</a></p>";
	unset($_SESSION['boxgroups']); 	unset($_SESSION['pages']);
	unset($_SESSION['boxes']); 	session_unset(); exit();}

	$b = $db->GetAssoc($sql2);
	$c = $db->GetAssoc($sql3);


	$_SESSION['boxes'] = $a;
	$_SESSION['boxgroups'] = $b;
	$_SESSION['pages'] = $c;
	$_SESSION['assigned'] = time();

}


//form data already here

//if data submitted, store it to local session
if (isset($_GET['piddone']))
{
	$pid = intval($_GET['piddone']);

	foreach($_GET as $getkey => $getval)
	{
		//print "SUBMIT Key: $getkey Val: $getval<br/>";
		if (strncmp($getkey,'bgid',4) == 0)
		{
			$bgid = intval(substr($getkey,4));
			if ($getval == "on") $getval = 1;
			$_SESSION['boxgroups'][$bgid]['done'] = $getval;

			//destroy existing data in this box group...
			$sql = "SELECT bid
				FROM boxgroups
				WHERE bgid = '$bgid'";
		
			$b = $db->GetAll($sql);

			foreach($b as $bb)
			{
				$_SESSION['boxes'][$bb['bid']]['val'] = "";
			}



		}
	}


	//store retrieved data
	foreach($_GET as $getkey => $getval)
	{
		//print "SUBMIT Key: $getkey Val: $getval<br/>";
		if (strncmp($getkey,'bid',3) == 0)
		{
			$bid = intval(substr($getkey,3));
			$_SESSION['boxes'][$bid]['val'] = $getval;
		}
	}


}

$bgid = "";
$pid = "";
$destroypage = 0;

//move to a specific page
if (isset($_GET['pid']))
{
	$pid = intval($_GET['pid']);
	//destroy "done" for this page
	$destroypage = 1;
}
else
{
	//get next page to work on
	foreach($_SESSION['boxgroups'] as $key => $val)
	{
		if ($val['done'] == 0)
		{
			$bgid = $key;
			break;
		}
	}
}


if ($bgid != "")
{
	$sql = "SELECT pid
		FROM boxes
		WHERE bgid = '$bgid'";
	
	$bggg = $db->GetRow($sql);
	
	$pid = $bggg['pid'];
}
else if ($pid == "") 
{
	//we are done
	print "<p>The required fields have been filled</p>";
	print "<div id=\"links\">";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?complete=complete\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Submit completed form to database</a></p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?review=review#boxGroup\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Review all questions again</a></p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?clear=clear#boxGroup\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Clear all entered data and review again</a></p></div>";

	print "<div id=\"wait\" style=\"visibility: hidden;\">
<p>Submitting: Please wait...</p>
</div>";


	exit();
}	
	



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Verifier - <? print "QID:$qid FID:$fid DESC:$description"; ?></title>
<script type="text/javascript">

/* <![CDATA[ */

var bgiddone = new Array();
var bgidbid = new Array();
var bgidtype = new Array();
var curbgid = 0;
var pagedone = 0;

<?
//print array of done/not done box groups for this page
//print all bgid box groups for this page containing a list of boxes in that box group
foreach($_SESSION['boxgroups'] as $key => $val)
{
	if ($val['pid'] == $pid)
	{
		if ($val['done'] == 0 || $destroypage == 1)
			print "bgiddone[$key] = 0;\n";
		else
			print "bgiddone[$key] = 1;\n";

		print "bgidtype[$key] = {$val['btid']};\n";

		$sql = "SELECT bid
			FROM boxgroups
			WHERE bgid = '$key'";

		$b = $db->GetAll($sql);
	
		print "bgidbid[$key] = new Array(";

		$s = "";

		foreach($b as $bb)
		{
			$s .= "'{$bb['bid']}',";
		}


		$s = substr($s,0,strlen($s) - 1);
	
		print "$s);\n";
	}
}
?>


function nextTask()
{
	var done = 0;
	var focusdone = 0;

	for (x in bgiddone)
	{
		document.getElementById('boxGroupBox_' + x ).style.visibility = 'hidden';

		if (bgidtype[x] == 3 || bgidtype[x] == 4 || bgidtype[x] == 6)
		{	
			for (y in bgidbid[x])
			{
				document.getElementById('textImage' + bgidbid[x][y]).style.visibility = 'visible';
				document.getElementById('textBox' + bgidbid[x][y]).style.visibility = 'hidden';
				document.getElementById('textImage' + bgidbid[x][y]).innerHTML = document.getElementById('textBox' + bgidbid[x][y]).value;
			}
		}

		if (bgiddone[x] == 0 && done == 0)
		{
			curbgid = x;

			if (bgidtype[x] == 3 || bgidtype[x] == 4 || bgidtype[x] == 6)
			{	
				for (y in bgidbid[x])
				{
					if (focusdone == 0)
					{
						focusText(bgidbid[x][y]);
						focusdone = 1;
					}
					document.getElementById('textImage' + bgidbid[x][y]).style.visibility = 'hidden';
					document.getElementById('textBox' + bgidbid[x][y]).style.visibility = 'visible';
				}
			}else
			{
				if (focusdone == 0)
				{
					focusRadio();
					focusdone = 1;
				}
			}


			document.getElementById('boxGroupBox_' + x ).style.visibility = 'visible';
			document.getElementById('content').scrollTop = document.getElementById('boxGroupBox_' + x).offsetTop - 40;
		 	done = 1;
		}
	}

	if (done == 0)
	{
		//if (pagedone == 1)
			document.forms[0].submit();
		//else
		//	pagedone = 1;
	}



}

function previous() {

	if (curbgid == 0) return;

	prev = 0;

	for (x in bgiddone)
	{
		if (x == curbgid) break;
		prev = x;
	}

	if (prev == 0) return;

	bgiddone[prev] = 0;
}


function detectEvent(e) {
	var evt = e || event;

	if (evt.ctrlKey)
	{
		previous();
		nextTask();
		return false;
	}


	if(evt.keyCode != 13){ //if generated character code is equal to ascii 13 (if enter key)
		return document.defaultAction;
		
	}


	if (curbgid != 0)
	{
		bgiddone[curbgid] = 1;
		document.getElementById('bgid' + curbgid ).checked = 'checked';
		document.getElementById('bgid' + curbgid ).val = '1';
	}

	nextTask();

	return false;
}


function focusRadio()
{
	//alert('curbgid: ' + curbgid + ' bgidbid: ' + bgidbid[curbgid]);
	document.getElementById('checkBox' + bgidbid[curbgid][0]).focus();
	document.getElementById('checkBox' + bgidbid[curbgid][0]).select();

	for (y in bgidbid[curbgid])
	{
		z = bgidbid[curbgid][y];

		box = document.getElementById('checkBox' + z);
		image = document.getElementById('checkImage' + z);

		if (box.checked)
		{
			box.focus();
			box.select();
		}
	}


}



function checkFocus(bid,bgid) {

	if (curbgid != bgid)
	{
		//goto selected bgid	
		bgiddone[bgid] = 0;
		nextTask();
		return;
	}


	for (x in bgidbid[bgid])
	{
		x = bgidbid[bgid][x];

		box = document.getElementById('checkBox' + x);
		image = document.getElementById('checkImage' + x);

		if (x == bid)
		{
			box.focus();
			if (box.checked)
			{
				image.style.backgroundColor='green';
			} else {
				image.style.backgroundColor='yellow';
			}
		} else {
			if (box.checked)
			{
				image.style.backgroundColor='green';
			} else {
				image.style.backgroundColor='white';
			}
	
		}
	}

}


function groupChange(bgid) {

	if (curbgid != bgid)
	{
		//goto selected bgid	
		bgiddone[bgid] = 0;
		nextTask();
		return;
	}

	//else do nothing
	return;

}


function radioChange(bid,bgid) {

	if (curbgid != bgid)
	{
		//goto selected bgid	
		bgiddone[bgid] = 0;
		nextTask();
		return;
	}


	for (x in bgidbid[bgid])
	{
		x = bgidbid[bgid][x];

		box = document.getElementById('checkBox' + x);
		image = document.getElementById('checkImage' + x);

		if (x == bid)
		{
			if (box.checked)
			{
				box.checked = '';
				image.style.backgroundColor='white';
			} else {
				box.checked = 'checked';
				image.style.backgroundColor='green';
				box.focus();
			}
		} else {

			box.checked = '';
			image.style.backgroundColor='white';
		}
	}

}

function radioUpdate(bid,bgid) {

	for (x in bgidbid[bgid])
	{
		x = bgidbid[bgid][x];

		box = document.getElementById('checkBox' + x);
		image = document.getElementById('checkImage' + x);


		if (x == bid)
		{
			if (box.checked)
			{
				box.checked = 'checked';
				image.style.backgroundColor='green';
			} else {
				box.checked = '';
				image.style.backgroundColor='white';
			}
		} else {
			box.checked = '';
			image.style.backgroundColor='white';
		}
	}

}

//change the checkbox status and the replacement image
function checkChange(bid,bgid) {

	if (curbgid != bgid)
	{
		//goto selected bgid
		bgiddone[bgid] = 0;
		nextTask();		
		return;
	}


	box = document.getElementById('checkBox' + bid);
	image = document.getElementById('checkImage' + bid);

	if(box.checked) {
		box.checked = '';
		image.style.backgroundColor='white';
	} else {
		box.checked = 'checked';
		image.style.backgroundColor='green';
		box.focus();
	}
}


//change the checkbox status and the replacement image
function textClick(bid,bgid) {

	if (curbgid != bgid)
	{
		//goto selected bgid	
		bgiddone[bgid] = 0;
		nextTask();
		return;
	}


}



function checkUpdate(bid,bgid) {

	box = document.getElementById('checkBox' + bid);
	image = document.getElementById('checkImage' + bid);

	if(box.checked) {
		image.style.backgroundColor='green';
		box.focus();
	} else {
		image.style.backgroundColor='white';
	}


}



function checkEnter(e,bgid,bid){ //e is event object passed from function invocation
	var characterCode //literal character code will be stored in this variable
	var whi = 0;
	var current = 0;
	var next = 0;
	var prev = 0;
	var select = 0;

	if (e.keyCode == 16) return false; //ignore uppercase/shift

	characterCode = e.keyCode; //character code is contained in IE's keyCode property
	whi = e.which;
		//alert(e.which);

	if (whi >= 49 && whi <= 57) //keys 1-9 select appropriate box
	{
		cv = 0;
		for (y in bgidbid[bgid])
		{
			select = bgidbid[bgid][y];
			if (cv == (whi - 49))
			{
				break;
			}
			cv++;
		}
	
		if (bgidtype[bgid] == 1)
		{
			radioChange(select,bgid);
		}
		else if (bgidtype[bgid] == 2)
		{
			checkChange(select,bgid);
		}
	
		return true;
	}

	for (y in bgidbid[bgid])
	{
		if (current != 0)
		{
			next = bgidbid[bgid][y];
			break;
		}
				
		if (bgidbid[bgid][y] == bid)
		{
			current = bid;
		}else
		{
			prev = bgidbid[bgid][y];
		}
	}

	if (next == 0) next = current;
	if (prev == 0) prev = current;

	//alert('next: ' + next + ' current: ' + current + ' prev: ' + prev + ' bgid: ' + bgid + ' ccode: ' + characterCode);

	if(characterCode == 39 || characterCode == 40){ 
		checkFocus(next,bgid);
	}else if (characterCode == 37 || characterCode == 38){
		checkFocus(prev,bgid);	
	}


	return true;
}



function textPress(th,e,bgid,bid){ //e is event object passed from function invocation
	var characterCode //literal character code will be stored in this variable
	var current = 0;
	var next = 0;
	var prev = 0;

	if (e.keyCode == 16) return false; //ignore uppercase/shift
	if (e.keyCode == 13) return false; //ignore uppercase/shift

	characterCode = e.keyCode //character code is contained in IE's keyCode property


	for (y in bgidbid[bgid])
	{
		if (current != 0)
		{
			next = bgidbid[bgid][y];
			break;
		}
				
		if (bgidbid[bgid][y] == bid)
		{
			current = bid;
		}else
		{
			prev = bgidbid[bgid][y];
		}
	}

	if (next == 0) next = current;
	if (prev == 0) prev = current;

	if(characterCode >= 37 && characterCode <= 40){ //if generated character code is equal to ascii 13 (if enter key)
		//
	}
	else if (characterCode == 8){
		focusText(prev);
	}
	else
	{
		focusText(next);
	}

	return true;
}

function focusText(field)
{
	if (document.getElementById('textBox'+field))
	{
		document.getElementById('textBox'+field).focus();
		document.getElementById('textBox'+field).select();
	}
}


function init() {
	document['onkeydown'] = detectEvent;
	nextTask();
//	focusText(0);

//	for(var i=0; i < inputs.length; i++)
//	{
//		if (inputs[i].checked)
//		{
//			inputs[i].focus();
//		}
//	}

}


window.onload = init;

/* ]]> */
</script>
<style type="text/css">
#topper {
  position : fixed;
  width : 100%;
  height : 5%;
  top : 0;
  right : 0;
  bottom : auto;
  left : 0;
  border-bottom : 2px solid #cccccc;
  overflow : auto;
	text-align:center;
}

#header {
  position : fixed;
  width : 15%;
  height : 95%;
  top : 5%;
  right : 0;
  bottom : auto;
  left : 0;
  border-bottom : 2px solid #cccccc;
  overflow : auto;
}
#content {
  position : fixed;
  top : 5%;
  left : 15%;
  bottom : auto;
  width : 85%;
  height : 100%;
  color : #000000;
  overflow : auto;
}

</style>
</head>
<body>



<?

$zoom = 1;
if (isset($_GET['zoom'])) $zoom = intval($_GET['zoom']);


print "<div id=\"content\">";

if ($pid == "")
{
	//no more to do:
	print "<p>The required fields have been filled</p>";
	print "<div id=\"links\">";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?complete=complete\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Submit completed form to database</a></p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?review=review#boxGroup\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Review all questions again</a></p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?clear=clear#boxGroup\" onclick=\"document.getElementById('links').style.visibility='hidden'; document.getElementById('wait').style.visibility='visible';\">Clear all entered data and review again</a></p></div>";

	print "<div id=\"wait\" style=\"visibility: hidden;\">
<p>Submitting: Please wait...</p>
</div>";


}
else
{
	
	//show content
	print "<div style=\"position:relative;\"><img src=\"showpage.php?pid=$pid&amp;fid=$fid\" style=\"width:800px;\" alt=\"Image of page $pid, form $fid\" />";
	bgidtocss((2480.0/800.0),$fid,$pid);
	print "</div>";
	print "</div>";

	//show list of bgid for this fid
	print "<div id=\"header\">";
	
	print "<p>Q:$qid F:$fid P:$pid</p>";
	print "<p><a href=\"" . $_SERVER['PHP_SELF'] . "?pid=$pid&amp;fid=$fid&amp;centre=centre\">Centre Page</a></p>";


	foreach($_SESSION['boxgroups'] as $key => $val)
	{
		if ($val['pid'] == $pid)
		{
			//if ($bgid == $key)
				print "<strong>{$val['varname']}</strong><br/>";
			//else
			//	print "<a id=\"link$key\" href=\"" . $_SERVER['PHP_SELF'] . "?bgid=$key&amp;fid=$fid#boxGroup\">{$val['varname']}</a><br/>";
		}	
	}
	
print "</div>";

//show list of pid for this fid
	print "<div id=\"topper\">";


	//print_r($_SESSION['pages']);

	$count = 1;	
	foreach($_SESSION['pages'] as $key => $val)
	{
		if ($pid == $key)
			print "<strong>$count</strong>";
		else
			print " <a href=\"" . $_SERVER['PHP_SELF'] . "?pid=$key&amp;fid=$fid#boxGroup\">$count</a> ";
		$count++;

	}
	
print "</div>";


}


?>


</body></html>



