--TEST--
Invalid response code (e.g. 999) should make isOK() fail.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();

	$response = new ResponseCore(
		$http->get_response_header(),
		$http->get_response_body(),
		999
	);

	var_dump($response->isOK());

	/*#block:["require_once"]*/
?>

--EXPECT--
bool(false)
