--TEST--
send_request2_failed

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore();
	$http->set_request_url('');
	$response = $http->send_request(true);
	var_dump($response);
?>

--EXPECT--
bool(false)