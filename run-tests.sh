#!/bin/bash
export TEST_PHP_EXECUTABLE=/usr/bin/php
export TEST_PHP_CGI_EXECUTABLE=/usr/bin/php-cgi
RUNTESTS=/usr/local/lib/php/build/run-tests.php
if [ "$1" = "--help" ]
then
$TEST_PHP_EXECUTABLE  $RUNTESTS --help
exit 0
fi

if [ -z $1 ]
then
tests=`find tests -path "*disabled*" -prune -o -name "*.phpt" -print`
teststring=""
for t in $tests
do
teststring="$teststring $t"
done
$TEST_PHP_EXECUTABLE  $RUNTESTS $teststring
else
if [ -d tests/$1 ]
then
$TEST_PHP_EXECUTABLE  $RUNTESTS tests/$1
else
$TEST_PHP_EXECUTABLE  $RUNTESTS tests/$1.phpt && cat tests/$1.out
fi
fi
