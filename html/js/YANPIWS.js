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
 * handle resizing clock to big then small 
 * by toggling between big_time and small_time classes
 */
function setClockSize(state, defaultSize){
    if (state == 'big'){
        $('.small_time').addClass("big_time").removeClass("small_time");
    } else {
        $('.big_time').addClass("small_time").removeClass("big_time");
    }
}

/**
 * AJAX call to get updated content and return JSON
 * @param endpoint URL key where AJAX call lives on server
 * @param target DOM element to push response into
 * @param callback JS function to call upon success
 * @param keyname if JSON key  doesn't match endpoint, override with this
 */
function refreshData(endpoint, target, callback = false, keyname = false){
    let baseUrl = './ajax.php?content=';
    $.getJSON( baseUrl + endpoint, function( data ) {
        // if no keyname was passed, use endpoint as keyname
        if (keyname === false){
            keyname = endpoint;
        }
        $(target).html(data[keyname]);
        if (typeof callback === "function") {
            callback();
        }
    });
}

/**
 * start the dark sky canvas DOM elements animating. intended to call if
 * canvas elements have been updated from refreshForecast()
 */
function animateForecast() {
    const elements = document.querySelectorAll('.forecasticon');
    let canvasArray;
    Array.prototype.forEach.call(
        elements, function(el, i){
            canvasArray = el.getAttribute('id').split('.');
            skycons.add(el.getAttribute('id'), canvasArray[1]);
        }
    );
    skycons.play();
}
