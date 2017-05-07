/**
 * do a GET and stuff results into an ID, optinally call call back
 * thanks http://stackoverflow.com/a/8567149 - look ma - no jquery!
 *
 * @param URL string URL to fetch contnet from
 * @param targetId string DOM ID where to innerHTML the result
 * @param callback function to callback when done, optional
 */
function loadXMLDoc(URL, targetId, callback) {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
            if (xmlhttp.status == 200) {
                document.getElementById(targetId).innerHTML = xmlhttp.responseText;

                if (typeof callback === "function") {
                    callback();
                }
            } else {
                document.getElementById(targetId).innerHTML = "AJAX Failed :(";
            }
        }
    };

    xmlhttp.open("GET", URL, true);
    xmlhttp.send();

}

/**
 * AJAX call to get updated forecast
 */
function refreshForecast(){
    loadXMLDoc('./ajax.php?content=forecast', 'forecast', animateForecast);
}

/**
 * AJAX call to get updated sunset time
 */
function refreshSunset(){
    loadXMLDoc('./ajax.php?content=sunset', 'sunset');
}

/**
 * AJAX call to get updated sunrise time
 */
function refreshSunrise(){
    loadXMLDoc('./ajax.php?content=sunrise', 'sunrise');
}

/**
 * AJAX call to get updated date and time
 */
function refeshDateTime(){
    loadXMLDoc('./ajax.php?content=datetime', 'datetime');
}

/**
 * AJAX call to get updated temps
 *
 * @param id int of sensor ID
 * @param id2 string of the DOM ID to put the results in - will concat "temp" + id2
 */
function refreshTemp(id, id2){
    loadXMLDoc('./ajax.php?content=temp&id=' + id, 'temp' + id2);
}

/**
 * AJAX call to get updated wind speed
 */
function refreshCurrentWind(){
    loadXMLDoc('./ajax.php?content=wind_now', 'wind_now');
}

/**
 * start the dark sky canvas DOM elements animating. intended to call if
 * canvas elements have been updated from refreshForecast()
 */
function animateForecast() {
    var elements = document.querySelectorAll('.forecasticon');
    var canvasArray;
    Array.prototype.forEach.call(
        elements, function(el, i){
            canvasArray = el.getAttribute('id').split('.');
            skycons.add(el.getAttribute('id'), canvasArray[1]);
        }
    );
    skycons.play();
}

/**
 * check that temps aren't old.  if they are, change text to yellow
 */
function checkTempAges(){
    loadXMLDoc('./ajax.php?content=age', 'YANPIWS');
}