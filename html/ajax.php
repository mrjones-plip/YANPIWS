<?php

if (isset($_GET['content'])){
    require_once("get_data.php");
    getConfig();
    print fetch_json($_GET['content']);
}
exit;
