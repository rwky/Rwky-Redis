<?php
namespace RWKY\Redis;
/**
 *@file
 *This file contains all the classes to use RWKY\\Redis
 */

/**
 *The version of the library
 */
define("VERSION",2011072600);

/**
 *@brief The main redis class, use this to access redis
 */
class Redis
{
  
  /**
   *Redis Errors, these should kill the script
   */
  const ERROR=1;
  /**
   *Redis warnings, these should be logged and may kill the script if you desire
   */
  const WARNING=2;
  
  /**
   *an array of errors that have occurred
   */
  public $errors=array();
  /**
   *The Protocol object
   */
  public $protocol;
  /**
   *A buffer of commands to run used for pipelining
   */
  protected $_buffer=array();
  /**
   *The current pipe in use, null if no pipe
   */
  protected $_currentPipe=null;
    /**
   *The stores configuration information
   */
  protected $_config=array(
			  "HOST"=>"127.0.0.1",
			  "PORT"=>6379,
			  //"UNIX_SOCKET"="/tmp/redis.sock",
			  //"DEBUG_LOG"=>"./debug.log",
			  "ON_REDIS_ERROR"=>Redis::WARNING,
			  "ERROR_LOG"=>"./error.log",
			  "ERROR_REPORTING"=>Redis::WARNING,
			  "ON_EXCEPTION"=>"",
			  "ON_EXCEPTION_FILE"=>"",
			  "STREAM_TIMEOUT"=>3600,
			  "CONNECTION_TIMEOUT"=>3,
			  );
  /**
   *If true return raw redis responses else format them
   */
  protected $_raw=false;
  /**
   *Stores if the debug log has been overwritten
   */
  private static $_debugOverwritten=false;
  /**
   *A unique id used for determining which redis object wrote a message to the debug log
   */
  protected $_debugID;  
   /**
   *Constructor method, sets the unique debugID and creates a Protocol object
   */
  public function __construct()
  {
    $this->_debugID=uniqid();
    $this->protocol=new Protocol($this);
  }
  
  /**
   *Gets/sets config values, if only one argument is passed then it gets the value, if two arguments are passed then it sets the value
   *@param string $key the key to set/get
   *@param mixed $value the value to set $key to
   *@return mixed the value of $key, if setting then the value of $key before setting, returns null if $key doesn't exist
   */
  public function config($key,$value=null)
  {
	 $r=isset($this->_config[$key]) ? $this->_config[$key] : null;
	 if(func_num_args()==2) $this->_config[$key]=$value;
	 return $r;
  }
  
  
  /**
   *Acts on an error, logs to error log if Config ERROR_REPORTING >= $severity
   *@param string $message the error message
   *@param int $severity either self::ERROR or self::WARNING
   *@param int $line __LINE__ passed from method call
   *@param Exception $previous_exception any previous exceptions or null
   *@throws RedisException if $severity == self::ERROR then exception is thrown
   */
  public function err($message,$severity,$line,$previous_exception=null)
  {
    if($this->config("ERROR_REPORTING")>=$severity) $this->logErr($message);
    $this->errors[]=$message;
    if($severity==self::ERROR)
    {
      throw new RedisException($this,$message,$severity,__FILE__,$line,$previous_exception);
    }
  }  
  
  /**
   *@brief Logs debug message if debugging enabled
   *
   *If Redis::config("DEBUG_OVERWRITE") is true then the debug log is overwritten
   *@param string $message the message to log
   */
  public function logDebug($message)
  {
    if(!$this->config("DEBUG_LOG")) return;
    try
    {
      if($this->config("DEBUG_OVERWRITE") and !self::$_debugOverwritten)
      {
		  $flags=0;
		  self::$_debugOverwritten=true;
      }
      else
      {
		  $flags=FILE_APPEND;
      }
      file_put_contents($this->config("DEBUG_LOG"),"---".date("c").PHP_EOL.$this->_debugID.PHP_EOL.$message.PHP_EOL."---".PHP_EOL,$flags);
    }
    catch(\Exception $e)
    {
      $this->err("Unable to write to debug file",self::WARNING,__LINE__,$e);
    }
  }
  
  /**
   *Logs an error message if error logging is enabled
   *@param string $message message to log
   */
  public function logErr($message)
  {
    $logFile=$this->config("ERROR_LOG");
    if(!$logFile) return;
    try
    {
      file_put_contents($logFile,"---".date("c").PHP_EOL.$message.PHP_EOL."---".PHP_EOL,FILE_APPEND);
    }
    catch(\Exception $e)
    {
      $this->err("Unable to write to error file",self::WARNING,__LINE__,$e);
    }
  }
  
 
  /**
   *magic method to call cmd, simple version of calling redis functions i.e
   *$redis->get("key") === $redis->cmd("get",array("key"))
   *Slightly slower than directly calling cmd but not much, check the benchmarks to see
   *@param string $name the name of the function to call
   *@param array $args the arguments to pass
   */
  public function __call($name,$args)
  {
    return $this->cmd($name,$args);
  }
  
