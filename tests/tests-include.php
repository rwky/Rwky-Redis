<?php
namespace RWKY\Redis;
date_default_timezone_set("UTC");
require("redis.php");

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("\RWKY\Redis\exception_error_handler");
file_put_contents("/tmp/redis.conf","maxmemory 500M\npidfile /tmp/redis-test.pid\ndaemonize yes\nunixsocket /tmp/redis-test.sock");
system('/usr/bin/redis-server /tmp/redis.conf');
sleep(1);
$redis=new Redis();
$redis->config("PORT",6379);
$redis->config("DEBUG_LOG","./debug.log");
if(isset($config))
{
  foreach($config as $k=>$v)
  {
    $redis->config($k,$v);
  }
}
$redis->flushdb();

