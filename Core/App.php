<?php
namespace Project\Core;

class App
{
  protected static $store;
  protected static $request;
  protected static $router;
  protected static $user;
  protected static $logger;
  protected static $messenger;
  protected static $session;
  protected static $cookie;
  protected static $db;
  protected static $config;

  public static function get($key)
  {
    return isset(self::$store[$key]) ? self::$store[$key] : null;
  }

  public static function set($key, $value)
  {
    self::$store[$key] = $value;
  }

  public static function log($logMessage, $additionalInfo = null)
  {
    if (!self::$logger) {

      if (isset(self::config()['logPath'])) {
        $logger = new Logger();
        $logger->setFilePath(self::config()['logPath']);
        self::$logger = $logger;
      }
      else {
        throw new \Exception('Log file path is not set.');
      }
    }
    self::$logger->log($logMessage, $additionalInfo);
  }

  public static function user()
  {
    if (!self::$user) {
      self::$user = new User();
    }
    return self::$user;
  }

  public static function messenger()
  {
    if (!self::$messenger) {
      self::$messenger = new Messenger();
    }
    return self::$messenger;
  }

  public static function config()
  {
    return self::$config;
  }

  public static function setConfig($config)
  {
    self::$config = $config;
  }

  public static function request()
  {
    if (!self::$request) {
      self::$request = new Request();
    }
    return self::$request;
  }

  public static function session()
  {
    if (!self::$session) {
      self::$session = new Session();
    }
    return self::$session;
  }

  public static function cookie()
  {
    if (!self::$cookie) {
      self::$cookie = new Cookie();
    }
    return self::$cookie;
  }

  public static function db()
  {
    if (!self::$db) {
      if (isset(self::config()['db'])) {
        $db = new \PDO(self::config()['db']['connectionString'],
          self::config()['db']['username'],
          self::config()['db']['password']);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$db = $db;
      }
      else {
        throw new \Exception('Database configuration is not set.');
      }
    }
    return self::$db;
  }

  public static function router()
  {
    if (!self::$router) {
      self::$router = new Router();
    }
    return self::$router;
  }

}