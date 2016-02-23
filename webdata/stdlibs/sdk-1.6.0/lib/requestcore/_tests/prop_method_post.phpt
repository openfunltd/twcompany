--TEST--
method::HTTP_POST

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_method($http::HTTP_POST);
	$http->send_request();
	var_dump($http->method);
?>

--EXPECT--
string(4) "POST"
