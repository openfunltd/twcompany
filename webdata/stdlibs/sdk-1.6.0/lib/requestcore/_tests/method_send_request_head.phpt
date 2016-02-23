--TEST--
Send a HEAD request instead of a GET request, and display the response's Content-Type and non-body (since this is a HEAD request).

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_method($http::HTTP_HEAD);
	$response = $http->send_request(true);

	var_dump($response->header['content-type']);
	var_dump($response->body);

	/*#block:["require_once"]*/
?>

--EXPECT--
string(25) "text/plain; charset=utf-8"
bool(false)
