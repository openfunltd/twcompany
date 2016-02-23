--TEST--
Set a useragent string to use for HTTP requests.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_useragent('SampleUserAgentString');

	var_dump($http->useragent);

	/*#block:["require_once"]*/
?>

--EXPECT--
string(21) "SampleUserAgentString"
