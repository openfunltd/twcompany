--TEST--
send_request_failed

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore();
	$response = $http->send_request(true);
	var_dump($response);
?>

--EXPECT--
bool(false)