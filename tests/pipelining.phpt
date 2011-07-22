--TEST--
Testing pipelining
--FILE--
<?php
require("tests-include.php");
var_dump($redis->pipe()->set("testkey","apple")->get("testkey")->cmd("del",array("testkey"),":")->drain());
require("cleanup.php");
--EXPECT--
array(3) {
  [0]=>
  bool(true)
  [1]=>
  string(5) "apple"
  [2]=>
  string(1) "1"
}