  /**
   *Run a redis command
   *@param string $name the name of the function to call
   *@param array $args the arguments to pass
   *@return mixed $this if pipelining, else returns the response from redis
   */
  public function cmd($name,$args=array())
  {
    array_unshift($args,$name);
    $request=$this->protocol->request($args,!is_null($this->_currentPipe));
    if(!is_null($this->_currentPipe))
    {
      $this->_buffer[$this->_currentPipe][]=$request;
      return $this;
    }
    return $this->protocol->response($this->_raw);
  }
  
  /**
   *enables/disables raw responses
   *@param bool $enable set $this->_raw to $enable
   *@return Redis $this
   */
  public function raw($enable=false)
  {
    $this->_raw=$enable;
    return $this;
  }
  
  /**
   *@brief Starts pipelining
   *
   *Start pipelining a very good idea for multiple commands, tends to be 30% faster than sending commands separately
   *You can have multiple pipes, every call to pipe opens a new pipe or returns the named pipe, if $name is not null then the pipe is named for easy reference
   *@param string|int|null $name null opens the next incremental pipe, string|int opens named pipe
   *@return Redis $this
   */
  public function pipe($name=null)
  {
    if(is_null($name)) $name=count($this->_buffer);
    if(!isset($this->_buffer[$name])) $this->_buffer[$name]=array();
    $this->_currentPipe=$name;
    return $this;
  }
  
  
  /**
   *@brief End pipelining, send commands and get response
   *
   *Drains a pipe, if no pipe exists then it throw an error, if $name is null then it opens the newest pipe else it opens the named pipe.
   *@param null|string|int $name null drains newest pipe, string|int drains named pipe
   *@return array an array of responses
   */
  public function drain($name=null)
  {
    if(is_null($this->_currentPipe)) $this->err("Cannot drain when no pipe exists",self::ERROR,__LINE__);
    if(!is_null($name))
    {
      $buffer=$this->_buffer[$name];
      unset($this->_buffer[$name]);
    }
    else
    {
      $buffer=array_pop($this->_buffer);
    }
    $this->protocol->pipeRequest(implode("",$buffer));
    $response=$this->protocol->pipeResponse(count($buffer),$this->_raw);
    if(empty($this->_buffer)) $this->_currentPipe=null;
    else
    {
      end($this->_buffer);
      $this->_currentPipe=key($this->_buffer);
    }
    return $response;
  }
  
  /**
   *@brief Discards a pipe
   *
   *Discards all contents of a pipe if $name is null it discards the newest pipe otherwise it discards the named pipe
   *@param null|int|string $name null empties last pipe, string|int empties named pipe
   *@return Redis $this
   */
  public function emptyPipe($name=null)
  {
    if(is_null($name))
    {
      end($this->_buffer);
      $name=key($this->_buffer);
    }
    
    unset($this->_buffer[$name]);
    
    if(empty($this->_buffer)) $this->_currentPipe=null;
    else
    {
      end($this->_buffer);
      $this->_currentPipe=key($this->_buffer);
    }
    return $this;
  }
  
  /**
   *@brief Bypasses all pipes
   *
   *Bypasses all pipes running a command directly, pass the same arguments as you would to Redis::__call()
   *@return mixed returns the output of the command
   */  
  public function bypassPipe()
  {
    $p=$this->_currentPipe;
    $this->_currentPipe=null;
    $args=func_get_args();
    $f=array_shift($args);
    $r=$this->cmd($f,$args);
    $this->_currentPipe=$p;
    return $r;
  }


}

/**
 *@brief Class for managing communication with Redis.
 *
 *
 *This class manages all protocol related stuff, i.e communicating with redis, you should never need to directly access this class
 *See http://redis.io/topics/protocol for specification details
 */
class Protocol
{
  
  /**
   *OK Results
   */
  const STATUS='+';
  /**
   *errors
   */
  const ERROR='-';
  /**
   *integers
   */
  const INTEGER=":";
  /**
   *Bulk replies
   */
  const BULK='$';
  /**
   *Multiple bulk replies
   */
  const MULTI_BULK='*';
  
  /**
   *stores which replies are single line
   */
  private static $_singleLineReply=array(self::STATUS,self::ERROR,self::INTEGER);

  /**
   *The command to run
   */
  private $_command;
  /**
   *The connection
   */
  private $_connection;
  /**
   *The end of line characters
   */
  private $_eol="\r\n";
  /**
   *The redis object calling protocol
   */
  private $_caller;
  
