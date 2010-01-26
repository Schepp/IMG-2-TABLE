<?php
error_reporting(2047);

function img2table($sourcefile = '',$stretch = 1,$prefix = 't')
{
	$style = 'table.'.$prefix.'table td{width:'.$stretch.'px;height:'.$stretch.'px;}'."\n";
	$table = '';
	$backgroundcolor = '';
	$class_array = array();
	$class_count = array();
	$class_table = array();
	for($i=48;$i<58;$i++) array_push($class_table,chr($i));
	for($i=65;$i<91;$i++) array_push($class_table,chr($i));
	for($i=97;$i<123;$i++) array_push($class_table,chr($i));
	$class_table_count = count($class_table);
	for($j=0;$j<$class_table_count;$j++)
	{
		for($i=0;$i<$class_table_count;$i++) 
		{
			if(count($class_table) < 256) array_push($class_table,$class_table[$j].$class_table[$i]);
			else break;
		}
	}

	$info = getimagesize($sourcefile);
	if(strtoupper($info[2]) == 1 || strtoupper($info[2]) == 2 || strtoupper($info[2]) == 3)
	{
		if(strtoupper($info[2]) == 1) $img = imagecreatefromgif($sourcefile);
		if(strtoupper($info[2]) == 2) 
		{
			$img = imagecreatefromjpeg($sourcefile);
			imagetruecolortopalette($img, false, 255);
		}
		if(strtoupper($info[2]) == 3) 
		{
			$img = imagecreatefrompng($sourcefile);
			imagetruecolortopalette($img, false, 255);
		}
		for($y=0;$y<$info[1];$y++)
		{
			$lastcolor = '';
			$colorcounter = 0;
			if($y > 0) $table .= "<tr>";
			for($x=0;$x<$info[0];$x++)
			{
				$currentcolor = imagecolorat($img, $x, $y);
				$colorrgb = imagecolorsforindex($img,$currentcolor);
				$currentcolor = '#'.str_pad(dechex($colorrgb['red']),2,'0',STR_PAD_LEFT).str_pad(dechex($colorrgb['green']),2,'0',STR_PAD_LEFT).str_pad(dechex($colorrgb['blue']),2,'0',STR_PAD_LEFT);
				if(substr($currentcolor,1,1) == substr($currentcolor,2,1) && substr($currentcolor,3,1) == substr($currentcolor,4,1) && substr($currentcolor,5,1) == substr($currentcolor,6,1))
				{
					$currentcolor = '#'.substr($currentcolor,1,1).substr($currentcolor,3,1).substr($currentcolor,5,1);
				}
				if(!isset($class_array[$currentcolor]))
				{
					$classname = $prefix.$class_table[count($class_array)];
					$class_array[$currentcolor] = $classname;
					$style .= ".".$classname."{background:".$currentcolor."}\n";
				}
				
				if($x == 0) $lastcolor = $currentcolor;
				
				if(($y == 0 && $x != 0) || ($colorcounter > 0 && $lastcolor != $currentcolor)) 
				{
					if($colorcounter > 1) $table .= "<td colspan=".$colorcounter." class=".$class_array[$lastcolor].">";
					else $table .= "<td class=".$class_array[$lastcolor].">";
					if(!isset($class_count[$currentcolor])) $class_count[$currentcolor] = 1;
					else $class_count[$currentcolor]++;
					$lastcolor = $currentcolor;
					$colorcounter = 0;
				}
				$colorcounter++;
			}
			if($colorcounter > 1) $table .= "<td colspan=".$colorcounter." class=".$class_array[$currentcolor].">";
			elseif($colorcounter == 1) $table .= "<td class=".$class_array[$currentcolor].">";
		}
		$table .= "</table>";
		arsort($class_count);
		reset($class_count);
		$backgroundcolor = key($class_count);
		$table = "<table cellpadding=0 cellspacing=0 border=0 width=".($info[0] * $stretch)." class=".$prefix."table style=table-layout:fixed bgcolor=".$backgroundcolor."><tr>".str_replace(" class=".$class_array[$backgroundcolor].">",">",$table);
		return "<style type=\"text/css\">\n".$style."</style>\n".$table;
	}
	else return 'Unsupported file format!';
}

$stretch = (isset($_POST['stretch']) && intval($_POST['stretch']) > 1) ? intval($_POST['stretch']) : 1;
$prefix = (isset($_POST['prefix']) && preg_match('/[a-z]/i',$_POST['prefix']) == 1) ? $_POST['prefix'] : 't';
$html = '';
if(isset($_FILES['userfile']) && isset($_FILES['userfile']['name']) && trim($_FILES['userfile']['name']) != '')
{
	$uploadfile = $_FILES['userfile']['name'];
	if(move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) 
	{
		$html = img2table($uploadfile,$stretch,$prefix);
		$info = getimagesize($uploadfile);
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>&lt;IMG&gt; 2 &lt;TABLE&gt; Converter</title>
<style type="text/css">
body {font-family: Arial, Helvetica, sans-serif; background-color: #666; color: #FFF;}
table {border: #333333 1px solid;}
</style>
</head>
<body>
<h2>Options</h2>
<form name="bildform" action="index.php" method="post" enctype="multipart/form-data">
	<label>Stretch-factor: <input type="text" name="stretch" value="<?php echo $stretch; ?>" size="1" maxlength="1"></label>&nbsp;
	<label>Class-prefix: <input type="text" name="prefix" value="<?php echo $prefix; ?>" size="1" maxlength="1"></label><br />
	<label>File (GIF/PNG/JPG): <input type="file" name="userfile" size="40"></label>
	<input type="submit" value="convert"><br />
	<i><strong>Note:</strong> truecolor-images will be reduced to 256 colors. Transparency is not yet supported.</i>
</form>

<?php 
if(isset($_FILES['userfile']) && isset($_FILES['userfile']['name']) && trim($_FILES['userfile']['name']) != '')
{
	echo '<h2>Result</h2>Dimensions: '.$info[0].' &times; '.$info[1].' pixel<br />Uncompressed Byte-Size (8-Bit Color): '.round(($info[0]*$info[1])/1024).' KB<br /><br />'.$html.'<br /><h2>Size Comparison</h2>'; 
	echo 'Before: '.round(filesize($uploadfile) / 1024).' KB (source-image file-size)<br />';
	echo 'After: '.round(strlen($html) / 1024).' KB (source-code byte-size)<br />';
echo '<h2>Code</h2>
<textarea cols="200" rows="100" style="width: 95%">'.$html.'</textarea>';
}
?>

</body>
</html>
