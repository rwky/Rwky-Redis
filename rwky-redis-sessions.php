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
 Simply include the file to have it enable session handling. You must include it before calling any <a href="http://www.php.net/manual/en/ref.session.php" target="_blank">session_*</a> functions.
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
    return (string) self::$redis->get(self::$_prefix.":{$id}");
  }
  
  /**
   *Writes session data
   *@param string $id the session id
   *@param string $data the session data to write
   *@return bool true on success false on failure
   */
  public static function write($id,$data)
  {
    return self::$redis->setex(self::$_prefix.":{$id}",ini_get("session.gc_maxlifetime"),$data);
  }
  
  /**
   *Destroys a session
   *@param string $id the session id
   */
  public static function destroy($id)
  {
    self::$redis->del(self::$_prefix.":{$id}");
  }
  
  /**
   *Place holder function for garbage collection. Just returns true since we use redis' <a href="http://redis.io/commands/setex" target="_blank">SETEX</a> to delete session data after session.gc_maxlifetime ini value
   *@return bool true
   */
  public static function gc()
  {
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
