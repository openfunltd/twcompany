--TEST--
send_multi_request_failed

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore();
	$responses = $http->send_multi_request(array(
		$http->set_request_url('')->prep_request()
	));

	$bodies = array(
		$responses[0]
	);

	var_dump($bodies);
?>

--EXPECT--
array(1) {
  [0]=>
  bool(false)
}
