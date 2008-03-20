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


/* Given a 1 bit image containing a barcode
 * Return an array of bar widths
 */
function barWidth($image,$scany)
{
	$xdim = imagesx($image);
	$b = array();
	$count = 0;

	$col = imagecolorat($image, 0, $scany);
	for ($x = 0; $x < $xdim; $x++) {
		$rgb = imagecolorat($image, $x, $scany);
		if ($rgb != $col)
		{
			$b[] = $count;
			//$b[]['colour'] = $rgb;
			$count = 0;
			$col = $rgb;
			//print("$col<br/>");
		}
		$count++;	
	}

	return $b;

}


/* Given an array of widths, return the guess of
 * the width of narrow and wide bars
 */
function nwWidth($array)
{
	$a = array();
	sort($array);
	$elements = count($array);

	if ($elements <= 1)
	{
		$a['n'] = 0;
		$a['w'] = 0;
		return $a;
	}

	$a['n'] = $array[(($elements/4)+1)];
	$a['w'] = $array[($elements-(($elements/4)+1))];

	//print ("N: {$a['n']} W: {$a['w']}<br/>");

	return $a;
}


/* Given an array of widths, an estimate of the widths
 * of wide and narrow bars, return narrow/wide rep as string
 */
function widthsToNW($widths,$narrow,$wide)
{
	//give a third tolerance

	$tolerance = ($wide - $narrow) / 3;
	$string = "";

	foreach($widths as $width)
	{
		if (($width >= ($narrow - $tolerance)) && ($width <= ($narrow + $tolerance))) $string .= "N";
		if (($width >= ($wide - $tolerance)) && ($width <= ($wide + $tolerance))) $string .= "W";
	}
	
	return $string;
}


/* Given a string of n's and w's return a code
 */
function NWtoCode($s)
{
	$hash = array();
	$hash['NNWWN'] = 0;
	$hash['WNNNW'] = 1;
	$hash['NWNNW'] = 2;
	$hash['WWNNN'] = 3;
	$hash['NNWNW'] = 4;
	$hash['WNWNN'] = 5;
	$hash['NWWNN'] = 6;
	$hash['NNNWW'] = 7;
	$hash['WNNWN'] = 8;
	$hash['NWNWN'] = 9;

	$code = "";
	//ignore the first 4 and last 3
	for ($i = 4; $i < (strlen($s) - 3); $i+= 10)
	{
		$b1 = $s[$i] . $s[$i + 2] . $s[$i + 4] . $s[$i + 6] . $s[$i + 8];
		$b2 = $s[$i + 1] . $s[$i + 3] . $s[$i + 5] . $s[$i + 7] . $s[$i + 9];
		if (!isset($hash[$b1]) || !isset($hash[$b2]))
			return "false";
		else
			$code .= $hash[$b1] . $hash[$b2];
	}

	return $code;
}

function validate($s)
{
	//length must be 10 * count + 7
	//must start with nnnn
	//must end with wnn
	
	if ( (fmod((strlen($s)-7.0),10.0) == 0) && (strncmp($s,"NNNN",4) == 0) && (strncmp(substr($s,(strlen($s)-3),3),"WNN",3) == 0)) return true;

	return false;

}

/* Given a GD image, Find an interleaved 2 of 5 barcode and return it otherwise
 * return false
 *
 * Currently steps pixel by pixel (step = 1)
 *
 */
function barcode($image, $step = 1)
{
	//search

	$height = imagesy($image);

	for ($i = ($step); $i < $height - ($step); $i += ($step))
	{
		$a = barWidth($image,$i);
		$w = nwWidth($a);
		if ($w['n'] != 0 && $w['w'] != 0){
			$s = widthsToNW($a,$w['n'],$w['w']);
			if(validate($s)){
				$code = NWtoCode($s);
				if ($code != "false" && strlen($code) == 8)
					return $code;
			}
		}
	}
	return false;
}

?>