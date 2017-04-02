/**
 * thanks http://stackoverflow.com/a/8567149
 */
function loadXMLDoc(URL, targetId) {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
            if (xmlhttp.status == 200) {
                document.getElementById(targetId).innerHTML = xmlhttp.responseText;
            } else {
                document.getElementById(targetId).innerHTML = "AJAX Failed :(";
            }
        }
    };

    xmlhttp.open("GET", URL, true);
    xmlhttp.send();
}

function refreshForecast(){
    loadXMLDoc('./ajax.php?content=forecast', 'forecast');
}

function refreshSunset(){
    loadXMLDoc('./ajax.php?content=sunset', 'sunset');
}

function refreshSunrise(){
    loadXMLDoc('./ajax.php?content=sunrise', 'sunrise');
}

function refeshDateTime(){
    loadXMLDoc('./ajax.php?content=datetime', 'datetime');
}

function refreshTemp(id, id2){
    loadXMLDoc('./ajax.php?content=temp&id=' + id, 'temp' + id2 );
}
