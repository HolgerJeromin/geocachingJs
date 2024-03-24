// @ts-check

/**
 * @type {any}
 */
let L = window.L;

//collect parameter given by the url
/** @type {Record<string, string|undefined>} */
const Parameter_Liste = {};
for (const [name, value] of new URLSearchParams(
  document.location.search
).entries()) {
  Parameter_Liste[name] = value;
}

if (Parameter_Liste.title !== undefined) {
  document.title = Parameter_Liste.title;
}

//make the map full screen
resizeElements();
window.addEventListener("resize", resizeElements);

//initialize global variables
let markerGreenOption = null;
let markerBlueOption = null;
let markerRedOption = null;
let markerYellowOption = null;
let map = null;
let AllCacheMarkers = [];

let iconGreen, iconRed, iconBlue, iconYellow;

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
var geocachingmenutable = document.getElementById("geocachingmenutable");
var newgeocachingmenutablebody = document.createElement("tbody");
var MenuTR = document.createElement("tr");
var MenuTD = document.createElement("th");
MenuTR.appendChild(MenuTD);
MenuTD = document.createElement("th");
var MenuTDValue = document.createTextNode("ID");
MenuTD.appendChild(MenuTDValue);
MenuTR.appendChild(MenuTD);
MenuTD = document.createElement("th");
MenuTDValue = document.createTextNode("Cachename");
MenuTD.appendChild(MenuTDValue);
MenuTR.appendChild(MenuTD);
newgeocachingmenutablebody.appendChild(MenuTR);

