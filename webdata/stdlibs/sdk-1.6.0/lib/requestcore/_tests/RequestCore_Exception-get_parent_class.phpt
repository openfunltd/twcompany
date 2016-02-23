--TEST--
RequestCore_Exception - Get parent class

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	var_dump(get_parent_class('RequestCore_Exception'));
?>

--EXPECT--
string(9) "Exception"
