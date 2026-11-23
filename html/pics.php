<?php
global $YANPIWS;
$path = realpath(dirname(__FILE__));
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'get_data.php';
getConfig();

$jsStrAry = get_photos_js_array();
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title>Dynamic Pics Page</title>
    <style media="screen">
    <style>
    body, a, html, img, div, html {
        margin: 0px;
        padding: 0px;
        border: 0px;
        height: auto;
        float: right;
    }
    body {
        margin: 0px;
    }
    img {
        height: 720px;
        float: right;
        max-width: auto;
        max-height: 100%;
        margin: 0px;
        padding: 0px;
        border: 0px;

    }
    div {
        display: flex;
        justify-content: center;
        align-items: center;
        overflow-y: hidden; /* Hide vertical scrollbar */
        overflow-x: hidden; /* Hide horizontal scrollbar */
    }
    </style>
</head>
<body>

    <div>
    <img id="thePhoto" src="" />
    </div>
    <script>
        <?= $jsStrAry ?>
        const interval = setInterval(function() {
            const random = Math.floor(Math.random() * photos.length);
            const img = document.getElementById('thePhoto');
            img.src = '/pics/' + photos[random];
            console.log(photos[random]);
        }, 60000);
        
        const random = Math.floor(Math.random() * photos.length);
        const img = document.getElementById('thePhoto');
        img.src = '/pics/' + photos[random];

    </script>
</body>
</html>
