--TEST--
Tests what happens if redis is killed
--FILE--
<?php
require("tests-include.php");
shell_exec('kill `cat /tmp/redis-test.pid`');
sleep(2);
try
{
var_dump($redis->set("crashtest",1));
var_dump($redis->set("crashtest2",3));
var_dump($redis->set("crashtest3",4));
var_dump($redis->get("crashtest"));
}
catch(\Exception $e)
{
  echo $e->getMessage();
  echo PHP_EOL;
}
--EXPECT--
Unable to read reply connection has probably failed
