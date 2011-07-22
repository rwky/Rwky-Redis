--TEST--
Testing invalid commands
--FILE--
<?php
require("tests-include.php");
var_dump($redis->get());
var_dump($redis->invalidCommand());
require("cleanup.php");
--EXPECT--
bool(false)
bool(false)
