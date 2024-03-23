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
<?xml version="1.0" encoding="iso-8859-15"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta name="viewport" content="width=device-width, height=device-height, target-densitydpi=device-dpi, initial-scale=1.0, user-scalable=no" />
		<link type="image/x-icon" href="favicon.ico" rel="shortcut icon"/>
		<title>loc2map by Holger Jeromin</title>
		<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4.5/leaflet.css" />
		<link rel="stylesheet" href="leaflet.markercluster.css" />
		<link rel="stylesheet" href="leaflet-control-Zoomslider.css" />
		<!--[if lte IE 8]>
			<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.ie.css" />
			<link rel="stylesheet" href="leaflet-control-Zoomslider.ie.css" />
		<![endif]-->
		<script src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
<script src="http://cdn.leafletjs.com/leaflet-0.4.5/leaflet.js"></script>
		<script src="leaflet.markercluster-src.js"></script>
		<script src="leaflet-providers-0.0.2.js"></script>
		<script src="leaflet-providers-0.0.2.js"></script>
		<script src="leaflet-layer-google.js"></script>
		<script src="leaflet-layer-bing.js"></script>
		<script src="leaflet-control-permalink.js"></script>
		<script src="leaflet-control-Zoomslider.js" ></script>
	</head>
	<body style="padding:0;margin:0;" onload="initmap()" >
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
				<img style="border:0px;" alt="" src="https//img.geocaching.com/stats/img.aspx?uid=bb140a75-2eb5-414c-a888-8b9d2f714bbb&amp;txt=Statistik+auf+Geocaching.com" /></a>
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
	<script type="text/javascript">
		/* <![CDATA[ */
		var xmlHttp = null;
		// Mozilla, Opera, Chrome, Safari and Internet Explorer (from v7)
		if (typeof XMLHttpRequest !== 'undefined') {
			xmlHttp = new XMLHttpRequest();
		}
		if (!xmlHttp) {
			// Internet Explorer 6 and older
			try {
				xmlHttp  = new ActiveXObject("Msxml2.XMLHTTP");
				} catch(e) {
					try {
						xmlHttp  = new ActiveXObject("Microsoft.XMLHTTP");
					} catch(e) {
						xmlHttp  = null;
				}
			}
		}
		//collect parameter given by the url
		var Parameter_Liste = new Array();
		var wertestring = unescape(window.location.search);
		wertestring = wertestring.slice(1);
		var paare = wertestring.split("&");
		var wert;
		for (var i=0; i < paare.length; i++) {
			name = paare[i].substring(0, paare[i].indexOf("="));
			wert = paare[i].substring(paare[i].indexOf("=")+1, paare[i].length);
			Parameter_Liste[name] = wert;
		}
		paare = null;
		wert = null;
		wertestring = null;

		if (Parameter_Liste.title !== undefined){
			document.title=Parameter_Liste.title;
		}

		//make the map full screen
		resizeElements();
		window.onresize = function(){resizeElements();};

		//initialize global variables
		markerGreenOption = null;
		markerBlueOption = null;
		markerRedOption = null;
		markerYellowOption = null;
		gmap = null;
		AllCacheMarkers = new Array();

		//initialize local variables
		var CountTR = 0;
		var CountHidden = 0;
		var CountFoundHolger = 0;
		var CountFoundSabine = 0;
		var CountFoundBoth = 0;
		var CenterCache = null;
		var CacheLatMin = Number.NaN;
		var CacheLatMax = Number.NaN;
		var CacheLonMin = Number.NaN;
		var CacheLonMax = Number.NaN;

		var markerGroup = null;
		var markerCluster = null;
		var markerNoCluster = null;
		//build tbody element with table header to append menulist entrys later
		//do not work in browser DOM for speed
		var geocachingmenutable = document.getElementById('geocachingmenutable');
		var newgeocachingmenutablebody = document.createElement('tbody');
		var MenuTR = document.createElement('tr');
		var MenuTD = document.createElement('th');
		MenuTR.appendChild(MenuTD);
		MenuTD = document.createElement('th');
		var MenuTDValue = document.createTextNode("ID");
		MenuTD.appendChild(MenuTDValue);
		MenuTR.appendChild(MenuTD);
		MenuTD = document.createElement('th');
		MenuTDValue = document.createTextNode("Cachename");
		MenuTD.appendChild(MenuTDValue);
		MenuTR.appendChild(MenuTD);
		newgeocachingmenutablebody.appendChild(MenuTR);


		//initmap() will be called from onload-event
		function initmap() {
			if (true) {
				map = L.map('geocachingmap');
				markerGroup = new L.layerGroup();
				markerCluster = new L.MarkerClusterGroup({maxClusterRadius:30, disableClusteringAtZoom:11, zoomToBoundsOnClick: false});
				markerNoCluster = new L.MarkerClusterGroup({disableClusteringAtZoom:1, zoomToBoundsOnClick: false});

				/******************************************************************************
					initialize four Icons
				*******************************************************************************/
				iconGreen = L.icon({
					iconUrl: 'green.png',
					iconSize: [12, 20],
					iconAnchor: [6, 20],
					popupAnchor: [8, 8],
					shadowUrl: 'shadow.png',
					shadowSize: [22, 20],
				});
				markerGreenOption = { icon:iconGreen };

				iconBlue = L.icon({
					iconUrl: 'blue.png',
					iconSize: [12, 20],
					iconAnchor: [6, 20],
					popupAnchor: [8, 8],
					shadowUrl: 'shadow.png',
					shadowSize: [22, 20],
				});
				markerBlueOption = { icon:iconBlue };

				iconRed = L.icon({
					iconUrl: 'red.png',
					iconSize: [12, 20],
					iconAnchor: [6, 20],
					popupAnchor: [8, 8],
					shadowUrl: 'shadow.png',
					shadowSize: [22, 20],
				});

				markerRedOption = { icon:iconRed };

				iconYellow = L.icon({
					iconUrl: 'yellow.png',
					iconSize: [12, 20],
					iconAnchor: [6, 20],
					popupAnchor: [8, 8],
					shadowUrl: 'shadow.png',
					shadowSize: [22, 20],
					});
				markerYellowOption = { icon:iconYellow };

				/******************************************************************************
					There are a three possibilities to fill map
						- URL parameter locurl
						- URL parameter greenlocurl
						- fallback to hardcoded two xml files
				*******************************************************************************/
				if (Parameter_Liste.locurl !== undefined){
					//fetch xml file from locurl
					//
					var requestURL = Parameter_Liste.locurl;
					xmlHttp.open('GET', requestURL, false);
					if(xmlHttp.overrideMimeType){
						//we can handle wrong MIME Type from the server, too.
						xmlHttp.overrideMimeType('application/xml');
					}
					xmlHttp.send(null);
					if (xmlHttp.status !== 200 || xmlHttp.responseXML === null){
						var maplabel = document.getElementById("maplabel");
						if (maplabel !== null){
							maplabel.innerText = "could not load LOC File from: "+requestURL+" Aborting.";
							//unhide stats bar
							document.getElementById("stats").style.display = "inline";
						}else{
							alert ("could not load LOC File from: "+requestURL+" Aborting.");
						}
						return false;
					}
					var locurlXML=xmlHttp.responseXML.documentElement;
					var locurlWaypoints = locurlXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(locurlWaypoints, iconRed);
				}
				if (Parameter_Liste.greenlocurl !== undefined){
					//fetch xml file from greenlocurl
					//
					var requestURL = Parameter_Liste.greenlocurl;
					xmlHttp.open('GET', requestURL, false);
					if(xmlHttp.overrideMimeType){
						//we can handle wrong MIME Type from the server, too.
						xmlHttp.overrideMimeType('application/xml');
					}
					xmlHttp.send(null);
					if (xmlHttp.status !== 200 || xmlHttp.responseXML === null){
						var maplabel = document.getElementById("maplabel");
						if (maplabel !== null){
							maplabel.innerText = "could not load LOC File from: "+requestURL+" Aborting.";
							//unhide stats bar
							document.getElementById("stats").style.display = "inline";
						}else{
							alert ("could not load LOC File from: "+requestURL+" Aborting.");
						}
						return false;
					}
					var greenlocurlXML=xmlHttp.responseXML.documentElement;
					var greenlocurlWaypoints = greenlocurlXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(locurlWaypoints, iconGreen);
				}

				if (Parameter_Liste.locurl === undefined && Parameter_Liste.greenlocurl === undefined){
					var requestURL = './cachedata/sabineholger-found.xml';
					xmlHttp.open('GET', requestURL, false);
					if(xmlHttp.overrideMimeType){
						//we can handle wrong MIME Type from the server, too.
						xmlHttp.overrideMimeType('application/xml');
					}
					xmlHttp.send(null);
					if (xmlHttp.status !== 200 || xmlHttp.responseXML === null){
						var maplabel = document.getElementById("maplabel");
						if (maplabel !== null){
							maplabel.innerText = "could not load LOC File from: "+requestURL+" Aborting.";
							//unhide stats bar
							document.getElementById("stats").style.display = "inline";
						}else{
							alert ("could not load LOC File from: "+requestURL+" Aborting.");
						}
						return false;
					}

					var FoundXML=xmlHttp.responseXML.documentElement;
					var AllWaypoints = FoundXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(AllWaypoints, null);

					var requestURL = './cachedata/sabineholger-hidden.xml';
					xmlHttp.open('GET', requestURL, false);
					if(xmlHttp.overrideMimeType){
						//we can handle wrong MIME Type from the server, too.
						xmlHttp.overrideMimeType('application/xml');
					}
					xmlHttp.send(null);
					if (xmlHttp.status !== 200 || xmlHttp.responseXML === null){
						var maplabel = document.getElementById("maplabel");
						if (maplabel !== null){
							maplabel.innerText = "could not load LOC File from: "+requestURL+" Aborting.";
							//unhide stats bar
							document.getElementById("stats").style.display = "inline";
						}else{
							alert ("could not load LOC File from: "+requestURL+" Aborting.");
						}
						return false;
					}
					var HiddenXML=xmlHttp.responseXML.documentElement;
					var AllWaypointsHidden = HiddenXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(AllWaypointsHidden, iconYellow);

					//unhide stats bar
					document.getElementById("stats").style.display = "inline";
				}
				//insert new Table ONCE to prevent multiple reflow/repaint in the browsers
				geocachingmenutable.replaceChild(newgeocachingmenutablebody, geocachingmenutable.firstChild);

				//fill stats field beneath map
				if(document.getElementById('CountFoundAll'))
					document.getElementById('CountFoundAll').firstChild.nodeValue = CountFoundHolger+CountFoundSabine+CountFoundBoth;
				if(document.getElementById('CountFoundHolger'))
					document.getElementById('CountFoundHolger').firstChild.nodeValue = CountFoundHolger;
				if(document.getElementById('CountFoundSabine'))
					document.getElementById('CountFoundSabine').firstChild.nodeValue = CountFoundSabine;
				if(document.getElementById('CountFoundBoth'))
					document.getElementById('CountFoundBoth').firstChild.nodeValue = CountFoundBoth;
				if(document.getElementById('CountHidden'))
					document.getElementById('CountHidden').firstChild.nodeValue = CountHidden;

				//center map via url parameter or calculate center automatically
				if (CenterCache !== null){
					map.setView(CenterCache, 15);
				}else{
					map.fitBounds([[CacheLatMin, CacheLonMin],[CacheLatMax, CacheLonMax]]);
				}
				//Add clustered Markers
				markerGroup.addLayer(markerCluster);
				markerGroup.addLayer(markerNoCluster);
				map.addLayer(markerGroup);
				//build a few tile layers
				var lOpenStreetMap = L.TileLayer.provider('OpenStreetMap');
				var lOpenStreetMapDE = L.TileLayer.provider('OpenStreetMap.DE');
				var lOpenCycleMap = L.TileLayer.provider('OpenCycleMap');
				var lStamenWater = L.TileLayer.provider('Stamen.Watercolor');
				var lGoogleRoad = new L.Google("ROADMAP");
				var lGoogleHybrid = new L.Google("HYBRID");
				var lGoogleSat = new L.Google("SATELLITE");
//				var lBing = new L.BingLayer("Anqm0F_JjIZvT0P3abS6KONpaBaKuTnITRrnYuiJCE0WOhH6ZbE4DzeT6brvKVR5");

				var lOpenPisteMap = L.tileLayer("http://tiles.openpistemap.org/nocontours/{z}/{x}/{y}.png", {attribution:'<a href="http://openpistemap.org">OpenPisteMap</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'});
				var lOpenStreetMapHOT = L.tileLayer('http://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
						attribution: '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>'
				});
				//only add one layer to the current map
				map.addLayer(lOpenStreetMapHOT);
				//add layer switcher, permalink and scale to the map
				var layers = new L.Control.Layers( {
					'OpenStreetMap':lOpenStreetMap,
					'Google Map':lGoogleRoad,
					'Google Satellite':lGoogleSat,
					'Google Hybrid':lGoogleHybrid,
	//				'Bing':lBing,
					'OSMHOT':lOpenStreetMapHOT,
					'OSM.de':lOpenStreetMapDE,
					'OpenCycleMap':lOpenCycleMap,
					'Watercolor':lStamenWater
					}, {"Caches":markerGroup, "OpenPisteMap":lOpenPisteMap}, {collapsed:true});
				map.addControl(layers);
				map.addControl(new L.Control.Permalink({text: 'Permalink', layers: layers}));
				map.addControl(new L.control.scale({imperial:false}));

				document.getElementById("idClustering").onchange = function(evt){
					if(evt.target.checked === false){
						markerCluster.clearLayers();
						markerNoCluster.addLayers(AllCacheMarkers);
					}else{
						markerNoCluster.clearLayers()
						markerCluster.addLayers(AllCacheMarkers);
					}
				}
			} else {
				document.getElementById('geocachingmap').style.backgroundColor = '#DDDDDD';
				document.getElementById('geocachingmap').innerHTML = 'Sorry, the Map cannot be displayed.';
			}
			if (typeof(navigator.geolocation) != "undefined"){
				var node = document.createElement("input");
				node.type = "button";
				node.value = "Center here";
				node.onclick = function(evt){
					map.locate({setView:true, maxZoom:16});
				}
				map.on('locationfound', function(e) {
					L.circle(e.latlng, e.accuracy).addTo(map);
				});
				if (document.getElementById("idCenterMapLink") !== null){
					document.getElementById("idCenterMapLink").appendChild(node);
				}
			}
			//opera mobile v12 needs this...
			resizeElements();
		}

		/******************************************************************************
			fill map with waypoints
		*******************************************************************************/
		function insertWaypoints(AllWaypoints, forceIcon){
			for (var i = AllWaypoints.length - 1; i >=0; i--) {
				//parse position of marker
				//
				var CacheLat = parseFloat(AllWaypoints[i].getElementsByTagName('coord')[0].getAttribute('lat'));
				var CacheLon = parseFloat(AllWaypoints[i].getElementsByTagName('coord')[0].getAttribute('lon'));
				var CachePos = new L.LatLng(CacheLat,CacheLon);

				//maintain position of all caches to be able to autozoom later
				if (CacheLat < CacheLatMin){
					CacheLatMin = CacheLat;
				}else if (CacheLat > CacheLatMax){
					CacheLatMax = CacheLat;
				}else if (isNaN(CacheLatMin)){
					CacheLatMin = CacheLat;
					CacheLatMax = CacheLat;
				}
				if (CacheLon < CacheLonMin){
					CacheLonMin = CacheLon;
				}else if (CacheLon > CacheLonMax){
					CacheLonMax = CacheLon;
				}else if (isNaN(CacheLonMin)){
					CacheLonMin = CacheLon;
					CacheLonMax = CacheLon;
				}

				//parse name and description of Cache
				var CacheID = AllWaypoints[i].getElementsByTagName('name')[0].getAttribute('id');
				var CacheDescription = AllWaypoints[i].getElementsByTagName('name')[0].textContent.trim();
				var CacheDescriptionParts = CacheDescription.split(' by ');
				var CacheTime = AllWaypoints[i].getElementsByTagName('time')[0]?.textContent.substr(0, 10);
				var CacheIcon;
				var CacheFinder = null;

				//waypoints could have a tag "teamfind". Visualize it different!
				if (AllWaypoints[i].getElementsByTagName('teamfind')[0] !== undefined && AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue != "both"){
					CacheFinder = AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue;
					CacheFinder = CacheFinder.slice(0, 1).toUpperCase()+CacheFinder.slice(1);
					if (AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue == "holger"){
						CountFoundHolger++;
						CacheIcon = iconBlue;
					}else if (AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue == "sabine"){
						CountFoundSabine++;
						CacheIcon = iconRed;
					}
				}else{
					if (forceIcon === null){
						CountFoundBoth++;
						CacheIcon = iconGreen;
					}else{
						CountHidden++;
						CacheIcon = forceIcon;
					}
				}
				//check if this should cache should center the map
				if (Parameter_Liste.centerWP !== undefined && Parameter_Liste.centerWP.toUpperCase() === CacheID){
					CenterCache = CachePos;
					//make the icon yellow
					CacheIcon = iconYellow;
				}

				var CacheText =	"CacheID: "+
										"<a target='_blank' href='http://www.geocaching.com/seek/cache_details.aspx?wp="+CacheID+"'>"+
										CacheID+"</a> ";

				//check if the cache name is standardconform
				if (CacheDescriptionParts.length === 1){
					//for example "NearbyWater"
					window.alert('Error, wrong syntax of CacheName in XML. Missing cache owner (string " by "). ID:'+CacheID+" cacheName: "+CacheDescription);
				}else if (CacheDescriptionParts.length == 2){
					//"NearbyWater I by BlueSheep"
					CacheText += "von <i>"+CacheDescriptionParts[1]+"</i>";
					CacheText += ":<br />";
					CacheText += "<strong>"+CacheDescriptionParts[0]+"</strong><br />";
					CacheName = CacheDescriptionParts[0];
				}else{
					//"Near by Water I by BlueSheep"
					CacheText += ":<br />";
					CacheText += CacheDescription+"<br />";
					CacheName = CacheDescription;
				}
				if (CacheFinder !== null){
					CacheText += "Found by "+CacheFinder+"<br />";
				}
				if (CacheTime){
					CacheText += "Found: "+CacheTime+"<br />";
				}
				CacheText += "<a href='https://www.openstreetmap.org/?mlat=" + CacheLat + "&mlon=" + CacheLon + "#map=17/" + CacheLat + "/" + CacheLon + "'>OpenStreetMap at this location</a>";

				//build marker with right info and add to the map
				var CacheMarker = L.marker(CachePos, {
					icon: CacheIcon,
					title: CacheID+": "+CacheName+(CacheTime?"\nFound: "+CacheTime:"")});
				CacheMarker.bindPopup(CacheText);
				AllCacheMarkers[CountTR] = CacheMarker;
				markerCluster.addLayer(CacheMarker);

				/******************************************************************************
					build menu item for marker
				*******************************************************************************/
				var MenuTR = document.createElement('tr');

				//save marker info in DOM for later use
				MenuTR.setAttribute('cachelat', CacheLat);
				MenuTR.setAttribute('cachelon', CacheLon);
				MenuTR.setAttribute('cacheid', CacheID);
				MenuTR.setAttribute('counttr', CountTR);
				MenuTR.setAttribute('title', CacheName+(CacheTime?'\nfound: '+CacheTime:''));
				MenuTR.style.whiteSpace = "nowrap";
				CountTR++;

				//register the events for the row

				//onmouse over check if marker is visible and open marker if yes
				MenuTR.onmouseover = function(evt){
					AllCacheMarkers[this.getAttribute('counttr')].openPopup();
				};
				//ondouble click centers and zooms to the marker
				MenuTR.ondblclick = function(evt){
					map.panTo([this.getAttribute('CacheLat'), this.getAttribute('CacheLon')]);
					map.zoomIn();
					evt.cancelBubble = true;
					if (evt.stopPropagation) evt.stopPropagation();
					if (evt.preventDefault) evt.preventDefault();
				};

				//first cell contains the icon
				var MenuTD = document.createElement('td');
				var MenuImg = document.createElement('img');
				MenuImg.setAttribute('src', CacheIcon.options.iconUrl);
				MenuTD.appendChild(MenuImg);
				MenuTR.appendChild(MenuTD);

				//second cell contains the cache ID
				MenuTD = document.createElement('td');
				var MenuTDValue = document.createTextNode(CacheID);
				MenuTD.appendChild(MenuTDValue);
				MenuTR.appendChild(MenuTD);

				//last cell contains the Cachename
				MenuTD = document.createElement('td');
				MenuTDValue = document.createTextNode(CacheName);
				MenuTD.appendChild(MenuTDValue);
				MenuTR.appendChild(MenuTD);

				//append row to the tbody
				newgeocachingmenutablebody.appendChild(MenuTR);
			}
		}

		/******************************************************************************
			resize map to fullsize
				called onload and onresize
		*******************************************************************************/
		function resizeElements(){
			//initialise local variables
			var widthLegend;
			var newheight;
			var newmenuheight;
			var newwidth;

			//check if size is preconfigured. If not than autodetect
			if (Parameter_Liste.height !== undefined){
				newheight = Parameter_Liste.height;
				newmenuheight = Parameter_Liste.height;
			}else if (window.innerHeight){
				//W3C DOM
				newmenuheight = window.innerHeight;
				newheight = window.innerHeight - 55;
			}else if (document.documentElement.clientHeight){
				//IE DOM
				newmenuheight = document.documentElement.clientHeight;
				newheight = document.documentElement.clientHeight - 55;
			}
			if (Parameter_Liste.width !== undefined){
				newwidth = Parameter_Liste.width;
			}else if (window.innerWidth){
				//W3C DOM
				newwidth = window.innerWidth;
			}else if (document.documentElement.clientWidth){
				//IE DOM
				newwidth = document.documentElement.clientWidth;
			}
			if (Parameter_Liste.widthLegend !== undefined)
			{
				widthLegend=Parameter_Liste.widthLegend;
			}else if(newwidth < 800){
				widthLegend = newwidth / 4;
			}else{
				widthLegend=200;
			}

			//set new size and position of all elements
			document.getElementById('geocachingmenu').style.width = (widthLegend)+"px";
			document.getElementById('geocachingmenu').style.height = (newmenuheight)+"px";
			document.getElementById('geocachingmap').style.width = (newwidth - widthLegend - 20)+"px";
			document.getElementById('geocachingmap').style.height = (newheight)+"px";
			document.getElementById('crosshair').style.left = ((newwidth - widthLegend)/2-9)+"px";
			document.getElementById('crosshair').style.top = "-"+(newheight/2+9)+"px";
		}
	/* ]]> */
	</script>
</body>
</html>
