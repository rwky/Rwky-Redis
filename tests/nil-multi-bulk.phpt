--TEST--
Tests a null multi bulk reply created from a failed watch transaction
--FILE--
<?php
namespace SimpleRedis;
require("tests-include.php");
$redis->watch("test");
sleep(1);
var_dump(shell_exec("/usr/bin/redis-cli set test 1"));
sleep(1);
$redis->multi();
$redis->set("test",2);
var_dump($redis->exec());
var_dump($redis->get("test"));
require("cleanup.php");
?>
--EXPECT--
string(3) "OK
"
NULL
string(1) "1"
