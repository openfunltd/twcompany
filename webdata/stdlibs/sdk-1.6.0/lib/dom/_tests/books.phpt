--TEST--
Create books XML from array.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = array(
	'book' => array(
		array(
			'author' => 'Author0',
			'title' => 'Title0',
			'publisher' => 'Publisher0',
			'__attributes__' => array(
				'isbn' => '978-3-16-148410-0'
			)
		),
		array(
			'author' => array('Author1', 'Author2'),
			'title' => 'Title1',
			'publisher' => 'Publisher1'
		),
		array(
			'__attributes__' => array(
				'isbn' => '978-3-16-148410-0'
			),
			'__content__' => 'Title2'
		)
	)
);

echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <book isbn="978-3-16-148410-0">
    <author><![CDATA[Author0]]></author>
    <title><![CDATA[Title0]]></title>
    <publisher><![CDATA[Publisher0]]></publisher>
  </book>
  <book>
    <author><![CDATA[Author1]]></author>
    <author><![CDATA[Author2]]></author>
    <title><![CDATA[Title1]]></title>
    <publisher><![CDATA[Publisher1]]></publisher>
  </book>
  <book isbn="978-3-16-148410-0"><![CDATA[Title2]]></book>
</root>
