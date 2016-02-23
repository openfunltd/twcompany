--TEST--
HTTP_HEAD

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	echo RequestCore::HTTP_HEAD;
?>

--EXPECT--
HEAD
