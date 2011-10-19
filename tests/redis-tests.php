<?php
/**
 *Tests basic commands
 */
require_once("tests-include.inc");
class RedisTest extends PHPUnit_Framework_TestCase
{
   public function testSetGet()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $this->assertTrue($redis->set("testkey","apple"));
      $this->assertEquals("apple",$redis->get("testkey"));
      $this->assertEquals(1,$redis->cmd("del",array("testkey")));
      $redis->flushdb();
   }
   
   
   public function testInvalidCommands()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $this->assertFalse($redis->get());
      $this->assertFalse($redis->invalidCommand());
   }
   
   public function testNilMultiBulk()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->watch("test");
      $redis2=\RWKY\Redis\setUpRedis();
      $redis2->set("test",1);
      $this->assertTrue($redis->multi());
      $this->assertTrue($redis->set("test",2));
      $this->assertNull($redis->exec());
      $this->assertEquals(1,$redis->get("test"));
      $redis->flushdb();
   }
   
   public function testRawCommands()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->raw(true);
      $this->assertEquals("+OK",$redis->set("testkey","apple"));
      $this->assertEquals("-ERR wrong number of arguments for 'get' command",$redis->get("testkey","apple"));
      $this->assertEquals("apple",$redis->get("testkey"));
      $this->assertEquals(":1",$redis->del("testkey"));
      $redis->flushdb();
   }
   
   public function testRawPipeCommands()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->raw(true);
      $redis->pipe();
      $redis->set("testkey","apple");
      $redis->get("testkey","apple");
      $redis->get("testkey");
      $redis->del("testkey");
      $this->assertEquals(array("+OK","-ERR wrong number of arguments for 'get' command","apple",":1"),$redis->drain());
      $redis->flushdb();
   }
   
   public function testLargeSetGet()
   {
      $data=file_get_contents("/dev/urandom",null,null,0,1048576);
      $redis=\RWKY\Redis\setUpRedis();
      $this->assertTrue($redis->set("testkey",$data));
      $this->assertEquals(1048576,strlen($redis->get("testkey")));
      $redis->flushdb();
   }
   
   public function testUnixStocket()
   {
      $config=array("UNIX_SOCKET","/tmp/redis.sock");
      $redis=\RWKY\Redis\setUpRedis($config);
      $this->assertTrue($redis->set("testkey","apple"));
      $this->assertEquals("apple",$redis->get("testkey"));
      $this->assertEquals(1,$redis->del("testkey"));
   }
   
   public function testMultipleInstances()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis2=\RWKY\Redis\setUpRedis();
      $this->assertTrue($redis->multi());
      $this->assertTrue($redis2->multi());
      $this->assertFalse($redis->multi());
   }
  
   public function testPipelining()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $this->assertEquals(array(true,"apple",1),$redis->pipe()->set("testkey","apple")->get("testkey")->cmd("del",array("testkey"),":")->drain());
   }
   
   public function testBypassPipe()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->pipe()->get("testkey")->set("testkey","apple");
      $this->assertEquals(true,$redis->bypassPipe("set","testkey","pie"));
      $this->assertEquals(array("pie",true,"apple"),$redis->get("testkey")->drain());
   }
   
   /**
    *@expectedException \RWKY\Redis\RedisException
    *@expectedExceptionMessage Cannot drain when no pipe exists
    */
   public function testEmptyPipe()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->pipe()->set("testkey","orange")->emptyPipe()->drain();
   }
   
   public function testMultiplePipes()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->pipe(); //pipe 1
      $redis->set("testkey","apple");
      $redis->pipe(); //pipe 2
      $redis->set("testkey","pie");
      $redis->get("testkey");
      $this->assertEquals(array(true,"pie"),$redis->drain()); //drain pipe 2
      $redis->get("testkey");
      $this->assertEquals(array(true,"apple"),$redis->drain()); //drain pipe 1
   }
   
   public function testNamedPipes()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->pipe("p1");//pipe p1
      $redis->set("testkey","apple");
      $redis->pipe("p2");//pipe p2
      $redis->set("testkey","pie");
      $redis->get("testkey");
      $this->assertEquals(array(true),$redis->drain("p1")); //drain pipe p1
      $redis->get("testkey");
      $this->assertEquals(array(true,"pie","pie"),$redis->drain("p2")); //drain pipe p2
   }
   
   public function testReopeningNamedPipes()
   {
      $redis=\RWKY\Redis\setUpRedis();
      $redis->pipe("p1"); //pipe p1
      $redis->set("testkey","apple");
      $redis->pipe("p2");
      $redis->set("testkey","pie");
      $redis->pipe("p1");
      $redis->get("testkey");
      $redis->pipe("p2");
      $redis->get("testkey");
      $this->assertEquals(array(true,"apple"),$redis->drain("p1"));
      $this->assertEquals(array(true,"pie"),$redis->drain("p2"));
   }
   
   public function testOOM()
   {
      /**
       *requires that redis have it's memory limit capped at 500m
       */
      ini_set("memory_limit","512M");
      $redis=\RWKY\Redis\setUpRedis();
      $f=fopen("/dev/zero","rb");
      $data=fread($f,7864320);
      fclose($f);
      $return=array();
      $expected=array();
      for($i=0;$i<100;$i++)
      {
        $return[]=$redis->set("OOM$i",$data);
        $expected[]=$i<61 ? true : false;
      }

      $this->assertEquals($expected,$return);
      $redis->flushdb();
   }
   
   public function testConfig()
   {//cc 1 + 2*if
      $redis=\RWKY\Redis\setUpRedis();
      $this->assertEquals(6379,$redis->config("PORT",1111));
      $this->assertEquals(1111,$redis->config("PORT"));
      $this->assertNull($redis->config("test"));
   }
   
   /**
    *@expectedException \RWKY\Redis\RedisException
    *@expectedExceptionMessage Test error 2
    */
   public function testErr()
   {//cc 1 + 2*if
      $redis=\RWKY\Redis\setUpRedis();
      $redis->err("Test error",2,1);
      $this->assertContains("Test error",$redis->errors);
      $redis->err("Test error 2",1,1);
   }
   /**
    *@expectedException \RWKY\Redis\RedisException
    */
   
   public function testProtcolConnect()
   {//cc 1 + 1*try + 3*if
      $redis=\RWKY\Redis\setUpRedis();
      $protocol=new \RWKY\Redis\Protocol($redis);
      $protocol->connect();
      $redis->config("PORT",1111);
      $protocol=new \RWKY\Redis\Protocol($redis);
      $protocol->connect();
   }
   
   /**
    *@expectedException \RWKY\Redis\RedisException
    *@expectedExceptionMessage Test exception 1
    */
   public function testException()
   {//cc 1 +  2*switch
      $redis=\RWKY\Redis\setUpRedis();
      throw new \RWKY\Redis\RedisException($redis,"Test exception 1",0,"test",1);
   }
   
 
   public function testException2()
   {//cc 1 +  2*switch
      $redis=\RWKY\Redis\setUpRedis();
      $redis->config("ON_EXCEPTION","file");
      $redis->config("ON_EXCEPTION_FILE","/tmp/redis-exception-test");
      file_put_contents("/tmp/redis-exception-test","Hello world");
      ob_start();
      try
      {
         throw new \RWKY\Redis\RedisException($redis,"Test exception 2",0,"test",1);
      }
      catch(Exception $e)
      {
         $this->assertEquals("Test exception 2",$e->getMessage());
      }
      $this->assertEquals("Hello world",ob_get_clean());
      shell_exec("rm /tmp/redis-exception-test");
   }

}

