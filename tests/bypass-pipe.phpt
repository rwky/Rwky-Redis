--TEST--
Tests bypassing pipes
--FILE--
<?php
require("tests-include.php");
$redis->pipe()->get("testkey")->set("testkey","apple");
var_dump($redis->bypassPipe("set","testkey","pie"));
var_dump($redis->get("testkey")->drain());
require("cleanup.php");
?>
--EXPECT--
bool(true)
array(3) {
  [0]=>
  string(3) "pie"
  [1]=>
  bool(true)
  [2]=>
  string(5) "apple"
}
