--TEST--
request_url

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://example.com');
	echo $http->request_url;
?>

--EXPECT--
http://example.com
