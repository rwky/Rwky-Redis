--TEST--
Test redis raw functionality
--FILE--
<?php
require("tests-include.php");
$redis->raw(true);
var_dump($redis->set("testkey","apple"));
var_dump($redis->get("testkey","apple"));
var_dump($redis->get("testkey"));
var_dump($redis->del("testkey"));
require("cleanup.php");
--EXPECT--
string(3) "+OK"
string(48) "-ERR wrong number of arguments for 'get' command"
string(5) "apple"
string(2) ":1"
