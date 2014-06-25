###This is no longer maintained

Author Rowan Wookey admin@rwky.net &copy; copyright 2010-2011.
LICENSE: Released under the Simplified BSD License, see the LICENSE file or http://www.opensource.org/licenses/BSD-2-Clause

System Requirements
PHP 5.3.0 or greater

Examples of usage

Create a redis instant, add some data via a pipe and then return it
<pre>
require("rwky-redis.php");
$redis = new \RWKY\Redis\Redis();
$response=$redis->pipe()->set("Question","What is your favourite colour?")->set("Answer","Blue...no yellow..ARGHHHH")->get("Question")->get("Answer")->drain();
print_r($response);
</pre>
Will output:
<pre>Array
(
    [0] => 1
    [1] => 1
    [2] => What is your favourite colour?
    [3] => Blue...no yellow..ARGHHHH
)
</pre>
The code for this example can be found in the examples/example1.php file


[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=rwky&url=https://github.com/rwky/Rwky-Redis&title=Rwky-Redis&language=en_GB&tags=github&category=software) 
