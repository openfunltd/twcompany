--TEST--
Add a custom header to the request (without firing it).

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->prep_request();
	$http->add_header('x-requestcore-header', 'value');

	var_dump($http->request_headers);

	/*#block:["require_once"]*/
?>

--EXPECT--
array(3) {
  ["Expect"]=>
  string(12) "100-continue"
  ["Connection"]=>
  string(5) "close"
  ["x-requestcore-header"]=>
  string(5) "value"
}
