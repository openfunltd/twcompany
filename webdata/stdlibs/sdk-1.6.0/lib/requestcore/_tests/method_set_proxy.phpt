--TEST--
Pass in a proxy DSN string, and process it with prep_request() (without firing the request).

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_proxy('proxy://user:pass@hostname:80');
	$http->prep_request();

	var_dump($http->proxy);

	/*#block:["require_once"]*/
?>

--EXPECT--
array(5) {
  ["scheme"]=>
  string(5) "proxy"
  ["host"]=>
  string(8) "hostname"
  ["port"]=>
  int(80)
  ["user"]=>
  string(4) "user"
  ["pass"]=>
  string(4) "pass"
}
