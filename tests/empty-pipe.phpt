--TEST--
Tests if an exception is thrown when trying to drain an empty pipe
--FILE--
<?php
require("tests-include.php");
$redis->set("testkey","apple");
var_dump($redis->pipe()->set("testkey","orange")->emptyPipe()->drain());
require("cleanup.php");
?>
--EXPECTF--
Fatal error: Uncaught exception 'RWKY\Redis\RedisException' with message 'Cannot drain when no pipe exists' in /host/rwky-redis/redis.php:%d
Stack trace:
#0 /host/rwky-redis/redis.php(%d): RWKY\Redis\Redis->err('Cannot drain wh...', 1, %d)
#1 /host/rwky-redis/tests/empty-pipe.php(%d): RWKY\Redis\Redis->drain()
#2 {main}
  thrown in /host/rwky-redis/redis.php on line %d
