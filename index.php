<?php
require('/var/www/sabineholgeraccess.php');

/* ********************************************************************************
	Visualisation of Geocachingpoints out of a .loc file
	JavaScript only implementation, inspired and API conform from GMAPLOC http://www.henning-mersch.de/projects.html
	Loc2Map Version: 1.1.1
	for new version visit http://www.katur.de/
	
	Copyright (c) 2011, Holger Jeromin <jeromin(at)hitnet.rwth-aachen.de>
	
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
*/

//This is the google maps key, default is for www.sklinke.de
//$gmapkey = isset($_GET['gmapkey'])?$_GET['gmapkey']:'ABQIAAAAtkatt47Y0Sqm3_HAhu5P0hQKFtqXg7fvovgtrsWMi0FGR5r5fhQCwu7POfUVie-OhzgjrBcz4Y87sg'; 
$gmapkey = isset($_GET['gmapkey'])?$_GET['gmapkey']:'ABQIAAAAtkatt47Y0Sqm3_HAhu5P0hQKFtqXg7fvovgtrsWMi0FGR5r5fhQCwu7POfUVie-OhzgjrBcz4Y87sg'; 

echo '<?xml version="1.0" encoding="iso-8859-15"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta name="viewport" content="width=device-width, height=device-height, target-densitydpi=device-dpi" />
		<link type="image/x-icon" href="favicon.ico" rel="shortcut icon"/>
		<title>loc2map by Holger Jeromin</title>
		<script src="http://maps.google.com/maps?v=2&amp;file=api&amp;key=<?php echo $gmapkey?>" type="text/javascript"></script>
	</head>
	<body style="padding:0;margin:0;" onload="initGM()" >
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
			<img id="crosshair" style="float:left;position:relative; top:-322px; left:292px;" src="crshair.gif" alt="" width="18" height="18" class="gmnoprint" />
		</div>
		<div id="stats" style="display:none;">
			<div style="float:right;padding:0;margin:0px;">
					<a href="http://www.geocaching.com/profile/?guid=bb140a75-2eb5-414c-a888-8b9d2f714bbb">
				<img style="border:0px;" alt="" src="http://img.groundspeak.com/stats/img.aspx?uid=bb140a75-2eb5-414c-a888-8b9d2f714bbb&amp;txt=Statistik+auf+Geocaching.com" /></a>
			</div>
			<div>
				<img alt="" src="yellow.png"/>(<span id="CountHidden">0</span>): hidden caches; 
				found <span id="CountFoundAll">0</span> in total: 
				<img alt="" src="red.png"/>(<span id="CountFoundSabine">0</span>): Sabine, 
				<img alt="" src="blue.png"/>(<span id="CountFoundHolger">0</span>): Holger,
				<img alt="" src="green.png"/>(<span id="CountFoundBoth">0</span>): both
				<?php if ($internview === true){ echo '(<a href="admin.php">Adminlink</a>)'; } ?>
				<span id="idCenterMapLink"></span>
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
		delete paare;
		delete wertestring;
		
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
		
		//initGM() will be called from onload-event
		function initGM() {
			if (GBrowserIsCompatible() && xmlHttp !== null) { 
				var copyright = new GCopyright(1, new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)),0,'(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>)');
				var copyOSM =	new GCopyrightCollection('Kartendaten &copy; <a href="http://www.openstreetmap.org/">OpenStreetMap</a> Contributors');
				copyOSM.addCopyright(copyright);

				var tilesMapnik = new GTileLayer(copyOSM, 1, 18, {tileUrlTemplate: 'http://tile.openstreetmap.org/{Z}/{X}/{Y}.png'});
				var mapMapnik = new GMapType([tilesMapnik], G_NORMAL_MAP.getProjection(), "OSM");
				
				//init GoogleMap
				gmap = new GMap2(document.getElementById("geocachingmap"), { mapTypes: [mapMapnik] }); // create map
				gmap.addMapType(G_NORMAL_MAP);
				gmap.addMapType(G_SATELLITE_MAP);
				gmap.addMapType(G_HYBRID_MAP);
				gmap.setCenter(new GLatLng(0,0), 13);
				gmap.addControl(new GMapTypeControl());
				gmap.addControl(new GLargeMapControl());
				gmap.addControl(new GScaleControl());
				gmap.enableScrollWheelZoom();
				
				/******************************************************************************
					initialize four GIcons
				*******************************************************************************/
				iconGreen = new GIcon();
				iconGreen.image = 'green.png';
				iconGreen.shadow = 'shadow.png';
				iconGreen.shadowSize = new GSize(22, 20);
				iconGreen.iconSize = new GSize(12,20);
				iconGreen.iconAnchor = new GPoint(6, 20);
				iconGreen.infoWindowAnchor = new GPoint(8, 8);
				markerGreenOption = { icon:iconGreen };
				
				iconBlue = new GIcon();
				iconBlue.image = 'blue.png';
				iconBlue.shadow = 'shadow.png';
				iconBlue.shadowSize = new GSize(22, 20);
				iconBlue.iconSize = new GSize(12,20);
				iconBlue.iconAnchor = new GPoint(6, 20);
				iconBlue.infoWindowAnchor = new GPoint(8, 8);
				markerBlueOption = { icon:iconBlue };
				
				iconRed = new GIcon();
				iconRed.image = 'red.png';
				iconRed.shadow = 'shadow.png';
				iconRed.shadowSize = new GSize(22, 20);
				iconRed.iconSize = new GSize(12,20);
				iconRed.iconAnchor = new GPoint(6, 20);
				iconRed.infoWindowAnchor = new GPoint(8, 8);
				markerRedOption = { icon:iconRed };
				
				iconYellow = new GIcon();
				iconYellow.image = 'yellow.png';
				iconYellow.shadow = 'shadow.png';
				iconYellow.shadowSize = new GSize(22, 20);
				iconYellow.iconSize = new GSize(12,20);
				iconYellow.iconAnchor = new GPoint(6, 20);
				iconYellow.infoWindowAnchor = new GPoint(8, 8);
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
					xmlHttp.open('GET', Parameter_Liste.locurl, false);
					xmlHttp.send(null);
					var locurlXML=xmlHttp.responseXML.documentElement;
					var locurlWaypoints = locurlXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(locurlWaypoints, markerRedOption);
				}
				if (Parameter_Liste.greenlocurl !== undefined){
					//fetch xml file from greenlocurl
					//
					xmlHttp.open('GET', Parameter_Liste.greenlocurl, false);
					xmlHttp.send(null);
					var greenlocurlXML=xmlHttp.responseXML.documentElement;
					var greenlocurlWaypoints = greenlocurlXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(locurlWaypoints, markerGreenOption);
				}
				
				if (Parameter_Liste.locurl === undefined && Parameter_Liste.greenlocurl === undefined){
					xmlHttp.open('GET', './cachedata/sabineholger-found.xml', false);
					xmlHttp.send(null);
					var FoundXML=xmlHttp.responseXML.documentElement;
					var AllWaypoints = FoundXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(AllWaypoints, null);
					
					xmlHttp.open('GET', './cachedata/sabineholger-hidden.xml', false);
					xmlHttp.send(null);
					var HiddenXML=xmlHttp.responseXML.documentElement;
					var AllWaypointsHidden = HiddenXML.getElementsByTagName('waypoint');
					//call function to insert marker to map
					insertWaypoints(AllWaypointsHidden, markerYellowOption);
					
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
					gmap.setCenter(CenterCache,13);
				}else{
					gmap.setCenter(new GLatLng((CacheLatMin+CacheLatMax)/2,(CacheLonMin+CacheLonMax)/2));
					gmap.setZoom(
						gmap.getBoundsZoomLevel(
							new GLatLngBounds(
								new GLatLng(CacheLatMin,CacheLonMin),
								new GLatLng(CacheLatMax,CacheLonMax)
							)
						)
					);
				}
			} else {
				document.getElementById('geocachingmap').style.backgroundColor = '#DDDDDD';
				document.getElementById('geocachingmap').innerHTML = 'Sorry, your Google Map cannot be displayed.';
			}
		}
		
		/******************************************************************************
			fill map with waypoints
		*******************************************************************************/
		function insertWaypoints(AllWaypoints, forceIcon){
			for (var i = 0; i < AllWaypoints.length; i++) {
				//parse position of marker
				//
				var CacheLat = parseFloat(AllWaypoints[i].getElementsByTagName('coord')[0].getAttribute('lat'));
				var CacheLon = parseFloat(AllWaypoints[i].getElementsByTagName('coord')[0].getAttribute('lon'));
				var CachePos = new GLatLng(CacheLat,CacheLon);
				
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
				var CacheDescription = AllWaypoints[i].getElementsByTagName('name')[0].firstChild.nodeValue;
				var CacheDescriptionParts = CacheDescription.split(' by ');
				var CacheIcon;
				var CacheFinder = null;
				
				//waypoints could have a tag "teamfind". Visualize it different!
				if (AllWaypoints[i].getElementsByTagName('teamfind')[0] !== undefined && AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue != "both"){
					CacheFinder = AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue;
					CacheFinder = CacheFinder.slice(0, 1).toUpperCase()+CacheFinder.slice(1);
					if (AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue == "holger"){
						CountFoundHolger++;
						CacheIcon = markerBlueOption;
					}else if (AllWaypoints[i].getElementsByTagName('teamfind')[0].firstChild.nodeValue == "sabine"){
						CountFoundSabine++;
						CacheIcon = markerRedOption;
					}
				}else{
					if (forceIcon === null){
						CountFoundBoth++;
						CacheIcon = markerGreenOption;
					}else{
						CountHidden++;
						CacheIcon = forceIcon;
					}
				}
				//check if this should cache should center the map
				if (Parameter_Liste.centerWP !== undefined && Parameter_Liste.centerWP.toUpperCase() === CacheID){
					CenterCache = CachePos;
					//make the icon yellow
					CacheIcon = markerYellowOption;
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
				
				//build marker with right info and add to the map
				var CacheMarker = createMarker(CachePos, CacheText, CacheIcon);
				gmap.addOverlay(CacheMarker);
				
				//remember marker for mouseover/click events
				AllCacheMarkers[CountTR] = CacheMarker;
				
				/******************************************************************************
					build menu item for marker
				*******************************************************************************/
				var MenuTR = document.createElement('tr');
				
				//save marker info in DOM for later use
				MenuTR.setAttribute('cachelat', CacheLat);
				MenuTR.setAttribute('cachelon', CacheLon);
				MenuTR.setAttribute('cacheid', CacheID);
				MenuTR.setAttribute('counttr', CountTR);
				MenuTR.setAttribute('title', CacheName);
				MenuTR.style.whiteSpace = "nowrap";
				CountTR++;
				
				//register the events for the row
				
				//onmouse over check if marker is visible and open marker if yes
				MenuTR.onmouseover = function(evt){
					if (	gmap.getBounds().containsLatLng(
								new GLatLng(
									parseFloat(this.getAttribute('CacheLat')),
									parseFloat(this.getAttribute('CacheLon'))
								)
							)
						)
					{
						GEvent.trigger(
							AllCacheMarkers[this.getAttribute('counttr')],
							'click');
					}
				};
				//onclick open the marker every time
				MenuTR.onclick = function(evt){
					GEvent.trigger(
						AllCacheMarkers[this.getAttribute('counttr')],
						'click');
					//prevent marking of text
					evt.cancelBubble = true;
					if (evt.stopPropagation) evt.stopPropagation();
					if (evt.preventDefault) evt.preventDefault();
				};
				//ondouble click centers and zooms to the marker
				MenuTR.ondblclick = function(evt){
					gmap.setCenter(
						new GLatLng(
							parseFloat(this.getAttribute('CacheLat')),
							parseFloat(this.getAttribute('CacheLon')))
						,gmap.getZoom()+3);
					//prevent marking of text
					evt.cancelBubble = true;
					if (evt.stopPropagation) evt.stopPropagation();
					if (evt.preventDefault) evt.preventDefault();
				};
				
				//first cell contains the icon
				var MenuTD = document.createElement('td');
				var MenuImg = document.createElement('img');
				MenuImg.setAttribute('src', CacheIcon.icon.image);
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
			create gmap marker and text window
		*******************************************************************************/
		function createMarker(point, text_marker,icon) {
			var marker = new GMarker(point,icon);
			GEvent.addListener(marker, "click", function() {
				marker.openInfoWindowHtml(text_marker);
			});
			return marker;
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
			document.getElementById('geocachingmap').style.width = (newwidth - widthLegend)+"px";
			document.getElementById('geocachingmap').style.height = (newheight)+"px";
			document.getElementById('crosshair').style.left = ((newwidth - widthLegend)/2-9)+"px";
			document.getElementById('crosshair').style.top = "-"+(newheight/2+9)+"px";
		}
		
		//needed for using page with firebug debugging
		function gtbExternal() { };
		
		if (typeof(navigator.geolocation) != "undefined"){
			var node = document.createElement("input");
			node.type = "button";
			node.value = "Center here";
			node.onclick = function(evt){
				navigator.geolocation.getCurrentPosition(GCcenterMap);
			}
			if (document.getElementById("idCenterMapLink") !== null){
				document.getElementById("idCenterMapLink").appendChild(node);
			}
		}
		function GCcenterMap(position){
			var here = new GLatLng(
					position.coords.latitude,
					position.coords.longitude);
			gmap.setCenter(here);
			
			var circlePoints = Array();
			var bounds = new GLatLngBounds();
			var circle;
			var d = position.coords.accuracy/(1000*6378.8);	// radians
			var lat1 = (Math.PI/180)* here.lat(); // radians
			var lng1 = (Math.PI/180)* here.lng(); // radians
			
			for (var a = 0 ; a < 361 ; a++ ) {
				var tc = (Math.PI/180)*a;
				var y = Math.asin(Math.sin(lat1)*Math.cos(d)+Math.cos(lat1)*Math.sin(d)*Math.cos(tc));
				var dlng = Math.atan2(Math.sin(tc)*Math.sin(d)*Math.cos(lat1),Math.cos(d)-Math.sin(lat1)*Math.sin(y));
				var x = ((lng1-dlng+Math.PI) % (2*Math.PI)) - Math.PI ; // MOD function
				var point = new GLatLng(parseFloat(y*(180/Math.PI)),parseFloat(x*(180/Math.PI)));
				circlePoints.push(point);
				bounds.extend(point);
			}
			
			circle = new GPolygon(circlePoints, '#000000', 2, 1, '#000000', 0.15);
			
			gmap.addOverlay(circle);
			
			if (gmap.getBoundsZoomLevel(bounds) < 16){
				gmap.setZoom(gmap.getBoundsZoomLevel(bounds));
			}else if (gmap.getZoom() < 15 ){
				if(window.innerWidth && window.innerWidth < 800){
					gmap.setZoom(16);
				}else{
					gmap.setZoom(15);
				}
			}
		}
	/* ]]> */
	</script>
</body>
</html>
