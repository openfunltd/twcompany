--TEST--
process_response() - No valid cURL handle, nor a response being passed in

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->send_request();

	var_dump($http->process_response(null, null));
?>

--EXPECT--
bool(false)
