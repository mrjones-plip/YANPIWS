<?php
/**
 * Assuming a CSV of this structure:
 *  2017-03-22 23:11:43,211,72.5,34
 * Return a multi-dimensional array like this:
 *
 *Array(
 *    [211] => Array(
 *        [2017-03-22 23:08:31] => Array(
 *                    [0] => 2017-03-22 23:08:31
 *                    [1] => 211
 *                    [2] => 72.5
 *                    [3] => 34
 *               )
 *
 * @param string $file where CSV data is
 * @return array of formatted CSV data
 */

function getData($file){
    if (is_file($file) && is_readable($file)){
        $data = file($file);
        $goodData = array();
        foreach ($data as $line){
            $lineArray = explode(",", $line);
            $goodData[$lineArray[1]][$lineArray[0]] = $lineArray;
        }
        foreach (array_keys($goodData) as $id){
            asort($goodData[$id]);
        }

        return $goodData;
    } else {
        return array();
    }
}