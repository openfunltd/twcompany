--TEST--
Use multiple nodes with different names as children of <root>.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = array(
	'configuration' => array(
		'workflowExecutionRetentionPeriodInDays' => 1
	),
	'domainInfo' => array(
		'name' => 'aws-php-sdk-domain',
		'status' => 'REGISTERED'
	)
);

echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <configuration>
    <workflowExecutionRetentionPeriodInDays>1</workflowExecutionRetentionPeriodInDays>
  </configuration>
  <domainInfo>
    <name><![CDATA[aws-php-sdk-domain]]></name>
    <status><![CDATA[REGISTERED]]></status>
  </domainInfo>
</root>
