--TEST--
Collect cURL handles for two requests, fire them, and then display the response bodies.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore();

	$responses = $http->send_multi_request(array(
		$http->set_request_url('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt')->prep_request(),
		$http->set_request_url('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request2.txt')->prep_request()
	));

	$bodies = array(
		$responses[0]->body,
		$responses[1]->body,
	);

	var_dump($bodies);

	/*#block:["require_once"]*/
?>

--EXPECT--
array(2) {
  [0]=>
  string(48) "abcdefghijklmnopqrstuvwxyz
0123456789
!@#$%^&*()"
  [1]=>
  string(48) ")(*&^%$#@!
9876543210
zyxwvutsrqponmljkihgfedcba"
}
