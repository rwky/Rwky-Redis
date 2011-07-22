--TEST--
Test connecting via unix socket
--FILE--
<?php
$config=array("UNIX_SOCKET","/tmp/redis.sock");
require("tests-include.php");
$redis->set("testkey","apple");
var_dump($redis->cmd("del",array("testkey")));
require("cleanup.php");
--EXPECT--
string(1) "1"
