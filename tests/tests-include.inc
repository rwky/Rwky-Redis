<?php
namespace RWKY\Redis;
ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
date_default_timezone_set("UTC");
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__."/..");
require("rwky-redis.php");

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("\RWKY\Redis\exception_error_handler");

function setUpRedis($config=array())
{
$redis=new Redis();
$redis->config("PORT",6379);
//$redis->config("DEBUG_LOG",__DIR__."/../debug.log");
  foreach($config as $k=>$v)
  {
    $redis->config($k,$v);
  }
$redis->flushdb();
return $redis;
}

