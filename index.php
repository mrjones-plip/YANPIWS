<?php
require_once ("get_data.php");
$today = date('Y-m-d', time());
$time = date('h:i:s A', time());
$date = date('l jS \of F Y', time());
$allData = getData("./data/" . $today);

$labels = array(
    '211' => 'Inside',
    '109' => 'Outside',
);

$currentTempHtml = '';
foreach (array_keys($allData) as $key){
    $temp = array_pop($allData[$key])[2];
    $label = $labels[$key];
    $currentTempHtml .= "<strong>{$temp}°</strong> $label <br />\n";
}

?>

<link rel="stylesheet" type="text/css" href="styles.css" />

<p><?php echo $date ?></p>
<p><?php echo $time ?></p>
<p><?php echo $currentTempHtml ?></p>

