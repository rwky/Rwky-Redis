--TEST--
Test getting and setting of a large amount of data
--FILE--
<?php
require("tests-include.php");
$data=file_get_contents("/dev/urandom",null,null,0,1048576);
$redis->set("testkey",$data);
var_dump(strlen($redis->get("testkey")));
require("cleanup.php");
?>
--EXPECT--
int(1048576)
