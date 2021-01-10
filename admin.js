// @ts-check

let btn = document.getElementById("btnSubmit");
if (btn) {
    btn.style.display = "";
}

function preparedata() {
    "use strict";
    //initialize local variables
    var Parser = new DOMParser();;
    var serializer = new XMLSerializer();
    var currentDom;
    var locdatatext;

    let teamSelect =/** @type {HTMLSelectElement} */ (document.getElementById("idcacherselect"));
    let inputElem = /** @type {HTMLInputElement} */ (document.getElementById("idlocxmldata"));

    if (0 === teamSelect.selectedIndex) {
        teamSelect.style.backgroundColor = "red";
        return false;
    } else {
        teamSelect.style.backgroundColor = "";
    }


    //transfer XML String to DOM Object
    currentDom = Parser.parseFromString(inputElem.value, "text/xml");
    if (!(currentDom.documentElement.namespaceURI === null || currentDom.documentElement.namespaceURI !== "http://www.mozilla.org/newlayout/xml/parsererror.xml")) {
        //parse Error => clear input, disable submit
        return false;
    }

    if (currentDom.documentElement.nodeName === 'waypoint') {
        let teamfind = (currentDom.documentElement.querySelector('teamfind'));
        teamfind.innerHTML = teamSelect.options[teamSelect.selectedIndex].value;

        let newValue = serializer.serializeToString(currentDom.documentElement);
        inputElem.value = newValue;

        //allow submitting the form
        return true;
    } else if (currentDom.documentElement.nodeName === 'gpx') {
        let wpt = currentDom.querySelector("wpt");
        if (!wpt) {
            return false;
        }
        /*
        <waypoint>
            <name id="GC69BBT">Steinhorster Becken 1 by Sherlock Holmes66</name>
            <coord lat="51.819083" lon="8.530517"/>
            <type>Geocache</type>
            <link text="Cache Details">http://www.geocaching.com/seek/cache_details.aspx?wp=GC69BBT</link>
            <teamfind>both</teamfind>
        </waypoint>
        */
        let doc = Parser.parseFromString('<waypoint></waypoint>', "text/xml");
        // let doc = document.implementation.createDocument(null, null,'text/xml');
        let waypoint = doc.documentElement;

        let cacheId = wpt.querySelector('name').innerHTML;
        //save cacheID for PHP script
        document.getElementById("cacheid").setAttribute("value", cacheId);

        let name = doc.createElement('name');
        name.setAttribute('id', cacheId);
        name.append(
            wpt.querySelector('urlname').innerHTML,
            ' by ',
            wpt.querySelector('placed_by').innerHTML
        );
        let coord = doc.createElement('coord');
        coord.setAttribute('lat', wpt.getAttribute('lat'));
        coord.setAttribute('lon', wpt.getAttribute('lon'));
        let type = doc.createElement('type');
        type.append('Geocache');
        let link = doc.createElement('link');
        link.setAttribute('text', 'Cache Details');
        link.append('http://www.geocaching.com/seek/cache_details.aspx?wp=' + cacheId);
        let teamfind = doc.createElement('teamfind');
        teamfind.append(teamSelect.options[teamSelect.selectedIndex].value);

        waypoint.append(
            '\n\t',
            name, '\n\t',
            coord, '\n\t',
            type, '\n\t',
            link, '\n\t',
            teamfind,'\n'
        );
        let logDate;
        let allLogDates = wpt.querySelectorAll('finder[id="797805"]');
        for (let i = 0; i < allLogDates.length; i++) {
            let logDateNode = allLogDates[i];
            let logType = logDateNode.parentElement.querySelector('type');
            if (logType.innerHTML === 'Found it') {
                logDate = logDateNode.parentElement.querySelector('date').innerHTML;
                // use first
                break;
            }
        }
        if (logDate) {
            let time = doc.createElement('time');
            time.append(logDate);
            waypoint.append('\t', time, '\n');
        }

        let newValue = serializer.serializeToString(doc.documentElement);
        inputElem.value = newValue;
    } else {
        return false;
    }
}