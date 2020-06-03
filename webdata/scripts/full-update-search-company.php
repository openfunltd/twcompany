<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
//Pix_Table::enableLog(Pix_Table::LOG_QUERY);
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$command = '';
$count = 0;

$last_show = microtime(true);
foreach (Unit::search(1)->volumemode(100000) as $unit) {
    if (microtime(true) - $last_show > 3) {
        $title = $unit->id();
        fwrite(STDERR, chr(27) . "k{$title}" . chr(27) . "\\");
        $last_show = microtime(true);
    }
    $command .= json_encode(array(
        'update' => array('_id' => $unit->id()),
    )) . "\n";
    $command .= json_encode(array(
        'doc' => $unit->getSearchData(),
        'doc_as_upsert' => true,
    )) . "\n";
    $count ++;

    if ($count >= 1000) {
        $url = getenv('SEARCH_URL') . '/company/_bulk';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
        $ret = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201))) {
            throw new Exception($info['http_code'] . ' ' . $ret);
        }
        $count = 0;
        $command = '';
        if ($ret->errors) {
            print_r($ret);
            exit;
        }
    }

}

if ($count) {
        $url = getenv('SEARCH_URL') . '/company/_bulk';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
        $ret = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201))) {
            throw new Exception($info['http_code'] . ' ' . $ret);
        }
        $count = 0;
        $command = '';
        if ($ret->errors) {
            print_r($ret);
            exit;
        }
}
