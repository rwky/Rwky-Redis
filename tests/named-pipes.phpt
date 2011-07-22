--TEST--
Testing named pipes
--FILE--
<?php
require("tests-include.php");
var_dump($redis->pipe("p1")->set("testkey","apple")->pipe("p2")->set("testkey","pie")->get("testkey")->drain("p1"),$redis->get("testkey")->drain("p2"));
require("cleanup.php");
?>
--EXPECT--
array(1) {
  [0]=>
  bool(true)
}
array(3) {
  [0]=>
  bool(true)
  [1]=>
  string(3) "pie"
  [2]=>
  string(3) "pie"
}
