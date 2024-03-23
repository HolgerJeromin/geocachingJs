// @ts-check
var xmlHttp = null;
// Mozilla, Opera, Chrome, Safari and Internet Explorer (from v7)
if (typeof XMLHttpRequest !== "undefined") {
  xmlHttp = new XMLHttpRequest();
}
if (!xmlHttp) {
  // Internet Explorer 6 and older
  try {
    xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) {
    try {
      xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (e) {
      xmlHttp = null;
    }
  }
}
//collect parameter given by the url
var Parameter_Liste = {};
var wertestring = unescape(window.location.search);
wertestring = wertestring.slice(1);
var paare = wertestring.split("&");
var wert;
for (var i = 0; i < paare.length; i++) {
  const name = paare[i].substring(0, paare[i].indexOf("="));
  wert = paare[i].substring(paare[i].indexOf("=") + 1, paare[i].length);
  Parameter_Liste[name] = wert;
}
paare = [];
wert = null;
wertestring = "";

if (Parameter_Liste.title !== undefined) {
  document.title = Parameter_Liste.title;
}

//make the map full screen
resizeElements();
window.onresize = function () {
  resizeElements();
};

//initialize global variables
let markerGreenOption = null;
let markerBlueOption = null;
let markerRedOption = null;
let markerYellowOption = null;
let map = null;
let AllCacheMarkers = [];

let iconGreen, iconRed, iconBlue;

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
  if (true) {
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

    const iconYellow = L.icon({
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
          document.getElementById("stats").style.display = "inline";
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
          document.getElementById("stats").style.display = "inline";
        } else {
          alert("could not load LOC File from: " + requestURL + " Aborting.");
        }
        return false;
      }
      var greenlocurlXML = xmlHttp.responseXML.documentElement;
      var greenlocurlWaypoints =
        greenlocurlXML.getElementsByTagName("waypoint");
      //call function to insert marker to map
      insertWaypoints(locurlWaypoints, iconGreen);
    }

    if (
      Parameter_Liste.locurl === undefined &&
      Parameter_Liste.greenlocurl === undefined
    ) {
      var requestURL = "./cachedata/sabineholger-found.xml";
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
          document.getElementById("stats").style.display = "inline";
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
          document.getElementById("stats").style.display = "inline";
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
      document.getElementById("stats").style.display = "inline";
    }
    //insert new Table ONCE to prevent multiple reflow/repaint in the browsers
    geocachingmenutable.replaceChild(
      newgeocachingmenutablebody,
      geocachingmenutable.firstChild
    );

    //fill stats field beneath map
    if (document.getElementById("CountFoundAll"))
      document.getElementById("CountFoundAll").firstChild.nodeValue =
        CountFoundHolger + CountFoundSabine + CountFoundBoth;
    if (document.getElementById("CountFoundHolger"))
      document.getElementById("CountFoundHolger").firstChild.nodeValue =
        CountFoundHolger;
    if (document.getElementById("CountFoundSabine"))
      document.getElementById("CountFoundSabine").firstChild.nodeValue =
        CountFoundSabine;
    if (document.getElementById("CountFoundBoth"))
      document.getElementById("CountFoundBoth").firstChild.nodeValue =
        CountFoundBoth;
    if (document.getElementById("CountHidden"))
      document.getElementById("CountHidden").firstChild.nodeValue = CountHidden;

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

    document.getElementById("idClustering").onchange = function (evt) {
      if (evt.target.checked === false) {
        markerCluster.clearLayers();
        markerNoCluster.addLayers(AllCacheMarkers);
      } else {
        markerNoCluster.clearLayers();
        markerCluster.addLayers(AllCacheMarkers);
      }
    };
  } else {
    document.getElementById("geocachingmap").style.backgroundColor = "#DDDDDD";
    document.getElementById("geocachingmap").innerHTML =
      "Sorry, the Map cannot be displayed.";
  }
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
    if (document.getElementById("idCenterMapLink") !== null) {
      document.getElementById("idCenterMapLink").appendChild(node);
    }
  }
  //opera mobile v12 needs this...
  resizeElements();
}

/******************************************************************************
			fill map with waypoints
		*******************************************************************************/
