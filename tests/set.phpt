--TEST--
Test setting of redis data
--FILE--
<?php
require("tests-include.php");
var_dump($redis->set("testkey","apple"));
require("cleanup.php");
--EXPECT--
bool(true)
