<?php

//we're in scripts
$gamebase = dirname(dirname(__FILE__));

$ns = @$_SERVER['argv'][1];
if (!empty($ns)) {
	define('Z_NAMESPACE',$ns);
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
	class PHPUnit_Framework_TestCase {
		function assertTrue($a,$b) {
			if ($a) {
				print("OK\n");
			} else {
				print("FAIL!\n");
			}
		}
	}
}


require_once($gamebase.'/tests/XMLValidationTest.php');
$test = new XMLValidationTest;

$params = $test->testXMLFileProvider();
foreach ($params as $param) {
	echo 'Testing '.basename($param[1].'...');
	call_user_func_array(array($test,'testXMLFile'),$param);
}

print("Testing keys....");
$test->testXMLKeys();
exec('xmllint --noout --schema '.$gamebase.'/tests/schemas/proto_keys.xsd proto_keys.xml');
