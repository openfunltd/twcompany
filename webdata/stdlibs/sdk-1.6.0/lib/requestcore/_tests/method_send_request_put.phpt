--TEST--
send_request_head

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_method($http::HTTP_PUT);
	$response = $http->send_request(true);
	var_dump($response->header['_info']['http_code']);
?>

--EXPECT--
int(200)