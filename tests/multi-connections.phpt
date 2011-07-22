--TEST--
Test multiple connections (should be able to send two multi commands)
--FILE--
<?php
require("tests-include.php");
$redis2=new \RWKY\Redis\Redis();
$redis2->config("PORT",6379);
$redis2->config("DEBUG_LOG","./debug.log");
var_dump($redis->multi());
var_dump($redis2->multi());
var_dump($redis->multi());
require("cleanup.php");
?>
--EXPECT--
bool(true)
bool(true)
bool(false)
