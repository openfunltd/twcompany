--TEST--
HTTP_DELETE

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	echo RequestCore::HTTP_DELETE;
?>

--EXPECT--
DELETE
