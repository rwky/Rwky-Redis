#!/bin/bash

cat <<EOT | redis-server -
maxmemory 500M
daemonize no
unixsocket /tmp/redis-test.sock
EOT
