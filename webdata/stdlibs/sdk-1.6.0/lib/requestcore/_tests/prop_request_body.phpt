--TEST--
request_body

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://example.com');
	$http->set_body('testing');
	$http->prep_request();
	var_dump($http->request_body);
?>

--EXPECT--
string(7) "testing"