function insertWaypoints(AllWaypoints, forceIcon) {
  for (var i = AllWaypoints.length - 1; i >= 0; i--) {
    //parse position of marker
    //
    var CacheLat = parseFloat(
      AllWaypoints[i].getElementsByTagName("coord")[0].getAttribute("lat")
    );
    var CacheLon = parseFloat(
      AllWaypoints[i].getElementsByTagName("coord")[0].getAttribute("lon")
    );
    var CachePos = new L.LatLng(CacheLat, CacheLon);

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
    var CacheDescription = AllWaypoints[i]
      .getElementsByTagName("name")[0]
      .textContent.trim();
    var CacheDescriptionParts = CacheDescription.split(" by ");
    var CacheTime = AllWaypoints[i]
      .getElementsByTagName("time")[0]
      ?.textContent.substr(0, 10);
    var CacheIcon;
    var CacheFinder = null;

    //waypoints could have a tag "teamfind". Visualize it different!
    if (
      AllWaypoints[i].getElementsByTagName("teamfind")[0] !== undefined &&
      AllWaypoints[i].getElementsByTagName("teamfind")[0].firstChild
        .nodeValue != "both"
    ) {
      CacheFinder =
        AllWaypoints[i].getElementsByTagName("teamfind")[0].firstChild
          .nodeValue;
      CacheFinder =
        CacheFinder.slice(0, 1).toUpperCase() + CacheFinder.slice(1);
      if (
        AllWaypoints[i].getElementsByTagName("teamfind")[0].firstChild
          .nodeValue == "holger"
      ) {
        CountFoundHolger++;
        CacheIcon = iconBlue;
      } else if (
        AllWaypoints[i].getElementsByTagName("teamfind")[0].firstChild
          .nodeValue == "sabine"
      ) {
        CountFoundSabine++;
        CacheIcon = iconRed;
      }
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
    var MenuTR = document.createElement("tr");

    //save marker info in DOM for later use
    MenuTR.setAttribute("cachelat", CacheLat);
    MenuTR.setAttribute("cachelon", CacheLon);
    MenuTR.setAttribute("cacheid", CacheID);
    MenuTR.setAttribute("counttr", CountTR);
    MenuTR.setAttribute(
      "title",
      CacheName + (CacheTime ? "\nfound: " + CacheTime : "")
    );
    MenuTR.style.whiteSpace = "nowrap";
    CountTR++;

    //register the events for the row

    //onmouse over check if marker is visible and open marker if yes
    MenuTR.onmouseover = function (evt) {
      AllCacheMarkers[this.getAttribute("counttr")].openPopup();
    };
    //ondouble click centers and zooms to the marker
    MenuTR.ondblclick = function (evt) {
      map.panTo([this.getAttribute("CacheLat"), this.getAttribute("CacheLon")]);
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
    var MenuTDValue = document.createTextNode(CacheID);
    MenuTD.appendChild(MenuTDValue);
    MenuTR.appendChild(MenuTD);

    //last cell contains the Cachename
    MenuTD = document.createElement("td");
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
function resizeElements() {
  //initialise local variables
  var widthLegend;
  var newheight;
  var newmenuheight;
  var newwidth;

  //check if size is preconfigured. If not than autodetect
  if (Parameter_Liste.height !== undefined) {
    newheight = Parameter_Liste.height;
    newmenuheight = Parameter_Liste.height;
  } else if (window.innerHeight) {
    //W3C DOM
    newmenuheight = window.innerHeight;
    newheight = window.innerHeight - 55;
  } else if (document.documentElement.clientHeight) {
    //IE DOM
    newmenuheight = document.documentElement.clientHeight;
    newheight = document.documentElement.clientHeight - 55;
  }
  if (Parameter_Liste.width !== undefined) {
    newwidth = Parameter_Liste.width;
  } else if (window.innerWidth) {
    //W3C DOM
    newwidth = window.innerWidth;
  } else if (document.documentElement.clientWidth) {
    //IE DOM
    newwidth = document.documentElement.clientWidth;
  }
  if (Parameter_Liste.widthLegend !== undefined) {
    widthLegend = Parameter_Liste.widthLegend;
  } else if (newwidth < 800) {
    widthLegend = newwidth / 4;
  } else {
    widthLegend = 200;
  }

  //set new size and position of all elements
  document.getElementById("geocachingmenu").style.width = widthLegend + "px";
  document.getElementById("geocachingmenu").style.height = newmenuheight + "px";
  document.getElementById("geocachingmap").style.width =
    newwidth - widthLegend - 20 + "px";
  document.getElementById("geocachingmap").style.height = newheight + "px";
  document.getElementById("crosshair").style.left =
    (newwidth - widthLegend) / 2 - 9 + "px";
  document.getElementById("crosshair").style.top =
    "-" + (newheight / 2 + 9) + "px";
}
initmap();
