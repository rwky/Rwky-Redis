--TEST--
Tests for exception being raised if redis sessions used without redis instance
--FILE--
<?php
require("tests-include.php");
require("rwky-redis-sessions.php");
session_start();
?>
--EXPECT--
Fatal error: Uncaught exception 'Exception' with message 'self::$redis is null, you must set RedisSessions::$redis to a redis instance before using any session methods' in /host/rwky-redis/rwky-redis-sessions.php:41
Stack trace:
#0 [internal function]: RWKY\Redis\RedisSessions::open('/var/lib/php5', 'PHPSESSID')
#1 /host/rwky-redis/tests/redis-sessions-missing-instance-exception.php(4): session_start()
#2 {main}
  thrown in /host/rwky-redis/rwky-redis-sessions.php on line 41

Fatal error: Call to a member function setex() on a non-object in /host/rwky-redis/rwky-redis-sessions.php on line 73
