<?php
//configure your access rights
require('/var/www/sabineholgeraccess.php');

$workingdir = "./cachedata";
$workingfile = "sabineholger-found.xml";
$requireTeamFind = "true";		//has to be a string

/* ********************************************************************************
	Admininterface for Loc2Map
	Loc2MapAdmin Version: 1.0.1

	Copyright (c) 2009, Holger Jeromin <jeromin(at)hitnet.rwth-aachen.de>
	for new version visit http://www.katur.de/

	This software is distributed under a Creative Commons Attribution-Noncommercial 3.0 License
	http://creativecommons.org/licenses/by-nc/3.0/de/
	Other licences available on request!

	History:
	--------
	03-October-2009			V1.0.0
		-	File created
	25-October-2011			V1.0.1
		-	better explanation

*/
?>
<!DOCTYPE html>
<html>
	<head>
		<link type="image/x-icon" href="/favicon.ico" rel="shortcut icon" />
		<title>loc2map Admininterface by Holger Jeromin</title>
		<script src="admin.js" async defer></script>
	<style type="text/css">
		body{
			margin-right:0px;
			margin-left:0px;
			background:#B3DAFF;
			font-family:sans-serif;
		}
	</style>
	</head>
	<body style="padding:0;margin:0;">
<?php
/***********************************************************
	check user rights
***********************************************************/
if ($internview != true){
?>
	<h1>Administration not possible from your Location</h1>
	</body>
</html>
<?php
	exit;
}
/***********************************************************
	check filesystem rights
***********************************************************/
else if (!is_writable($workingdir) || !is_writable($workingdir."/".$workingfile)){
?>
	<h1>Directory or XML File not writeable</h1>
	</body>
</html>
<?php
	exit;
}

/***********************************************************
	parse input
***********************************************************/
$foundXML = file_get_contents($workingdir."/".$workingfile);

/***********************************************************/
if (isset($_POST["cacheid"]) && empty($_POST["cacheid"])){
?>
	<h1>CacheID empty</h1>
	<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Retry</a> again.</p>
	</body>
</html>
<?php
/***********************************************************/
}elseif (isset($_POST["idlocxmldata"]) && empty($_POST["idlocxmldata"])){
?>
	<h1>XML-Data empty</h1>
	<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Retry</a> again.</p>
	<p><a href="./">Visit Map</a>.</p>
	</body>
</html>
<?php
/***********************************************************/
}elseif (isset($_POST["cacheid"]) && FALSE !== stripos($foundXML, $_POST["cacheid"])){
?>
	<h1>Cache with the ID <a href='http://www.geocaching.com/seek/cache_details.aspx?wp=<?php echo $_POST["cacheid"]; ?>'><?php echo $_POST["cacheid"]; ?></a> already submitted</h1>
	<p>Import <a href="<?php echo $_SERVER['PHP_SELF']; ?>">another Cache</a>.</p>
	<p><a href="./">Visit Map</a>.</p>
	</body>
</html>
<?php
/***********************************************************/
}elseif (isset($_POST["idlocxmldata"]) && isset($_POST["cacheid"]) && !empty($_POST["idlocxmldata"]) && !empty($_POST["cacheid"])){
	copy($workingdir."/".$workingfile, $workingdir."/".$workingfile.date("Y-m-d_Hi-s").".xml");
	$strNewlocData = str_replace('\"', '"', trim($_POST["idlocxmldata"]));
	$foundXML = str_replace("</loc>", $strNewlocData."\n</loc>", $foundXML);
	file_put_contents($workingdir."/".$workingfile, $foundXML);
?>
	<h1>Cache successfully submitted.</h1>
	<p>Import <a href="<?php echo $_SERVER['PHP_SELF']; ?>">another Cache</a>.</p>
	<p><a href="./">Visit Map</a>.</p>
	</body>
</html>
<?php
/***********************************************************/
}else{
?>
		<h1>Admintool for loc2map</h1>
		<p>Please visit the cache detail site on <a href="http://geocaching.com">geocaching.com</a>. Every Cache have a Link named <span
		style="border:1px outset #C0C0C0;font-family:verdana;font-size:11px;padding-top:2px;padding-right:7px;padding-bottom:2px;padding-left:7px;background-color:#D4D0C8;font-size:10.6667px;font-weight:400;"
		>Download GPX</span>. Download the file and paste the content into the textfield:</p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return preparedata();">
			<div>
				<textarea name="idlocxmldata" autofocus="autofocus" id="idlocxmldata" cols="140" rows="15"
				onblur="preparedata()" onpaste="preparedata()"
				></textarea>
			</div>
			<div>
				<span id="idCacherselection">
					found by:
					<select id="idcacherselect" size="1" onchange="preparedata()">
						<option value="none">Please select</option>
						<option value="both">both</option>
						<option value="holger">Holger</option>
						<option value="sabine">Sabine</option>
					</select>
				</span>
				<input type="hidden" id="cacheid" name="cacheid" value="" />
				<input style="display:none;" id="btnSubmit" type="submit" value=" Absenden " />
			</div>
		</form>

	</body>
</html>
<?php
}
?>
