<?php
//configure your access rights
if ( ( eregi('192.168.0.', $_SERVER["REMOTE_ADDR"]) && "extern" != $_GET['view']){
	$internview = true;
}else{
	$internview = false;
}

$workingdir = "./cachedata";
$workingfile = "sabineholger-found.xml";
$requireTeamFind = "false";		//has to be a string

/* ********************************************************************************
	Admininterface for Loc2Map
	Loc2MapAdmin Version: 1.0.0
	
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

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<link type="image/x-icon" href="/favicon.ico" rel="shortcut icon" />
		<title>loc2map Admininterface by Holger Jeromin</title>
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
if ($internview == false){
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
set_magic_quotes_runtime(0);
$strNewlocData = $_POST["idlocxmldata"];

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
}elseif (isset($strNewlocData) && empty($strNewlocData)){
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
}elseif (isset($strNewlocData) && isset($_POST["cacheid"]) && !empty($strNewlocData) && !empty($_POST["cacheid"])){
	copy($workingdir."/".$workingfile, $workingdir."/".$workingfile.date("Y-m-d_Hi-s").".xml");
	$strNewlocData = str_replace('\"', '"', trim($strNewlocData));
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
		>LOC Waypoint File</span>. Download the file and paste the content into the textfield:</p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return preparedata();">
			<div>
				<textarea name="idlocxmldata" id="idlocxmldata" cols="140" rows="15" onblur="preparedata()"></textarea>
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
		<script type="text/javascript">
			/* <![CDATA[ */
			document.getElementById("btnSubmit").style.display = "";
			if (false === <?php echo $requireTeamFind; ?>){
				document.getElementById("idCacherselection").style.display = "none";
			}
			
			function preparedata(){
				"use strict";
				//initialize local variables
				var Parser;
				var serializer;
				var locdata;
				var locdatatext;
				var requireTeamFind = <?php echo $requireTeamFind; ?>;
				
				/******************************************************************************
					Transfer XML String to DOM Object to manipulate
				*******************************************************************************/
				if ("function" === typeof DOMParser && "function" === typeof XMLSerializer){
					//Opera, Gecko, Webkit
					Parser = new DOMParser();
					serializer = new XMLSerializer();
					//transfer XML String to DOM Object
					locdata = Parser.parseFromString(document.getElementById("idlocxmldata").value, "text/xml");
					if ( !(locdata.documentElement.namespaceURI === null || locdata.documentElement.namespaceURI !== "http://www.mozilla.org/newlayout/xml/parsererror.xml" )){
						//parse Error => clear input, disable submit
						document.getElementById("idlocxmldata").value = "";
						return false;
					}
				}else if(window.ActiveXObject){
					//Internet Explorer
					locdata = new ActiveXObject("Microsoft.XMLDOM");
					locdata.preserveWhiteSpace=true;
					//transfer XML String to DOM Object
					locdata.loadXML(document.getElementById("idlocxmldata").value);
					if ( false === locdata ){
						//parse Error => clear input, disable submit
						document.getElementById("idlocxmldata").value = "";
						return false;
					}
				}else{
					document.getElementById("idlocxmldata").value = "Sorry, your browser do not support the DOM Parser Object or a ActiveXObject!";
					document.getElementById("idlocxmldata").style.backgroundColor = "red";
					return false;
				}
				
				/******************************************************************************
					check if select box is chosen
				*******************************************************************************/
				if (requireTeamFind === true){
					if (0 === document.getElementById("idcacherselect").selectedIndex){
						document.getElementById("idcacherselect").style.backgroundColor = "red";
						return false;
					}else{
						document.getElementById("idcacherselect").style.backgroundColor = "";
					}
				}
				
				/******************************************************************************
					append finder Node to XML and re-export to String
				*******************************************************************************/
				if (requireTeamFind === true && locdata.getElementsByTagName("waypoint").length !==0 && locdata.getElementsByTagName("waypoint")[0].getElementsByTagName("teamfind").length === 0){
					//no teamfind in XML => create it
					var TeamNode = locdata.createElement("teamfind");
					var TeamNodeText = locdata.createTextNode(document.getElementById("idcacherselect").options[document.getElementById("idcacherselect").selectedIndex].value);
					TeamNode.appendChild(TeamNodeText);
					
					//indent required
					TeamNodeText = locdata.createTextNode("\t");
					locdata.getElementsByTagName("waypoint")[0].appendChild(TeamNodeText);
					
					//append teamfind Node
					locdata.getElementsByTagName("waypoint")[0].appendChild(TeamNode);
					
					//indent required
					TeamNodeText = locdata.createTextNode("\n");
					locdata.getElementsByTagName("waypoint")[0].appendChild(TeamNodeText);
					
					//re-transform DOM Object to String
					if (serializer){
						locdatatext = serializer.serializeToString(locdata.getElementsByTagName("waypoint")[0]);
					}else{
						locdatatext = locdata.getElementsByTagName("waypoint")[0].xml;
					}
					
					//save cacheID for PHP script
					document.getElementById("cacheid").setAttribute("value", locdata.getElementsByTagName("waypoint")[0].getElementsByTagName("name")[0].getAttribute("id"));
				}else if(requireTeamFind === true && locdata.getElementsByTagName("waypoint").length !==0 && locdata.getElementsByTagName("waypoint")[0].getElementsByTagName("teamfind").length !== 0){
					//teamfind Node available => configure it
					locdata.getElementsByTagName("waypoint")[0].getElementsByTagName("teamfind")[0].firstChild.nodeValue = document.getElementById("idcacherselect").options[document.getElementById("idcacherselect").selectedIndex].value;
					document.getElementById("cacheid").setAttribute("value", locdata.getElementsByTagName("waypoint")[0].getElementsByTagName("name")[0].getAttribute("id"));
					
					//re-transform DOM Object to String
					if (serializer){
						locdatatext = serializer.serializeToString(locdata.getElementsByTagName("waypoint")[0]);
					}else{
						locdatatext = locdata.getElementsByTagName("waypoint")[0].xml;
					}
				}else if (requireTeamFind === false && locdata.getElementsByTagName("waypoint").length !==0) {
					//no team find required
					
					//re-transform DOM Object to String
					if (serializer){
						locdatatext = serializer.serializeToString(locdata.getElementsByTagName("waypoint")[0]);
					}else{
						locdatatext = locdata.getElementsByTagName("waypoint")[0].xml;
					}
				}else{
					//not correct XML file, clear display
					locdatatext = "";
				}
				
				/******************************************************************************
					save new string to Textinputform
				*******************************************************************************/
				document.getElementById("idlocxmldata").value = locdatatext;
				
				//allow submitting the form
				return true;
			}
		/* ]]> */
		</script>
	</body>
</html>
<?php
}
?>