//initmap() will be called from onload-event
function initmap() {
  const statsDiv = /** @type {HTMLDivElement} */ (
    document.getElementById("stats")
  );
  map = L.map("geocachingmap");
  markerGroup = new L.layerGroup();
  markerCluster = new L.MarkerClusterGroup({
    maxClusterRadius: 30,
    disableClusteringAtZoom: 11,
    zoomToBoundsOnClick: false,
  });
  markerNoCluster = new L.MarkerClusterGroup({
    disableClusteringAtZoom: 1,
    zoomToBoundsOnClick: false,
  });

  /******************************************************************************
					initialize four Icons
				*******************************************************************************/
  iconGreen = L.icon({
    iconUrl: "green.png",
    iconSize: [12, 20],
    iconAnchor: [6, 20],
    popupAnchor: [8, 8],
    shadowUrl: "shadow.png",
    shadowSize: [22, 20],
  });
  markerGreenOption = { icon: iconGreen };

  iconBlue = L.icon({
    iconUrl: "blue.png",
    iconSize: [12, 20],
    iconAnchor: [6, 20],
    popupAnchor: [8, 8],
    shadowUrl: "shadow.png",
    shadowSize: [22, 20],
  });
  markerBlueOption = { icon: iconBlue };

  iconRed = L.icon({
    iconUrl: "red.png",
    iconSize: [12, 20],
    iconAnchor: [6, 20],
    popupAnchor: [8, 8],
    shadowUrl: "shadow.png",
    shadowSize: [22, 20],
  });

  markerRedOption = { icon: iconRed };

  iconYellow = L.icon({
    iconUrl: "yellow.png",
    iconSize: [12, 20],
    iconAnchor: [6, 20],
    popupAnchor: [8, 8],
    shadowUrl: "shadow.png",
    shadowSize: [22, 20],
  });
  markerYellowOption = { icon: iconYellow };

  /******************************************************************************
					There are a three possibilities to fill map
						- URL parameter locurl
						- URL parameter greenlocurl
						- fallback to hardcoded two xml files
				*******************************************************************************/
  if (Parameter_Liste.locurl !== undefined) {
    //fetch xml file from locurl
    //
    var requestURL = Parameter_Liste.locurl;
    const xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", requestURL, false);
    if (xmlHttp.overrideMimeType) {
      //we can handle wrong MIME Type from the server, too.
      xmlHttp.overrideMimeType("application/xml");
    }
    xmlHttp.send(null);
    if (xmlHttp.status !== 200 || xmlHttp.responseXML === null) {
      var maplabel = document.getElementById("maplabel");
      if (maplabel !== null) {
        maplabel.innerText =
          "could not load LOC File from: " + requestURL + " Aborting.";
        //unhide stats bar
        statsDiv.style.display = "inline";
      } else {
        alert("could not load LOC File from: " + requestURL + " Aborting.");
      }
      return false;
    }
    var locurlXML = xmlHttp.responseXML.documentElement;
    var locurlWaypoints = locurlXML.getElementsByTagName("waypoint");
    //call function to insert marker to map
    insertWaypoints(locurlWaypoints, iconRed);
  }
  if (Parameter_Liste.greenlocurl !== undefined) {
    //fetch xml file from greenlocurl
    //
    var requestURL = Parameter_Liste.greenlocurl;
    const xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", requestURL, false);
    if (xmlHttp.overrideMimeType) {
      //we can handle wrong MIME Type from the server, too.
      xmlHttp.overrideMimeType("application/xml");
    }
    xmlHttp.send(null);
    if (xmlHttp.status !== 200 || xmlHttp.responseXML === null) {
      var maplabel = document.getElementById("maplabel");
      if (maplabel !== null) {
        maplabel.innerText =
          "could not load LOC File from: " + requestURL + " Aborting.";
        //unhide stats bar
        statsDiv.style.display = "inline";
      } else {
        alert("could not load LOC File from: " + requestURL + " Aborting.");
      }
      return false;
    }
    var greenlocurlXML = xmlHttp.responseXML.documentElement;
    var greenlocurlWaypoints = greenlocurlXML.getElementsByTagName("waypoint");
    //call function to insert marker to map
    insertWaypoints(greenlocurlWaypoints, iconGreen);
  }

  if (
    Parameter_Liste.locurl === undefined &&
    Parameter_Liste.greenlocurl === undefined
  ) {
    var requestURL = "./cachedata/sabineholger-found.xml";
    const xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", requestURL, false);
    if (xmlHttp.overrideMimeType) {
      //we can handle wrong MIME Type from the server, too.
      xmlHttp.overrideMimeType("application/xml");
    }
    xmlHttp.send(null);
    if (xmlHttp.status !== 200 || xmlHttp.responseXML === null) {
      var maplabel = document.getElementById("maplabel");
      if (maplabel !== null) {
        maplabel.innerText =
          "could not load LOC File from: " + requestURL + " Aborting.";
        //unhide stats bar
        statsDiv.style.display = "inline";
      } else {
        alert("could not load LOC File from: " + requestURL + " Aborting.");
      }
      return false;
    }

    var FoundXML = xmlHttp.responseXML.documentElement;
    var AllWaypoints = FoundXML.getElementsByTagName("waypoint");
    //call function to insert marker to map
    insertWaypoints(AllWaypoints, null);

    var requestURL = "./cachedata/sabineholger-hidden.xml";
    xmlHttp.open("GET", requestURL, false);
    if (xmlHttp.overrideMimeType) {
      //we can handle wrong MIME Type from the server, too.
      xmlHttp.overrideMimeType("application/xml");
    }
    xmlHttp.send(null);
    if (xmlHttp.status !== 200 || xmlHttp.responseXML === null) {
      var maplabel = document.getElementById("maplabel");
      if (maplabel !== null) {
        maplabel.innerText =
          "could not load LOC File from: " + requestURL + " Aborting.";
        //unhide stats bar
        statsDiv.style.display = "inline";
      } else {
        alert("could not load LOC File from: " + requestURL + " Aborting.");
      }
      return false;
    }
    var HiddenXML = xmlHttp.responseXML.documentElement;
    var AllWaypointsHidden = HiddenXML.getElementsByTagName("waypoint");
    //call function to insert marker to map
    insertWaypoints(AllWaypointsHidden, iconYellow);

    //unhide stats bar
    statsDiv.style.display = "inline";
  }
  //insert new Table ONCE to prevent multiple reflow/repaint in the browsers
  geocachingmenutable?.replaceChildren(newgeocachingmenutablebody);

  const countFoundAllDiv = document.getElementById("CountFoundAll");
  //fill stats field beneath map
  if (countFoundAllDiv)
    countFoundAllDiv.textContent = (
      CountFoundHolger +
      CountFoundSabine +
      CountFoundBoth
    ).toString();
  const countFoundHolgerDiv = document.getElementById("CountFoundHolger");
  if (countFoundHolgerDiv)
    countFoundHolgerDiv.textContent = CountFoundHolger.toString();
  const countFoundSabineDiv = document.getElementById("CountFoundSabine");
  if (countFoundSabineDiv)
    countFoundSabineDiv.textContent = CountFoundSabine.toString();
  const countFoundBothDiv = document.getElementById("CountFoundBoth");
  if (countFoundBothDiv)
    countFoundBothDiv.textContent = CountFoundBoth.toString();
  const countHiddenDiv = document.getElementById("CountHidden");
  if (countHiddenDiv) countHiddenDiv.textContent = CountHidden.toString();

  //center map via url parameter or calculate center automatically
  if (CenterCache !== null) {
    map.setView(CenterCache, 15);
  } else {
    map.fitBounds([
      [CacheLatMin, CacheLonMin],
      [CacheLatMax, CacheLonMax],
    ]);
  }
  //Add clustered Markers
  markerGroup.addLayer(markerCluster);
  markerGroup.addLayer(markerNoCluster);
  map.addLayer(markerGroup);
  //build a few tile layers
  var lOpenStreetMap = L.TileLayer.provider("OpenStreetMap");
  var lOpenStreetMapDE = L.TileLayer.provider("OpenStreetMap.DE");
  var lOpenCycleMap = L.TileLayer.provider("OpenCycleMap");
  var lStamenWater = L.TileLayer.provider("Stamen.Watercolor");
  var lGoogleRoad = new L.Google("ROADMAP");
  var lGoogleHybrid = new L.Google("HYBRID");
  var lGoogleSat = new L.Google("SATELLITE");
  //				var lBing = new L.BingLayer("Anqm0F_JjIZvT0P3abS6KONpaBaKuTnITRrnYuiJCE0WOhH6ZbE4DzeT6brvKVR5");

  var lOpenPisteMap = L.tileLayer(
    "http://tiles.openpistemap.org/nocontours/{z}/{x}/{y}.png",
    {
      attribution:
        '<a href="http://openpistemap.org">OpenPisteMap</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
    }
  );
  var lOpenStreetMapHOT = L.tileLayer(
    "http://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png",
    {
      attribution:
        '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
    }
  );
  //only add one layer to the current map
  map.addLayer(lOpenStreetMapHOT);
  //add layer switcher, permalink and scale to the map
  var layers = new L.Control.Layers(
    {
      OpenStreetMap: lOpenStreetMap,
      "Google Map": lGoogleRoad,
      "Google Satellite": lGoogleSat,
      "Google Hybrid": lGoogleHybrid,
      //				'Bing':lBing,
      OSMHOT: lOpenStreetMapHOT,
      "OSM.de": lOpenStreetMapDE,
      OpenCycleMap: lOpenCycleMap,
      Watercolor: lStamenWater,
    },
    { Caches: markerGroup, OpenPisteMap: lOpenPisteMap },
    { collapsed: true }
  );
  map.addControl(layers);
  map.addControl(
    new L.Control.Permalink({ text: "Permalink", layers: layers })
  );
  map.addControl(new L.control.scale({ imperial: false }));

  const clusterCheck = /**@type {HTMLInputElement} */ (
    document.getElementById("idClustering")
  );
  if (clusterCheck)
    clusterCheck.onchange = function (evt) {
      if (clusterCheck.checked === false) {
        markerCluster.clearLayers();
        markerNoCluster.addLayers(AllCacheMarkers);
      } else {
        markerNoCluster.clearLayers();
        markerCluster.addLayers(AllCacheMarkers);
      }
    };

  if (typeof navigator.geolocation != "undefined") {
    var node = document.createElement("input");
    node.type = "button";
    node.value = "Center here";
    node.onclick = function (evt) {
      map.locate({ setView: true, maxZoom: 16 });
    };
    map.on("locationfound", function (e) {
      L.circle(e.latlng, e.accuracy).addTo(map);
    });

    document.getElementById("idCenterMapLink")?.append(node);
  }
  //opera mobile v12 needs this...
  resizeElements();
}

