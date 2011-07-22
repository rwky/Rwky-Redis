--TEST--
Tests multiple pipelining
--FILE--
<?php
require("tests-include.php");
var_dump($redis->pipe()->set("testkey","apple")->pipe()->set("testkey","pie")->get("testkey")->drain(),$redis->get("testkey")->drain());
require("cleanup.php");
?>
--EXPECT--
array(2) {
  [0]=>
  bool(true)
  [1]=>
  string(3) "pie"
}
array(2) {
  [0]=>
  bool(true)
  [1]=>
  string(5) "apple"
}
