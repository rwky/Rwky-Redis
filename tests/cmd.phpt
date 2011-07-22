--TEST--
Test deleting of redis data via cmd
--FILE--
<?php
require("tests-include.php");
$redis->set("testkey","apple");
var_dump($redis->cmd("del",array("testkey")));
require("cleanup.php");
?>
--EXPECT--
string(1) "1"
