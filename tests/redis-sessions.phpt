--TEST--
Tests for manging sessions with redis
--FILE--
<?php
require("tests-include.php");
require("rwky-redis-sessions.php");
\RWKY\Redis\RedisSessions::$redis=$redis;
ob_start();
session_start();
$id=session_id();
var_dump($_SESSION);
$_SESSION["test"]=1;
var_dump($_SESSION);
session_write_close();
session_id($id);
session_start();
var_dump($_SESSION);
session_destroy();
var_dump(\RWKY\Redis\RedisSessions::read($id));
ob_end_flush();
?>
--EXPECT--
array(0) {
}
array(1) {
  ["test"]=>
  int(1)
}
array(1) {
  ["test"]=>
  int(1)
}
string(0) ""
