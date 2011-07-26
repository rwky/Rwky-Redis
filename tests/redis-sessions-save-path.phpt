--TEST--
Tests for testing the session save path using redis sessions
--FILE--
<?php
require("tests-include.php");
require("rwky-redis-sessions.php");
\RWKY\Redis\RedisSessions::$redis=$redis;
session_save_path("redis-sessions");
ob_start();
session_start();
$id=session_id();
$_SESSION["test"]=1;
session_write_close();
var_dump($redis->get("redis-sessions:$id"));
ob_end_flush();
?>
--EXPECT--
string(9) "test|i:1;"
