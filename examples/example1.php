<?php
require("rwky-redis.php");
$redis = new \RWKY\Redis\Redis();
$response=$redis->pipe()->set("Question","What is your favourite colour?")->set("Answer","Blue...no yellow..ARGHHHH")->get("Question")->get("Answer")->drain();
print_r($response);
?>
