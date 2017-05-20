
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <script src="HappyHistogram.min.js" ></script>
    <link rel="stylesheet" href="HappyHistogram.min.css">
    <style>
        .yearHistogram .month .chart { height: 20px; }
        .yearHistogram .month { width:50%; }
        .yearHistogram .yAxisLabel {font-size: 9pt }
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
            echo "\t$label<div id='histogram{$count}'></div>\n";
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
        $hourlyTemps = convertDataToHourly($data[$id]);
        echo "\t\trefreshTemp($id,$count);\n";
        echo "
            var Year{$count} = [
                [" . implode(",", $hourlyTemps) . "]
            ];
        ";
        echo "\t\tHappyHistogram('histogram{$count}', Year{$count}, 'white');\n";
        $count++;
    }
    ?>

</script>

</body>
</html>