<?php
namespace Project\Core;

class Session
{

  public function __construct()
  {
    if ($this->wasStarted()) {
      session_start();
    }
  }

  public function start()
  {
    if (!$this->isStarted()) {
      session_start();
    }
  }

  public function isStarted()
  {
    return (bool)session_id();
  }

  public function wasStarted()
  {
    return App::cookie()->exists(session_name());
  }

  public function close()
  {
    if ($this->isStarted()) {
      session_write_close();
    }
  }

  public function destroy()
  {
    if ($this->isStarted()) {
      session_destroy();
    }
  }

  public function getId()
  {
    return session_id();
  }

  public function getName()
  {
    return session_name();
  }

  public function regenerateId($deleteOldSession = false)
  {
    if ($this->isStarted()) {
      session_regenerate_id($deleteOldSession);
    }
  }

  public function get($key, $remove = false)
  {
    if (isset($_SESSION[$key])) {
      $value = $_SESSION[$key];
      if ($remove) {
        unset($_SESSION[$key]);
      }
      return $value;
    }
    else {
      return null;
    }
  }

  public function set($key, $value)
  {
    $this->start();
    $_SESSION[$key] = $value;
  }

  public function remove($key)
  {
    if ($this->has($key)) {
      unset($_SESSION[$key]);
    }
  }

  public function has($key)
  {
    return isset($_SESSION[$key]) ? true : false;
  }

  public function clear()
  {
    if ($this->isStarted()) {
      session_unset();
    }
  }

}