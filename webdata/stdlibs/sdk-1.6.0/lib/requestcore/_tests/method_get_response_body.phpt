--TEST--
Display the response body of the request.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();

	var_dump($http->get_response_body());

	/*#block:["require_once"]*/
?>

--EXPECT--
string(48) "abcdefghijklmnopqrstuvwxyz
0123456789
!@#$%^&*()"
