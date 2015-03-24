<?php

use Project\Components\App;
use Project\Components\Controller;
use Project\Core\HttpException;
use Project\Core\ValidationException;

$rootPath = __DIR__ . '/..';
require($rootPath . '/Code/bootstrap.php');

try {
  App::router()->run(App::request()->getUri());
}
catch(HttpException $e) {
  (new Controller())->handleHttpError($e->getMessage(), $e->getCode());
}
catch(PDOException $e) {
  App::log('DB Error: ' . $e->getMessage() . ' (' . $e->getCode() . ')');
  (new Controller())->handleHttpError('Internal Error', 500);
}
catch(ValidationException $e) {
  App::log('Validation Error: ' . $e->getMessage());
  (new Controller())->handleHttpError('Bad Request', 400);
}
catch(Exception $e) {
  App::log('Internal Error: ' . $e->getMessage());
  (new Controller())->handleHttpError('Internal Error', 500);
}