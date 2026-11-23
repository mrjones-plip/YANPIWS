<!DOCTYPE html>
<html lang="en-US" style="width: 1920px;height: 720px;">
<head>
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title>Weathercast & Pics</title>
    <style media="screen">
    body, a {
        padding:0 0 0 0 ;
        margin:0 0 0 0 ;
        background-color: black;
        font-size: 10px;
        font-family: Helvetica,serif;
        text-decoration: none;
        overflow-y: hidden;
        overflow-x: hidden;
    }
    iframe {
        border:none;
        margin:0;
        padding:0;
        display: block;
        float: left;
        overflow-y: hidden;
        overflow-x: hidden;
    }
    .mainDiv {
        height: 720px;
        float: left;
        overflow-y: hidden;
        overflow-x: hidden;
    }
    #weatherDash, #picDash {
        width: 47%;
    }
    #picDash {
        padding-left: 6%;
    }
    #weatherDash {
        padding-top: 5%;
    }
    h1 {
        font-family: Helvetica,serif;
    }
    </style>
</head>
<body>
<iframe title="mainDiv" class="mainDiv" id="weatherDash" src="/"></iframe>
<iframe title="picDash" class="mainDiv" id="picDash" src="/pics.php" ></iframe>

</body>
</html>
