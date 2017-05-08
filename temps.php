
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <script type="text/javascript" src="./jquery.3.2.1.slim.min.js"></script>
    <script type="text/javascript" src="./jquery.sparklines.2.1.2.min.js"></script>
</head>
<body>
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
            echo "\t<div id='temp{$count}'></div><div class='inlinesparkline'></div><br />\n";
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

    /* <![CDATA[ */
    $(function() {
        $('.inlinesparkline').sparkline([1,4,4,7,5,1,4,4,7,5,1,4,4,7,5,9,10],{
            type: 'line',
            barColor: 'green',
            width: '300px',
            height: '70px',
            lineWidth: '7'
        });
    });
    /* ]]> */
</script>

</body>
</html>