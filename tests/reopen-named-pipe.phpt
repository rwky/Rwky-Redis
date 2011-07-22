--TEST--
testing reopening named pipes
--FILE--
<?php
require("tests-include.php");
var_dump($redis->pipe("p1")->set("testkey","apple")->pipe("p2")->set("testkey","pie")->pipe("p1")->get("testkey")->pipe("p2")->get("testkey")->drain("p1"),$redis->drain("p2"));
require("cleanup.php");
?>
--EXPECT--
array(2) {
  [0]=>
  bool(true)
  [1]=>
  string(5) "apple"
}
array(2) {
  [0]=>
  bool(true)
  [1]=>
  string(3) "pie"
}
