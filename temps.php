
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <script src='skycons/skycons.js'></script>
    <script>var skycons = new Skycons({'color': 'white'});</script>
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css" />

<?php
require_once 'get_data.php';
getConfig();

$forecast = getDarkSkyData();
$status =configIsValid();
?>

<div class="col">
    <div class="row">
        <?php
        $count = 1;
        foreach ($YANPIWS['labels'] as $id => $label){
            echo "\t<div id='temp{$count}'></div><br />\n";
            $count++;
        }
        ?>
    </div>
</div>

<script src="./YANPIWS.js"></script>
<script>
    <?php
    $count = 1;
    foreach ($YANPIWS['labels'] as $id => $label){
        echo "\t\trefreshTemp($id,$count);\n";
        $count++;
    }
    ?>
</script>
</body>
</html>