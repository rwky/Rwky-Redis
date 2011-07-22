--TEST--
Test getting of redis data
--FILE--
<?php
require("tests-include.php");
$redis->set("testkey","apple");
var_dump($redis->get("testkey"));
require("cleanup.php");
--EXPECT--
string(5) "apple"
