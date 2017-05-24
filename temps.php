
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <script src="jquery.3.2.1.slim.min.js" ></script>
    <script src="jquery.jqplot.min.js" ></script>
    <link rel="stylesheet" href="HappyHistogram.min.css">
    <link rel="stylesheet" href="jquery.jqplot.min.css">
    <style>
        .chart { height: 120px; width:70%; float:left;}
        .temp, .temp .label { font-size: 15pt; float: left; }
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
            echo "
                <div id='chart{$count}' class='chart'></div>
                <div class='temp' id='temp{$count}'></div>
                ";
            $count++;
        }
        ?>
    </div>
</div>

<script src="./YANPIWS.js"></script>
<script>
    temp = {
        grid: {
            backgroundColor: "black",
            gridLineColor: 'grey',
            gridLineWidth: false
        },
        seriesStyles: {
            color: "white",
            lineWidth: 2,
            markerOptions: {
                show: false
            }
        },
    };
    <?php
    $count = 1;
    foreach ($YANPIWS['labels'] as $id => $label){
        $hourlyTemps = convertDataToHourly($data[$id]);
        echo "\n\trefreshTemp($id,$count);\n";
        echo "
            var plot{$count} = $.jqplot ('chart{$count}', [[" . implode(",", $hourlyTemps) . "]]
            ,{
                axes: {
                    xaxis: {
                      pad: .5
                    }
                  }
                }
            );
            temp = plot{$count}.themeEngine.newTheme('temp', temp);
            plot{$count}.activateTheme('temp');
        ";
        $count++;
    }
    ?>

</script>

</body>
</html>