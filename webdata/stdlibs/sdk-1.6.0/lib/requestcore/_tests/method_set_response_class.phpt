--TEST--
set_request_class

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt', null, array(
		'response' => 'TestResponseClass'
	));
	var_dump($http->response_class);
?>

--EXPECT--
string(17) "TestResponseClass"
