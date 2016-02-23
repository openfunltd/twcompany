--TEST--
RequestCore_Exception - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	var_dump(class_exists('RequestCore_Exception'));
?>

--EXPECT--
bool(true)
