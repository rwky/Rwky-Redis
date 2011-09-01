<?php
namespace RWKY\Redis;

/**
 *@file
 *This file contains the requirements for storing sessions in redis
 */

/**
 *@brief Class for storing PHP sessions in redis
 *
 *Stores session data in redis, uses <a href="http://www.php.net/manual/en/function.session-save-path.php" target="_blank">session_save_path()</a> to set the prefix of the redis key.<br />
 Simply include the file and set \RWKY\Redis\RedisSessions::$redis to an instance of \RWKY\Redis\Redis to have it enable session handling. You must include it before calling any <a href="http://www.php.net/manual/en/ref.session.php" target="_blank">session_*</a> functions.<br />
 Example:<br />
 <code><br />
 <?php<br />
 require("rwky-redis.php");<br />
 require("rwky-redis-sessions.php");<br />
 $redis=new \\RWKY\\Redis\\Redis();<br />
 \\RWKY\\Redis\\RedisSessions::$redis=$redis;<br />
 ?><br />
 </code>
 */
class RedisSessions
{
  /**
   *The prefix of sessions
   */
  protected static $_prefix;
  
  /**
   *The session id
   */
  protected static $_session_id;
  
  /**
   *The Redis instance to communicate with
   */
  public static $redis;
  
  /**
   *@brief stores sessions in the hash
   *
   *store the sessions in the hash specified by the variable value, if null then sessions are stored in keys<br />
   *Hashes allow purging of all sessions using the <a href="http://redis.io/commands/del" target="_blank">DEL</a> command.<br />
   *However they don't allow auto expiration so the garbage collector has to be used. The key self::$hash-expire will be used to manage expiration of hashes for the garbage collector<br />
   *If you are using the redis database only as a session store leave this null since it will skil the need for a garbage collector and you can purge all sessions by using <a href="http://redis.io/commands/flushdb" target="_blank">FLUSHDB</a>
   */
  public static $hash=null;
  
  /**
   *Opens the session
   *@param string $prefix the session prefox
   *@param string $name the session name
   *@return bool true
   *@exception Exception throws an exception if self::$redis is null
   */
  public static function open($prefix,$name)
  {
    if(is_null(self::$redis)) throw new \Exception('self::$redis is null, you must set RedisSessions::$redis to a redis instance before using any session methods');
    self::$_prefix=$prefix;
    return true;
  }
  
  /**
   *Closes the session
   *@return bool true
   */
  public static function close()
  {
    return true;
  }
  
  /**
   *Get the session data
   *@param string $id the session id
   *@return string the string representation of the session data
   */
  public static function read($id)
  {
    return (string) is_null(self::$hash) ? self::$redis->get(self::$_prefix.":{$id}") : self::$redis->hget(self::$hash,self::$_prefix.":{$id}");
  }
  
  /**
   *Writes session data
   *@param string $id the session id
   *@param string $data the session data to write
   *@return bool true on success false on failure
   */
  public static function write($id,$data)
  {
    return is_null(self::$hash) ? self::$redis->setex(self::$_prefix.":{$id}",ini_get("session.gc_maxlifetime"),$data) : self::$redis->pipe()->multi()->zadd(self::$hash."-expire",time()+ini_get("session.gc_maxlifetime"),self::$_prefix.":{$id}")->hset(self::$hash,self::$_prefix.":{$id}",$data)->exec()->drain();
  }
  
  /**
   *Destroys a session
   *@param string $id the session id
   */
  public static function destroy($id)
  {
    return (string) is_null(self::$hash) ? self::$redis->del(self::$_prefix.":{$id}") : self::$redis->pipe()->multi()->hdel(self::$hash,self::$_prefix.":{$id}")->zrem(self::$hash."-expire",self::$_prefix.":{$id}")->exec()->drain();
  }
  
  /**
   *Delete expired sessions, if the value of self::$hash is null then this just returns true, else it prunes up to 100 expired sessions at once.
   *@return bool true
   */
  public static function gc()
  {
    if(is_null(self::$hash)) return true;
    
    $expired=self::$redis->zrangebyscore(self::$hash."-expire",0,time(),'LIMIT 0 100');
    self::$redis->pipe();
    foreach($expired as $e)
    {
      self::$redis->multi()->zrem(self::$hash."-expire",$e)->hdel(self::$hash,$e)->exec();
    }
    self::$redis->drain();
    return true;
  }
  
  /**
   *PHP shutdown function, needs to be here due to a bug in PHP 5.3.2 and below that prevented sessions closing properly when using external classes due to the class being unloaded before the session has closed, registering this as a shutdown function keeps the class loaded
   */
  public static function shutdown()
  {
    session_write_close();
  }
}

/**
 *Set the session save handler
 */
\session_set_save_handler('\RWKY\Redis\RedisSessions::open','\RWKY\Redis\RedisSessions::close','\RWKY\Redis\RedisSessions::read','\RWKY\Redis\RedisSessions::write','\RWKY\Redis\RedisSessions::destroy','\RWKY\Redis\RedisSessions::gc');

/**
 *Register the shutdown function for PHP <= 5.3.2
 */
if(version_compare(PHP_VERSION, '5.3.3') === -1)
{
  register_shutdown_function('\RWKY\Redis\RedisSessions::shutdown');
}
