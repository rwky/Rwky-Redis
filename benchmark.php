<?php
/**
 *@file
 *This file benchmarks a few aspects of \\RWKY\\Redis<br />
 *It can benchmark magic methods vs cmd, pipelining vs no pipelining and tcp vs unix sockets<br />
 *<strong>Be careful when running this file it may overwrite your redis database</strong>
 */
require("tests/tests-include.inc");
$redis=\RWKY\Redis\setUpRedis();
$tests=array("magic","pipelining","connection");

if(!isset($argv[1])) $argv[1]="all";

if($argv[1]=="all")
{
  foreach($tests as $t)
  {
    echo "---$t---".PHP_EOL;
    $t();
    echo "--- ---".PHP_EOL;
  }
  exit;
}

array_shift($argv);
foreach($argv as $arg)
{
  if(!in_array($arg,$tests))
  {
    echo "Invalid test $arg".PHP_EOL;
  }
  else{
    echo "---$arg---".PHP_EOL;
    $arg();
    echo "--- ---".PHP_EOL;
  }
}

/**
 *Benchmarks using  __call vs cmd
 */
function magic()
{
  global $redis;
  echo "Benchmarking __call vs cmd".PHP_EOL;
  $redis->flushdb();
  $t=microtime(true);
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  echo "__call to set 10,000 times = ".(microtime(true)-$t)." seconds".PHP_EOL;
  sleep(1);
  $redis->flushdb();
  echo "cmd...".PHP_EOL;
  $t=microtime(true);
  for($i=0;$i<10000;$i++)
  {
    $redis->cmd("set",array("key$i",$i));
  }
  echo "cmd time to set 10,000 times = ".(microtime(true)-$t)." seconds".PHP_EOL;
  $redis->flushdb();

}

/**
 *Benchmarks pipelining vs no pipelining
 */
function pipelining()
{
  global $redis;
  
  echo "Benchmarking set pipeline vs non pipeline".PHP_EOL;
  $redis->flushdb();
  $nonPipe=microtime(true);
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  echo "Non pipelining time to set 10,000 times = ".(microtime(true)-$nonPipe)." seconds".PHP_EOL;
  sleep(1);
  $redis->flushdb();
  echo "Pipelining...".PHP_EOL;
  $pipe=microtime(true);
  $redis->pipe();
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  $redis->drain();
  echo "Pipelining time to set 10,000 times = ".(microtime(true)-$pipe)." seconds".PHP_EOL;
  $redis->flushdb();

}

/**
 *Benchmarks tcp vs unix sockets
 */
function connection()
{
  global $redis;
  $redis=new \RWKY\Redis\Redis();
  $redis->config("PORT",6379);
  echo "Benchmarking tcp vs unix socket using pipelining".PHP_EOL;
  $redis->flushdb();
  $nonPipe=microtime(true);
  $redis->pipe();
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  $redis->drain();
  echo "TCP time to set 10,000 times = ".(microtime(true)-$nonPipe)." seconds".PHP_EOL;
  sleep(1);
  $redis=new \RWKY\Redis\Redis();
  $redis->config("UNIX_SOCKET","/tmp/redis-test.sock");
  $redis->flushdb();
  echo "Unix socket...".PHP_EOL;
  $pipe=microtime(true);
  $redis->pipe();
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  $redis->drain();
  echo "UNIX Socket time to set 10,000 times = ".(microtime(true)-$pipe)." seconds".PHP_EOL;
  $redis->flushdb();
  
  $redis=new \RWKY\Redis\Redis();
  $redis->config("PORT",6379);
  echo "Benchmarking tcp vs unix socket without using pipelining".PHP_EOL;
  $redis->flushdb();
  $nonPipe=microtime(true);
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  echo "TCP time to set 10,000 times = ".(microtime(true)-$nonPipe)." seconds".PHP_EOL;
  sleep(1);
  $redis=new \RWKY\Redis\Redis();
  $redis->config("UNIX_SOCKET","/tmp/redis-test.sock");
  $redis->flushdb();
  echo "Unix socket...".PHP_EOL;
  $pipe=microtime(true);
  for($i=0;$i<10000;$i++)
  {
    $redis->set("key$i",$i);
  }
  echo "UNIX Socket time to set 10,000 times = ".(microtime(true)-$pipe)." seconds".PHP_EOL;
  $redis->flushdb();
  $redis=new \RWKY\Redis\Redis();
  $redis->config("PORT",6379);
}
