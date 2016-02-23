--TEST--
Display the URL that was requested.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();
	$info = $http->get_response_header('_info');

	var_dump($info['url']);

	/*#block:["require_once"]*/
?>

--EXPECT--
string(71) "http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt"
