--TEST--
proxy

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt', 'proxy://user:pass@hostname:80');
	var_dump($http->proxy);
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
