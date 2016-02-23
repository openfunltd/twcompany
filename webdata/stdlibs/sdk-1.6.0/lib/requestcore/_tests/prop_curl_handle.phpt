--TEST--
curl_handle

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();
	var_dump($http->curl_handle);
?>

--EXPECTF--
resource(%d) of type (Unknown)
