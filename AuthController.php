<?php

use Project\Components\App;
use Project\Components\View;
use Project\Modules\Auth\AuthModel;
use Project\Modules\User\UserModel;

class AuthController extends \Project\Components\Controller
{

  public function registrationAction()
  {
    if (App::user()->isLoggedIn()) {
      App::request()->redirect('/');
    }

    // POST
    if (App::request()->isPost()) {
      $this->requireAjaxRequest();
      $this->requirePostRequest(array('email', 'password', 'username'));
      $authModel = new AuthModel();
      $this->initModel($authModel);
      $token = $authModel->register(array(
        'email' => App::request()->getPostParameter('email'),
        'password' => App::request()->getPostParameter('password'),
        'username' => App::request()->getPostParameter('username'),
        'firstName' => App::request()->getPostParameter('firstName'),
        'lastName' => App::request()->getPostParameter('lastName'),
      ));
      if ($token) {
        $view = new View();
        $this->lightInitView($view);
        App::mailer()->mail($_POST['email'], 'Регистрация', $view->renderTemplate('registration.mail', array(
          'host' => App::request()->getFullHost(),
          'token' => $token,
        )));

        $this->outputJson(array(
          'status' => 'ok',
          'successMessage' => 'На Ваш E-Mail была выслана инструкция по окончанию регистрации.',
        ));
      }
      else {
        $errorMessage = '';
        if ($authModel->getError() == 'email_exists') {
          $errorMessage = 'Пользователь c таким E-Mail уже существует.';
        }
        if ($authModel->getError() == 'user_exists') {
          $errorMessage = 'Пользователь c таким именем уже существует.';
        }
        $this->outputJson(array(
          'status' => 'error',
          'errorMessage' => $errorMessage,
        ));
      }
    }
    // GET
    else {
      $view = new View();
      $this->initView($view);
      $view->setPageTitle('Регистрация');
      if (isset($_GET['token'])) {
        $authModel = new AuthModel();
        $this->initModel($authModel);
        if ($authModel->confirm($_GET['token'])) {
          $this->output($view->renderPage('message.page', array(
            'message' => 'Спасибо за регистрацию! Теперь Вы можете войти на сайт.'
          ), 'site'));
        }
        else {
          $view->setPageTitle('Ошибка');
          $this->output($view->renderPage('message.page', array(
            'message' => 'Неверный токен. Возможно, истекло время дейтсвия токена. Необходимо пройти процесс регистрации заново.'
          ), 'site'));
        }
      }
      else {
        $this->output($view->renderPage('registration.page'));
      }
    }
  }

  public function loginAction()
  {
    if (App::user()->isLoggedIn()) {
      App::request()->redirect('/');
    }

    // POST
    if (App::request()->isPost()) {
      $this->requireAjaxRequest();
      $this->requirePostRequest(array('email', 'password'));
      $authModel = new AuthModel();
      $this->initModel($authModel);
      $userId = $authModel->authenticate($_POST['email'], $_POST['password']);
      if ($userId) {
        $userModel = new UserModel();
        $this->initModel($userModel);
        $userData = $userModel->getUserData($userId);
        App::user()->login($userData);
        $this->outputJson(array(
          'status' => 'ok',
          'redirect' => isset($_POST['destination']) ? $_POST['destination'] : '/' . $userData['username'] . '/',
        ));
      }
      else {
        $errorMessage = '';
        if ($authModel->getError() == 'user_blocked') {
          $errorMessage = 'Аккаунт заблокирован.';
        }
        if ($authModel->getError() == 'credentials_error') {
          $errorMessage = 'Неверный E-Mail или пароль.';
        }
        $this->outputJson(array(
          'status' => 'error',
          'errorMessage' => $errorMessage,
        ));
      }
    }
    // GET
    else {
      $view = new View();
      $this->initView($view);
      $view->setPageTitle('Вход');
      $this->output($view->renderPage('login.page'));
    }
  }

  public function logoutAction()
  {
    App::user()->logout();
    App::request()->redirect('/');
  }

  public function recoveryAction()
  {
    if (App::user()->isLoggedIn()) {
      App::request()->redirect('/');
    }

    if (empty($_REQUEST['token'])) {
      // POST
      if (App::request()->isPost()) {
        $this->requireAjaxRequest();
        $this->requirePostRequest(array('email'));
        $authModel = new AuthModel();
        $this->initModel($authModel);
        $token = $authModel->recover($_POST['email']);
        if ($token) {
          $view = new View();
          $this->lightInitView($view);
          App::mailer()->mail($_POST['email'], 'Восстановление доступа', $view->renderTemplate('recovery.mail', array(
            'host' => App::request()->getFullHost(),
            'token' => $token,
          )));
        }
        $this->outputJson(array(
          'status' => 'ok',
          'successMessage' => 'На Ваш E-Mail была выслана инструкция по восстановлению пароля.',
        ));
      }
      // GET
      else {
        $view = new View();
        $this->initView($view);
        $view->setPageTitle('Восстановление пароля');
        $this->output($view->renderPage('recovery.page'));
      }
    }
    else {
      $this->newPassword();
    }
  }

  private function newPassword()
  {
    $authModel = new AuthModel();
    $this->initModel($authModel);

    // POST
    if (App::request()->isPost()) {
      $this->requireAjaxRequest();
      $this->requirePostRequest(array('password', 'token'));
      if ($authModel->changePasswordByToken($_POST['password'], $_POST['token'])) {
        $this->outputJson(array(
          'status' => 'ok',
          'successMessage' => 'Теперь Вы можете войти на сайт, используя новый пароль.',
        ));
      }
      else {
        $this->outputJson(array(
          'status' => 'error',
          'errorMessage' => 'Неверный токен. Возможно, истекло время дейтсвия токена. Необходимо пройти процесс регистрации заново.',
        ));
      }
    }
    // GET
    else {
      $view = new View();
      $this->initView($view);
      if ($authModel->verifyChangePasswordToken($_GET['token'])) {
        $view->setPageTitle('Новый пароль');
        $this->output($view->renderPage('recovery_new_password.page', array('token' => $_GET['token'])));
      }
      else {
        $view->setPageTitle('Ошибка');
        $this->output($view->renderPage('message.page', array(
          'message' => 'Неверный токен. Возможно, истекло время дейтсвия токена. Необходимо пройти процесс регистрации заново.'
        ), 'site'));
      }
    }
  }

  public function changePasswordAction()
  {
    $this->requireUser();
    $this->requireAjaxRequest();
    $this->requirePostRequest(array('currentPassword', 'password'));
    $authModel = new AuthModel();
    $this->initModel($authModel);
    if ($authModel->changePasswordByCurrentPassword(App::user()->get('id'), $_POST['currentPassword'], $_POST['password'])) {
      $this->outputJson(array(
        'status' => 'ok',
        'successMessage' => 'Пароль успешно изменен.',
      ));
    }
    else {
      $this->outputJson(array(
        'status' => 'error',
        'errorMessage' => 'Текущий пароль задан неверно.',
      ));
    }
  }

}