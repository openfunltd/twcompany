--TEST--
HTTP_PUT

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	echo RequestCore::HTTP_PUT;
?>

--EXPECT--
PUT
