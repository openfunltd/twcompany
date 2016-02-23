--TEST--
response_class

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	var_dump($http->response_class);
?>

--EXPECT--
string(12) "ResponseCore"
