<?php
/**
 * 把 import-name_map.php 的列表匯入 elastic sesrch 的 /twcompany/name_map
 */

include(__DIR__ . '/../init.inc.php');

$bulkInsert = function($command){
    error_log(substr($command, 0, strpos($command, "\n") - 1));
    $curl = curl_init();
    $url = getenv('SEARCH_URL') . "/twcompany/name_map/_bulk";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
    $ret = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if (!in_array($info['http_code'], array(200, 201))) {
        throw new Exception($info['http_code'] . $ret);
    }
};

$fp = fopen($_SERVER['argv'][1], 'r');
$command = '';
while ($line = trim(fgets($fp))) {
    list($id, $name) = explode(' ', $line, 2);
    if (!json_encode($name)) {
        continue;
    }
    $name = str_replace('　', '', $name);


    $command .= json_encode(array(
        'create' => array(
            '_id' => $id . '-' . $name,
        ),
    ), JSON_UNESCAPED_UNICODE) . "\n";
    $command .= json_encode(array(
        'name' => $name,
        'id' => $id,
    ), JSON_UNESCAPED_UNICODE) . "\n";

    if (strlen($command) > 655360) {
        $bulkInsert($command);
        $command = '';
    }
}
if (strlen($command)) {
    $bulkInsert($command);
    $command = '';
}
