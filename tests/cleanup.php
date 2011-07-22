<?php
shell_exec('kill `cat /tmp/redis-test.pid`');
unlink("/tmp/redis.conf");
sleep(1);
