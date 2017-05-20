
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <script src="HappyHistogram.min.js" ></script>
    <link rel="stylesheet" href="HappyHistogram.min.css">
    <style>
        .yearHistogram .month .chart { height: 50px; }
        .yearHistogram .month { width:50%; }
        .yearHistogram .yAxisLabel {font-size: 20pt }
        .yearHistogram .yAxis { color: white; }
        .yearHistogram { float: lefts; }
    </style>
</head>
<body>
<?php
require_once 'get_data.php';
getConfig();
$forecast = getDarkSkyData();
$status = configIsValid();
$data = getData($YANPIWS['dataPath'] . '/' . date('Y-m-d', time()));
?>
<div class="col">
    <div class="row">
        <?php
        $count = 1;
        foreach ($YANPIWS['labels'] as $id => $label){
            $hourlyTemps = convertDataToHourly($data[$id]);
            die('<pre>' . print_r($hourlyTemps,1));
            echo "\t<div id='temp{$count}'></div><div id='histogram{$count}'></div>\n";
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
        echo "
            var Year{$count} = [
                [1,0,0,0,0,0,3,0,0,0,0,1,0,1,0,1,1,1,2,4,2,1,1,0,7,9,7,3,2,1]
            ];
        ";
        echo "\t\tHappyHistogram('histogram{$count}', Year{$count}, 'white');\n";
        $count++;
    }
    ?>

</script>

</body>
</html>