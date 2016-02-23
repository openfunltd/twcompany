--TEST--
RequestCore - Object type

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	$http = new RequestCore();
	var_dump(get_class($http));
?>

--EXPECT--
string(11) "RequestCore"