  /**
   *Constructor sets $_caller to $caller
   *@param Redis $caller the calling Redis object
   */
  public function __construct($caller)
  {
    $this->_caller=$caller;
  }

  
  /**
   *@brief connects to redis if not already connected
   *
   *Connects to redis if not already connected, sets the stream timeout to the value of STREAM_TIMEOUT in Redis::$_config which defaults to one hour, if a client is running a blocking operation i.e. brpop for more than an hour it will disconnect.
   *It will attempt to pull configuration information from Redis::$_config if a UNIX_SOCKET is set then that will be preferred over a TCP connection
   *@return $this
   *@exception RedisException if connection fails
   */
  public function connect()
  {
    if(is_null($this->_connection))
    {
      try
      {
		  $timeout=$this->_caller->config("CONNECTION_TIMEOUT");
		  $unixSocket=$this->_caller->config("UNIX_SOCKET");
		  if(!is_null($unixSocket))
		  {
			 $this->_connection=fsockopen("unix://".$unixSocket,0,$errno,$errstr,$timeout);
		  }
		  else
		  {
			 $host=$this->_caller->config("HOST");
			 $port=$this->_caller->config("PORT");
			 $this->_connection=fsockopen($host,$port,$errno,$errstr,$timeout);
		  }
		  stream_set_timeout($this->_connection,$this->_caller->config("STREAM_TIMEOUT"));
      }
      catch(\Exception $e)
      {
		  $this->_caller->err("Unable to connect to redis, $errno - $errstr",self::ERROR,__LINE__,$e);
      }
    }
    return $this;
  }

  /**
   *@brief Sends a request to redis
   *
   *If pipelining then the request is buffered otherwise it is sent directly to redis
   *@param array $args arguments to pass
   *@param bool $buffer if true then buffer
   *@return mixed if $buffer then return the command else do not return
   */
  public function request($args,$buffer=false)
  {
    $numArgs=count($args);
    $this->_command="*$numArgs".$this->_eol;
    foreach($args as $arg)
    {
      $this->_command.='$'.strlen($arg).$this->_eol;
      $this->_command.=$arg.$this->_eol;
    }
    if($buffer) return $this->_command;
    else $this->_write();
  }
  
  
    /**
		 *@fn RWKY::Redis::Protocol::format($r)
		 *@memberof Protocol
		 *@private
		 *Anonymous function inside RWKY::Redis::Protocol::response
		 *Formats STATUS, ERROR and INTEGER replies into PHP types TRUE, FALSE and a string representation of the integer reply
		 *@param string $r the response tof ormat
		 */
  
  
  /**
   *@brief Gets the response from redis
   *
   *Reads a response from redis, if $raw is false then format() is called on the response if it's a single line response.<br />
   *If the response is a bulk or multi bulk then Protocol::_readBulk() and Protocol::_readMultiBulk() are called respectively
   *@param bool $raw if true then return raw response else use $format function
   *@return string|array the response from redis
   *@exception if redis connection has failed throws RedisException, else if Redis reports a -ERR reply and the ON_REDIS_ERROR configuration setting is set to Redis::ERROR then it will also throw a RedisException
   */
  public function response($raw=false)
  {
    $format=function($r)
    {
      if($r[0]==Protocol::STATUS) return true;
      else if($r[0]==Protocol::ERROR) return false;
      else if($r[0]==Protocol::INTEGER) return substr($r,1);
    };
    
    $type=$this->_read(1);
    if(empty($type)) $this->_caller->err("Unable to read reply connection has probably failed",Redis::ERROR,__LINE__);
    if(in_array($type,self::$_singleLineReply))
    {
      $resp=rtrim($type.$this->_readLine());
      if($type==self::ERROR)
      {
		  $this->_caller->err($resp,$this->_caller->config("ON_REDIS_ERROR"),__LINE__);
      }
      if(!$raw) $resp=$format($resp);
      return $resp;
    } 
    if($type==self::BULK) return $this->_readBulk();
    return $this->_readMultiBulk();    
  }
  
  /**
   *Runs a pipeline request
   *@param string $cmd the command to send
   */
  public function pipeRequest($cmd)
  {
    $this->_command=$cmd;
    $this->_write();
  }
  
  /**
   *returns a pipeline response
   *@param int $num the number of responses to collect
   *@return array an array of responses
   */
  public function pipeResponse($num)
  {
    $arr=array();
    for($i=0;$i<$num;++$i)
    {
      $arr[]=$this->response();
    }
    return $arr;
  }

