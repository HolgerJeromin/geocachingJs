<?php
//this file generates a variable $internview, which enables administration link
//(the only php function of this js software)
require('/var/www/sabineholgeraccess.php');

/* ********************************************************************************
	Visualisation of Geocachingpoints out of a .loc file
	JavaScript only implementation, inspired and API conform from GMAPLOC http://www.henning-mersch.de/projects_former
	Loc2Map Version: 2.0.1
	for new version visit http://www.katur.de/

	Uses the leaflet software with multiple plugins.
	js+css leaflet is used from the leaflet CDN (see http://leafletjs.com/download.html)
	The other files are not available via CDN, you have to download the locally on your server.
	I have renamed a few of them, to have the directory a little bit cleaner

	INSTALL (all urls tested with github files from january 2013):
		js+css from https://github.com/danzel/Leaflet.markercluster/tree/master/dist
			download leaflet.markercluster-src.js
			download and rename MarkerCluster.Default.css to leaflet.markercluster.css
		js+css+png from https://github.com/kartena/Leaflet.zoomslider/tree/master/src
			download and rename L.Control.Zoomslider.js to leaflet-control-Zoomslider.js
			download and rename L.Control.Zoomslider.ie.css to leaflet-control-Zoomslider.ie.css
			download and rename L.Control.Zoomslider.css to leaflet-control-Zoomslider.css
			download all images in the directory images
		js from https://github.com/seelmann/leaflet-providers
			download leaflet-providers-0.0.2.js
		js for tile layer bing and google from https://github.com/shramov/leaflet-plugins/tree/master/layer/tile
			download and rename Bing.js to leaflet-layer-bing.js
			download and rename Google.js to leaflet-layer-google.js
		js for permalink from https://github.com/shramov/leaflet-plugins/tree/master/control
			download and rename Permalink.js to leaflet-control-permalink.js
		png from http://www.katur.de/geocaching/blue.png and crshair.gif, yellow.png, red.png and green.png

	USAGE:
		After downloading all files into one directory, you can point the browser to
		the corresponding URL on your server.
		There are a three possibilities to fill map with caches
			- URL parameter locurl, gives red icons
			- URL parameter greenlocurl, gives green icons
			- fallback to hardcoded two xml files, if none locurl is found
		various URL parameter:
			&title=NewDocumentTitle
			&centerWP=CacheID for centering
			&height=height of Window
			&width=width of Window
			&widthLegend=width of the legend

	Copyright (c) 2013, Holger Jeromin <jeromin(at)hitnet.rwth-aachen.de>

	This software is distributed under a Creative Commons Attribution-Noncommercial 3.0 License
	http://creativecommons.org/licenses/by-nc/3.0/de/
	Other licences available on request!

	History:
	--------
	02-October-2009			V1.0.0
		-	File created
	09-November-2009		V1.0.1
		-	Adjusting for iPhone and Opera Mobile
	15-July-2010		V1.0.2
		-	Using the W3C Geolocation API
	26-August-2010		V1.0.3
		-	display the accuracy of the location
	05-April-2011		V1.1.0
		-	support of Openstreetmap basemap
	15-October-2011		V1.1.1
		-	better error reporting
	27-December-2012		V2.0.0
		-	ported to leaflet with many new features
	07-January-2013		V2.0.1
		- Dokumentation update
		- Openpistemap overlay
		- Clustering is optional via checkbox
*/

?>
<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, height=device-height, target-densitydpi=device-dpi, initial-scale=1.0, user-scalable=no" />
		<link type="image/x-icon" href="favicon.ico" rel="shortcut icon"/>
		<title>loc2map by Holger Jeromin</title>
		<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4.5/leaflet.css" />
		<link rel="stylesheet" href="leaflet.markercluster.css" />
		<link rel="stylesheet" href="leaflet-control-Zoomslider.css" />
		<script src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
		<script src="http://cdn.leafletjs.com/leaflet-0.4.5/leaflet.js"></script>
		<script src="leaflet.markercluster-src.js"></script>
		<script src="leaflet-providers-0.0.2.js"></script>
		<script src="leaflet-providers-0.0.2.js"></script>
		<script src="leaflet-layer-google.js"></script>
		<script src="leaflet-layer-bing.js"></script>
		<script src="leaflet-control-permalink.js"></script>
		<script src="leaflet-control-Zoomslider.js" ></script>
		<script src="loc2map.mjs" defer type="module"></script>
	</head>
	<body style="padding:0;margin:0;">
		<div id="geocachingmenu" style="width: 180px; height:500px; float:right;font-size:small;overflow-x:hidden;overflow-y:auto;">
			<table style="padding:0px;" id="geocachingmenutable"><tbody>
				<tr>
					<th></th>
					<th>ID</th>
					<th>Name</th>
				</tr>
			</tbody></table>
		</div>
		<div id="geocachingmap" style="width: 750px; height:500px;"></div>
		<!-- crosshair makes a funny empty space on the left side. but better than on the right or middle -->
		<div>
			<img id="crosshair" style="float:left;position:relative; top:-322px; left:292px;" src="crshair.gif" alt="" width="18" height="18" />
		</div>
		<div id="stats" style="display:none;">
			<div style="float:right;padding:0;margin:0px;">
					<a href="https://www.geocaching.com/seek/nearest.aspx?ul=SabineHolger">
				<img style="border:0px;" alt="" src="https://img.geocaching.com/stats/img.aspx?uid=bb140a75-2eb5-414c-a888-8b9d2f714bbb&amp;txt=Statistik+auf+Geocaching.com" /></a>
			</div>
			<div id="maplabel">
				<img alt="" src="yellow.png"/>(<span id="CountHidden">0</span>): hidden caches;
				found <span id="CountFoundAll">0</span> in total:
				<img alt="" src="red.png"/>(<span id="CountFoundSabine">0</span>): Sabine,
				<img alt="" src="blue.png"/>(<span id="CountFoundHolger">0</span>): Holger,
				<img alt="" src="green.png"/>(<span id="CountFoundBoth">0</span>): both
				<?php if ($internview === true){ echo '(<a href="admin.php">Adminlink</a>)'; } ?>
				<span id="idCenterMapLink"></span>
				<input id="idClustering" type="checkbox" checked="checked"/>
				<label for="idClustering">Clustering</label>
			</div>
		</div>
</body>
</html>
