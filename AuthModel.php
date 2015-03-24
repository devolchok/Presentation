<?php

namespace Project\Modules\Auth;

use Project\Components\Model;
use Project\Core\ValidationException;

class AuthModel extends Model
{
  private $email;
  private $password;
  private $username;
  private $firstName;
  private $lastName;
  private $token;

  public function register(array $data)
  {
    $this->initData($data);
    $this->validate('register');

    if ($user = $this->userExists($this->email, $this->username)) {
      if (strcasecmp($user['email'], $this->email) === 0) {
        $this->setError('email_exists');
      }
      elseif (strcasecmp($user['username'], $this->username) === 0) {
        $this->setError('user_exists');
      }
      return false;
    }
    $userId = $this->createUser();
    $token = $this->createToken($userId);

    return $token;
  }

  public function confirm($token)
  {
    $this->token = trim($token);
    $this->validate('verify_token');

    if ($userId = $this->verifyToken($this->token)) {
      $this->activateUser($userId);
      $this->removeToken($this->token);
      mkdir('usrimg/id' . $userId);
      return true;
    }
    else {
      return false;
    }
  }

  public function recover($email)
  {
    $this->email = trim($email);
    $this->validate('confirm');

    if ($user = $this->userExists($this->email)) {
      $token = $this->createToken($user['id']);
      return $token;
    }
    else {
      return false;
    }
  }

  public function verifyChangePasswordToken($token)
  {
    $this->token = trim($token);
    $this->validate('verify_token');

    if ($this->verifyToken($this->token)) {
      return true;
    }
    else {
      return false;
    }
  }

  public function changePasswordByToken($password, $token)
  {
    $this->password = $password;
    $this->token = trim($token);
    $this->validate('change_password_by_token');

    if ($userId = $this->verifyToken($this->token)) {
      $this->changePassword($userId, $this->password);
      $this->removeToken($this->token);
      return true;
    }
    else {
      return false;
    }
  }

  public function changePasswordByCurrentPassword($userId, $currentPassword, $password)
  {
    $this->password = $password;
    $this->validate('change_password_by_current_password');
    $user = $this->userExists(null, null, $userId);
    if (password_verify($currentPassword, $user['password'])) {
      $this->changePassword($userId, $this->password);
      return true;
    }
    else {
      return false;
    }
  }

  public function authenticate($email, $password)
  {
    $this->email = trim($email);
    $this->validate('authenticate');

    if ($user = $this->userExists($this->email)) {
      if (password_verify($password, $user['password'])) {
        if (!$user['blocked']) {
          return $user['id'];
        }
        else {
          $this->setError('user_blocked');
        }
      }
      else {
        $this->setError('credentials_error');
      }
    }
    else {
      $this->setError('credentials_error');
    }

    return false;
  }

  private function createToken($userId)
  {
    $token = $this->generateToken();

    $st = $this->db->prepare("
       INSERT INTO user_tokens
       (user_id, token, expiration)
       VALUES (:userId, :token, FROM_UNIXTIME(:expiration));
    ");
    $st->bindValue(':userId', $userId);
    $st->bindValue(':token', $token);
    $st->bindValue(':expiration', time() + 3600*24*15);
    $st->execute();

    return $token;
  }

  private function verifyToken($token)
  {
    $st = $this->db->prepare("
      SELECT user_id FROM user_tokens
      WHERE token = :token AND expiration > FROM_UNIXTIME(:currentTime)
      LIMIT 1;
    ");
    $st->bindValue(':token', $token);
    $st->bindValue(':currentTime', time());
    $st->execute();
    if ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
      return  $row['user_id'];
    }
    else {
      return false;
    }
  }

  private function removeToken($token)
  {
    $st = $this->db->prepare("
      DELETE FROM user_tokens
      WHERE token = :token
      LIMIT 1;
    ");
    $st->bindValue(':token', $token);
    $st->execute();
    return true;
  }

  private function activateUser($userId)
  {
    $st = $this->db->prepare("
      UPDATE users
      SET activated = 1
      WHERE id = :userId
      LIMIT 1;
    ");
    $st->bindValue(':userId', $userId);
    $st->execute();
  }

  private function userExists($email = null, $username = null, $userId = null)
  {
    $st = $this->db->prepare("
       SELECT id, email, username, password, blocked FROM users
       WHERE (email = :email OR username = :username OR id = :userId) AND activated = 1
       LIMIT 1;
    ");
    $st->bindValue(':email', $email);
    $st->bindValue(':username', $username);
    $st->bindValue(':userId', $userId);
    $st->execute();

    return $st->fetch(\PDO::FETCH_ASSOC);
  }

  private function createUser()
  {
    $st = $this->db->prepare("
       INSERT INTO users
       (email, password, username, first_name, last_name, user_pic, user_pic_square)
       VALUES (:email, :password, :username, :firstName, :lastName, :userPic, :userPicSquare);
    ");
    $st->bindValue(':email', $this->email);
    $st->bindValue(':password', password_hash($this->password, PASSWORD_DEFAULT));
    $st->bindValue(':username', $this->username);
    $st->bindValue(':firstName', $this->firstName);
    $st->bindValue(':lastName', $this->lastName);
    $st->bindValue(':userPic', 'img/user_pic_default.jpg');
    $st->bindValue(':userPicSquare', 'img/user_pic_square_default.jpg');
    $st->execute();

    return $this->db->lastInsertId();
  }

  private function changePassword($userId, $password)
  {
    $st = $this->db->prepare("
      UPDATE users
      SET password = :password
      WHERE id = :userId
      LIMIT 1;
    ");
    $st->bindValue(':userId', $userId);
    $st->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
    $st->execute();
  }

  private function initData(array $data)
  {
    foreach ($data as $key => $value) {
      if ($key == 'password') {
        $this->$key = $value;
      }
      $this->$key = trim($value);
    }
  }

  private function validate($scenario = null)
  {
    if ($scenario == 'register') {
      if (!$this->checkVars(array($this->email, $this->password, $this->username))) {
        return false;
      }
    }

    if ($scenario == 'confirm') {
      if (!$this->checkVars(array($this->email))) {
        return false;
      }
    }

    if ($scenario == 'verify_token') {
      if (!$this->checkVars(array($this->token))) {
        return false;
      }
    }

    if ($scenario == 'change_password_by_token') {
      if (!$this->checkVars(array($this->password, $this->token))) {
        return false;
      }
    }

    if ($scenario == 'change_password_by_current_password') {
      if (!$this->checkVars(array($this->password))) {
        return false;
      }
    }

    if ($scenario == 'authenticate') {
      if (!$this->checkVars(array($this->email))) {
        return false;
      }
    }

    if (!empty($this->email)) {
      if (!$this->checkMaxLength($this->email, 40)) {
        return false;
      }
      if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('Email is not correct.');
      }
    }

    if (!empty($this->password)) {
      if (!$this->checkMinLength($this->password, 6) || !$this->checkMaxLength($this->password, 30)) {
        return false;
      }
    }

    if (!empty($this->username)) {
      if (!$this->checkMinLength($this->username, 4) || !$this->checkMaxLength($this->username, 20)) {
        return false;
      }
      if (!preg_match('/^[a-z][a-z0-9]*$/i', $this->username)) {
        throw new ValidationException('Username is not correct.');
      }
    }

    if (!empty($this->firstName)) {
      if (!$this->checkMaxLength($this->firstName, 20)) {
        return false;
      }
    }

    if (!empty($this->lastName)) {
      if (!$this->checkMaxLength($this->lastName, 20)) {
        return false;
      }
    }

    return true;
  }

}