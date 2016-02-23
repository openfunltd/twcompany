--TEST--
request_headers

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://example.com');
	$http->prep_request();
	var_dump($http->request_headers);
?>

--EXPECT--
array(2) {
  ["Expect"]=>
  string(12) "100-continue"
  ["Connection"]=>
  string(5) "close"
}
