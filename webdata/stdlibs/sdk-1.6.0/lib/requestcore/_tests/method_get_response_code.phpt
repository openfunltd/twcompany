--TEST--
Display the response code for the request.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();

	var_dump($http->get_response_code());

	/*#block:["require_once"]*/
?>

--EXPECT--
int(200)
