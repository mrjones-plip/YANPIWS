
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/styles.css.php" />
    <script src="js/jquery-3.5.1.min.js" ></script>
    <script src="js/jquery.jqplot.min.js" ></script>
    <link rel="stylesheet" href="css/HappyHistogram.min.css">
    <link rel="stylesheet" href="css/jquery.jqplot.min.css">
    <style>
        .chart { height: 98%; width:70%; float:left;}
        .temp, .temp .label { font-size: 15pt; float: right; clear: right; width: 30%;}
        .temp .degrees { text-align: right; }
        .temp .label { text-align: left; }
    </style>
</head>
<body>
<?php
require_once 'get_data.php';
getConfig();
$forecast = fetchRemoteApiDataAndSave('weather');
$status = configIsValid();

// get two days worth of data and merge them so we can ensure we have a
// a rolling 24 hours of data
$data1 = getTodaysData();
$data2 = getYesterdaysData();
$data = mergeDayData($data1 ,$data2, $YANPIWS['labels']);
// todo - mergeDayData doesn't percolate up correctly to the graph to offer more
// than prior 24 hrs

// we start count at 1, so null pad array to skip 0th element
$colors = array(null,'white','red','yellow','blue','green','orange','brown','grey');
?>
<div class="col">
    <div class="row">

        <div id='chart1' class='chart'></div>
        <?php
        $count = 1;
        foreach ($YANPIWS['labels'] as $id => $label){
            $color = $colors[$count];
            echo "<div class='temp' id='temp{$count}' style='color:$color'></div>";
            $count++;
        }
        
        echo "<div class='temp' id='temp{$count}' style='font-size:25pt' > <a href='/'><br /><- Weather</a></div>";
        ?> 

    </div>
</div>

<script src="js/YANPIWS.js"></script>
<script>
    <?php
    $count = 1;
    $tempsJsArray = '';
    $tempsJsSeries = "\n";
    foreach ($YANPIWS['labels'] as $id => $label){
        $color = $colors[$count];
        $hourlyTemps = convertDataToHourly($data[$id]);
        $tempsJsArray .= "\n[";
        foreach ($hourlyTemps as $hour => $temp) {
            $tempsJsArray .= "[$hour,$temp],";
        }
        $tempsJsArray .= '],';

        $tempsJsSeries .= "{color: '$color',markerOptions:{size:0} }";
        if (sizeof($YANPIWS['labels']) > $count){
            $tempsJsSeries .= ",\n";
        } else {
            $tempsJsSeries .= "\n";
        }
        echo "\trefreshTemp($id,$count);\n";
        $count++;
    }
    ?>

    temp = {
        grid: {
            backgroundColor: "black",
            gridLineColor: 'grey',
            gridLineWidth: false
        },

    };
    var plot1 = $.jqplot ('chart1', [<?php print $tempsJsArray?> ],
        {
            axes: {
                xaxis: {
                  pad: .5
                }
              },
            seriesDefaults: {
                rendererOptions: {
                    smooth: true
                },
            },
            series:[<?php print $tempsJsSeries?>]

      },
    );
    temp = plot1.themeEngine.newTheme('temp', temp);
    plot1.activateTheme('temp');

    /**
     * AJAX call to get updated temps
     *
     * @param id int of sensor ID
     * @param id2 string of the DOM ID to put the results in - will concat "temp" + id2
     */
    function refreshTemp(id, id2){
        loadXMLDoc('./ajax.php?content=temp&cooked=yes_sir&id=' + id, 'temp' + id2);
    }
</script>

</body>
</html>
