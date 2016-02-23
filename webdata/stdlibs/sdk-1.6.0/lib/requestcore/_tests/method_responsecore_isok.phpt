--TEST--
Successful response code should make isOK() return true.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();

	$response = new ResponseCore(
		$http->get_response_header(),
		$http->get_response_body(),
		$http->get_response_code()
	);

	var_dump($response->isOK());

	/*#block:["require_once"]*/
?>

--EXPECT--
bool(true)
