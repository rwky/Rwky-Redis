<?php
/**
 *Tests basic commands
 */
require_once("tests-include.inc");
require("rwky-redis-sessions.php");
class RedisSessionsTest extends PHPUnit_Framework_TestCase
{
   public function testSessions()
   {
      $redis=\RWKY\Redis\setUpRedis();
      \RWKY\Redis\RedisSessions::$redis=$redis;
      session_start();
      $id=session_id();
      $this->assertEmpty($_SESSION);
      $_SESSION["test"]=1;
      $this->assertEquals(array("test"=>1),$_SESSION);
      session_write_close();
      session_id($id);
      session_start();
      $this->assertEquals(array("test"=>1),$_SESSION);
      session_destroy();
      $this->assertEmpty(\RWKY\Redis\RedisSessions::read($id));
      $redis->flushdb();
   }
   
   public function testSessionSavePath()
   {
      $redis=\RWKY\Redis\setUpRedis();
      \RWKY\Redis\RedisSessions::$redis=$redis;
      session_save_path("redis-sessions");
      session_start();
      $id=session_id();
      $_SESSION["test"]=1;
      session_write_close();
      $this->assertEquals("test|i:1;",$redis->get("redis-sessions:$id"));
      $redis->flushdb();
   }
   
    /**
    *@expectedException Exception
    *@expectedExceptionMessage $redis is null, you must set RedisSessions::$redis to a redis instance before using any session methods
    */
   public function testOpen()
   {//cc 1 + 1*if
      $redis=\RWKY\Redis\setUpRedis();
      \RWKY\Redis\RedisSessions::$redis=$redis;
      $this->assertTrue(\RWKY\Redis\RedisSessions::open("test","test"));
      \RWKY\Redis\RedisSessions::$redis=null;
      \RWKY\Redis\RedisSessions::open("test","test");
      $redis->flushdb();
   }
   
   public function testReadWriteDestroy()
   {//read: cc 1 + 1*if write: cc 1 + 1*if destroy: cc 1 + 1*if
      $redis=\RWKY\Redis\setUpRedis();
      \RWKY\Redis\RedisSessions::$redis=$redis;
      session_start();
      $id=session_id();
      $_SESSION["test"]=1;
      session_write_close();
      $this->assertEquals("test|i:1;",\RWKY\Redis\RedisSessions::read($id));
      $this->assertEquals("test|i:1;",$redis->get("redis-sessions:$id"));
      \RWKY\Redis\RedisSessions::destroy($id);
      $this->assertEmpty($redis->keys("*"));
      $redis->flushdb();
      \RWKY\Redis\RedisSessions::$hash="hash";
      session_start();
      $id=session_id();
      $_SESSION["test"]=1;
      session_write_close();
      $this->assertEquals("test|i:1;",\RWKY\Redis\RedisSessions::read($id));
      $this->assertEquals("test|i:1;",$redis->hget("hash","redis-sessions:$id"));
      $this->assertEquals(0,$redis->zrank("hash-expire","redis-sessions:$id"));
      \RWKY\Redis\RedisSessions::destroy($id);
      $this->assertEmpty($redis->keys("*"));
      $redis->flushdb();
   }
   
   public function testGc()
   {//cc 1 + 1*if + 1*for
      ini_set("session.gc_maxlifetime",3);
      $redis=\RWKY\Redis\setUpRedis();
      \RWKY\Redis\RedisSessions::$redis=$redis;
      \RWKY\Redis\RedisSessions::$hash="hash";
      $this->assertEmpty($redis->keys("*"));
      session_start();
      $_SESSION["test"]=1;
      session_write_close();
      $this->assertNotEmpty($redis->keys("*"));
      sleep(5);
      $this->assertTrue(\RWKY\Redis\RedisSessions::gc());
      $this->assertEmpty($redis->keys("*"));
      \RWKY\Redis\RedisSessions::$hash=null;
      session_start();
      $_SESSION["test"]=1;
      session_write_close();
      $this->assertNotEmpty($redis->keys("*"));
      sleep(5);
      $this->assertTrue(\RWKY\Redis\RedisSessions::gc());
      $this->assertEmpty($redis->keys("*"));
      
   }
   
}