/******************************************************************************
			fill map with waypoints
		*******************************************************************************/
/**
 * @param {HTMLCollectionOf<Element>} AllWaypoints
 * @param {object|null} forceIcon
 */
function insertWaypoints(AllWaypoints, forceIcon) {
  for (var i = AllWaypoints.length - 1; i >= 0; i--) {
    //parse position of marker
    //
    const CacheLat = parseFloat(
      AllWaypoints[i].getElementsByTagName("coord")[0].getAttribute("lat") ??
        "0"
    );
    const CacheLon = parseFloat(
      AllWaypoints[i].getElementsByTagName("coord")[0].getAttribute("lon") ??
        "0"
    );
    const CachePos = new L.LatLng(CacheLat, CacheLon);

    //maintain position of all caches to be able to autozoom later
    if (CacheLat < CacheLatMin) {
      CacheLatMin = CacheLat;
    } else if (CacheLat > CacheLatMax) {
      CacheLatMax = CacheLat;
    } else if (isNaN(CacheLatMin)) {
      CacheLatMin = CacheLat;
      CacheLatMax = CacheLat;
    }
    if (CacheLon < CacheLonMin) {
      CacheLonMin = CacheLon;
    } else if (CacheLon > CacheLonMax) {
      CacheLonMax = CacheLon;
    } else if (isNaN(CacheLonMin)) {
      CacheLonMin = CacheLon;
      CacheLonMax = CacheLon;
    }

    //parse name and description of Cache
    var CacheID = AllWaypoints[i]
      .getElementsByTagName("name")[0]
      .getAttribute("id");
    var CacheDescription =
      AllWaypoints[i].getElementsByTagName("name")[0].textContent?.trim() ?? "";
    var CacheDescriptionParts = CacheDescription.split(" by ");
    var CacheTime = AllWaypoints[i]
      .getElementsByTagName("time")[0]
      ?.textContent?.substr(0, 10);
    var CacheIcon;
    var CacheFinder = null;

    //waypoints could have a tag "teamfind". Visualize it different!
    if (
      AllWaypoints[i].getElementsByTagName("teamfind")[0]?.firstChild
        ?.nodeValue == "holger"
    ) {
      CacheFinder = "Holger";
      CountFoundHolger++;
      CacheIcon = iconBlue;
    } else if (
      AllWaypoints[i].getElementsByTagName("teamfind")[0]?.firstChild
        ?.nodeValue == "sabine"
    ) {
      CacheFinder = "Sabine";
      CountFoundSabine++;
      CacheIcon = iconRed;
    } else {
      if (forceIcon === null) {
        CountFoundBoth++;
        CacheIcon = iconGreen;
      } else {
        CountHidden++;
        CacheIcon = forceIcon;
      }
    }
    //check if this should cache should center the map
    if (
      Parameter_Liste.centerWP !== undefined &&
      Parameter_Liste.centerWP.toUpperCase() === CacheID
    ) {
      CenterCache = CachePos;
      //make the icon yellow
      CacheIcon = iconYellow;
    }

    var CacheText =
      "CacheID: " +
      "<a target='_blank' href='http://www.geocaching.com/seek/cache_details.aspx?wp=" +
      CacheID +
      "'>" +
      CacheID +
      "</a> ";
    let CacheName;
    //check if the cache name is standardconform
    if (CacheDescriptionParts.length === 1) {
      //for example "NearbyWater"
      window.alert(
        'Error, wrong syntax of CacheName in XML. Missing cache owner (string " by "). ID:' +
          CacheID +
          " cacheName: " +
          CacheDescription
      );
    } else if (CacheDescriptionParts.length == 2) {
      //"NearbyWater I by BlueSheep"
      CacheText += "von <i>" + CacheDescriptionParts[1] + "</i>";
      CacheText += ":<br />";
      CacheText += "<strong>" + CacheDescriptionParts[0] + "</strong><br />";
      CacheName = CacheDescriptionParts[0];
    } else {
      //"Near by Water I by BlueSheep"
      CacheText += ":<br />";
      CacheText += CacheDescription + "<br />";
      CacheName = CacheDescription;
    }
    if (CacheFinder !== null) {
      CacheText += "Found by " + CacheFinder + "<br />";
    }
    if (CacheTime) {
      CacheText += "Found: " + CacheTime + "<br />";
    }
    CacheText +=
      "<a href='https://www.openstreetmap.org/?mlat=" +
      CacheLat +
      "&mlon=" +
      CacheLon +
      "#map=17/" +
      CacheLat +
      "/" +
      CacheLon +
      "'>OpenStreetMap at this location</a>";

    //build marker with right info and add to the map
    var CacheMarker = L.marker(CachePos, {
      icon: CacheIcon,
      title:
        CacheID + ": " + CacheName + (CacheTime ? "\nFound: " + CacheTime : ""),
    });
    CacheMarker.bindPopup(CacheText);
    AllCacheMarkers[CountTR] = CacheMarker;
    markerCluster.addLayer(CacheMarker);

    /******************************************************************************
					build menu item for marker
				*******************************************************************************/
    let MenuTR = document.createElement("tr");

    //save marker info in DOM for later use
    MenuTR.setAttribute("cachelat", CacheLat + "");
    MenuTR.setAttribute("cachelon", CacheLon + "");
    MenuTR.setAttribute("cacheid", CacheID ?? "");
    MenuTR.setAttribute("counttr", CountTR + "");
    MenuTR.setAttribute(
      "title",
      CacheName + (CacheTime ? "\nfound: " + CacheTime : "")
    );
    MenuTR.style.whiteSpace = "nowrap";
    CountTR++;

    //register the events for the row

    //onmouse over check if marker is visible and open marker if yes
    MenuTR.onmouseover = function (evt) {
      AllCacheMarkers[MenuTR.getAttribute("counttr") ?? 0].openPopup();
    };
    //ondouble click centers and zooms to the marker
    MenuTR.ondblclick = function (evt) {
      map.panTo([
        MenuTR.getAttribute("CacheLat"),
        MenuTR.getAttribute("CacheLon"),
      ]);
      map.zoomIn();
      evt.cancelBubble = true;
      if (evt.stopPropagation) evt.stopPropagation();
      if (evt.preventDefault) evt.preventDefault();
    };

    //first cell contains the icon
    var MenuTD = document.createElement("td");
    var MenuImg = document.createElement("img");
    MenuImg.setAttribute("src", CacheIcon.options.iconUrl);
    MenuTD.appendChild(MenuImg);
    MenuTR.appendChild(MenuTD);

    //second cell contains the cache ID
    MenuTD = document.createElement("td");
    var MenuTDValue = document.createTextNode(CacheID ?? "");
    MenuTD.appendChild(MenuTDValue);
    MenuTR.appendChild(MenuTD);

    //last cell contains the Cachename
    MenuTD = document.createElement("td");
    MenuTDValue = document.createTextNode(CacheName ?? "");
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
function resizeElements() {
  //check if size is preconfigured. If not than autodetect
  const newheight = Parameter_Liste.height
    ? +Parameter_Liste.height
    : window.innerHeight ?? document.documentElement.clientHeight;
  const newmenuheight = newheight - 55;

  const newwidth = Parameter_Liste.width
    ? +Parameter_Liste.width
    : window.innerWidth;

  let widthLegend;

  if (Parameter_Liste.widthLegend !== undefined) {
    widthLegend = +Parameter_Liste.widthLegend;
  } else if (newwidth < 800) {
    widthLegend = newwidth / 4;
  } else {
    widthLegend = 200;
  }

  //set new size and position of all elements
  const menu = document.getElementById("geocachingmenu");
  if (menu) {
    menu.style.width = widthLegend + "px";
    menu.style.height = newmenuheight + "px";
  }
  const map = document.getElementById("geocachingmap");
  if (map) {
    map.style.width = newwidth - widthLegend - 20 + "px";
    map.style.height = newheight + "px";
  }
  const crossHair = document.getElementById("crosshair");
  if (crossHair) {
    crossHair.style.left = (newwidth - widthLegend) / 2 - 9 + "px";
    crossHair.style.top = "-" + (newheight / 2 + 9) + "px";
  }
}
initmap();
