--TEST--
Pass credentials to set_credentials(), and display the password to use.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore('http://github.com/skyzyx/requestcore/raw/master/_tests/test_request.txt');
	$http->set_credentials('user', 'pass');
	$http->prep_request();

	var_dump($http->password);

	/*#block:["require_once"]*/
?>

--EXPECT--
string(4) "pass"
