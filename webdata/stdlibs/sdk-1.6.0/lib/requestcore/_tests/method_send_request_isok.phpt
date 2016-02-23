--TEST--
Standard request with a standard (successful) response code.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_method($http::HTTP_PUT);
	$response = $http->send_request(true);

	var_dump($response->isOK());

	/*#block:["require_once"]*/
?>

--EXPECT--
bool(true)