  /**
   *@brief Write to redis
   *
   *Writes to redis, if not connected it will attempt to connect, this allows lazy connections i.e. connections are only made when required.<br />
   *If debugging is enable the commands are written to the debug file<br />
   *If the write fails an exceptions is thrown
   *@exception RedisException when unable to write
   */
  private function _write()
  {
    $this->connect();
    $this->_caller->logDebug("---WRITE---".PHP_EOL.$this->_command);
    $len=strlen($this->_command);
    $written=0;
    try
    {
		while($written<$len)
		{
		  $sent=fwrite($this->_connection,substr($this->_command,$written));
		  $written+=$sent;
		  if($sent===false)
		  {
			 $this->_caller->err("Failed to write to redis",Redis::ERROR,__LINE__);
		  }
		}
    }
    catch(\Exception $e)
    {
      $this->_caller->err("Failed to write to redis",Redis::ERROR,__LINE__,$e);
    }
  }
  
  /**
   *@brief Reads a single line from redis
   *
   *Used for single line responses. It will try to connect if connection isn't started, returns the single line. If debugging is enabled then logs to the debug file
   *@return string the response
   *@exception RedisException when unable to communicate with redis
   */
  private function _readLine()
  {
    $this->connect();
    try
    {
     $resp=fgets($this->_connection); 
    }
    catch(\Exception $e)
    {
      $this->_caller->err("Failed to read from redis",Redis::ERROR,__LINE__);
    }
    $this->_caller->logDebug("---READ LINE---".PHP_EOL.$resp);
    return $resp;
  }
  
  /**
   *@brief Read $bytes bytes from redis
   *
   *Reads a response from redis, of $bytes length if debugging is enabled then logs to the debug file
   *@param int $bytes the number of bytes to read
   *@return string the response
   *@exception RedisException when unable to communicate with redis
   */
  private function _read($bytes)
  {
    $this->connect();
    $resp=stream_get_contents($this->_connection,$bytes);
    if($resp===false) $this->_caller->err("Failed to read from redis",Redis::ERROR,__LINE__);
    $this->_caller->logDebug("---READ---".PHP_EOL."BYTES $bytes".PHP_EOL.$resp);
    return $resp;
  }
  
  /**
   *@brief Read a bulk reply from redis
   *
   *Reads a bulk reply, the number of responses is the first line, if it's -1 then null is returned which means the request doesn't exist<br />
   *If the length is 0 then an empty string is returned<br />
   *Else $length bytes is read and returned.<br />
   *Lastly if debugging is enabled then the EOL read is noted and the EOL marker is read so it's stripped from the response.
   *
   *@return string|null the response
   */
  private function _readBulk()
  {
    $length=rtrim($this->_readLine());
    if($length==-1) return null;
    else if($length==0) $reply="";
    else $reply=$this->_read($length);
    $this->_caller->logDebug("---READEOL---");
    $this->_read(strlen($this->_eol));
    $this->_caller->logDebug("---READEOLEND---");
    return $reply;
  }
  
  /**
   *@brief Read a multibulk reply from redis
   *
   *Reads multi bulk response from redis, the number of bulk replies is the first line<br />
   *If the number of bulks is -1 then the request doesn't exist and null is returned, otherwise response() is run for each bulk and returned in an array
   *@return array an array of responses
   */
  private function _readMultiBulk()
  {
    $numBulks=rtrim($this->_readLine());
    if($numBulks==-1) return null;
    $replies=array();
    for($i=0;$i<$numBulks;++$i)
    {
      $replies[]=$this->response();
    }
    return $replies;
  }

  
}


/**
 *@brief RedisException class handles exceptions caused by Redis
 */

class RedisException extends \ErrorException
{
  /**
   *constructor extends error exception<br />
   *<code>ErrorException([string $exception [, long $code, [ long $severity, [ string $filename, [ long $lineno  [, Exception $previous = NULL]]]]]])</code><br />
   *If Config ON_EXCEPTION == file then the contents of ON_EXCEPTION_FILE are displayed, else if ON_EXCEPTION == silent then the script is killed silently (you should enable logging if you're going to do this) else it is handle like a normal exception<br />
   *@param Redis $caller the calling object
   *@param string $exception the exception message
   *@param int $severity the severity of the exception will be either RedisError::ERROR or RedisError::WARNING
   *@param string $filename the file name which caused the error normally __FILE__
   *@param int $lineno the line number that the error occurred on, normally passed by __LINE__ from RedisError::err()
   *@param Exception $previous the previous exception or null
   *@see ErrorException::__construct()
   */
  public function __construct($caller,$exception,$severity,$filename,$lineno,$previous)
  {
    switch($caller->config("ON_EXCEPTION"))
    {
      case "file":
		  echo file_get_contents($caller->config("ON_EXCEPTION_FILE"));
      break;
      case "silent":
		  exit(1);
    }
    /**
     *@see ErrorException::__construct()
     */
    parent::__construct($exception,0,$severity,$filename,$lineno,$previous);
  }
}


