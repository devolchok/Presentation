<?php
namespace Project\Core;

class Router
{
  private $controllerName;
  private $defaultControllerName;
  private $actionName;
  private $defaultActionName;
  private $parameters = array();
  private $routes = array();
  private $isAppliedRoutes = false;

  public function parseRequest($request)
  {
    $segments = $this->getSegments($request);

    $controllerName = isset($segments[0]) ? $segments[0] : $this->defaultControllerName;
    $actionName = isset($segments[1]) ? $segments[1] : $this->defaultActionName;
    $parameters = array();
    for ($i = 2; isset($segments[$i]); $i++) {
      $parameters[] = $segments[$i];
    }

    $this->controllerName = $controllerName;
    $this->actionName = $actionName;
    $this->parameters = $parameters;

    if (is_numeric($actionName)) {
      array_unshift($this->parameters, $actionName);
      $this->actionName = $this->defaultActionName;
    }

    return array(
      'controllerName' => $this->controllerName,
      'actionName' => $this->actionName,
      'parameters' => $this->parameters,
    );
  }

  public function run($request = null)
  {
    if ($request) {
      $this->parseRequest($request);
    }
    $controllerClassName = ucfirst($this->controllerName) . 'Controller';
    $actionMethodName = $this->actionName . 'Action';
    if (method_exists($controllerClassName, $actionMethodName)) {
      $controller = new $controllerClassName();
      $controller->setControllerName($this->controllerName);
      $controller->setActionName($this->actionName);
      if (method_exists($controllerClassName, 'beforeAction')) {
        $controller->beforeAction();
      }
      call_user_func_array(array($controller, $actionMethodName), $this->parameters);
      if (method_exists($controllerClassName, 'afterAction')) {
        $controller->afterAction();
      }
    }
    else {
      if (!$this->isAppliedRoutes && $this->applyRoutes()) {
        $this->run();
      }
      else {
        throw new HttpException('Not Found', 404);
      }
    }
  }

  public function setDefaultControllerName($defaultControllerName)
  {
    $this->defaultControllerName = $defaultControllerName;
  }

  public function setDefaultActionName($defaultActionName)
  {
    $this->defaultActionName = $defaultActionName;
  }

  public function getControllerName()
  {
    return $this->controllerName;
  }

  public function getActionName()
  {
    return $this->actionName;
  }

  public function getParameters()
  {
    return $this->parameters;
  }

  public function setRoutes($routes)
  {
    $this->routes = $routes;
  }

  private function applyRoutes()
  {
    $this->isAppliedRoutes = true;
    foreach ($this->routes as $from => $to) {
      $segmentsFrom = $this->getSegments($from);
      $segmentsTo = $this->getSegments($to);
      if ($segmentsFrom[0] == $this->controllerName) {
        array_unshift($this->parameters, $this->actionName);
        $this->controllerName = $segmentsTo[0];
        $this->actionName = $segmentsTo[1];
        return true;
      }
      if ($segmentsFrom[0] == '*') {
        array_unshift($this->parameters, $this->actionName);
        array_unshift($this->parameters, $this->controllerName);
        $this->controllerName = $segmentsTo[0];
        $this->actionName = $segmentsTo[1];
        return true;
      }
    }

    return false;
  }

  private function getSegments($request)
  {
    $getParametersStartPosition = strpos($request, '?');
    $requestWithoutGetParameters = $getParametersStartPosition === false ? $request : substr($request, 0, strpos($request, '?'));
    $segments = explode('/', $requestWithoutGetParameters);
    $segments = array_values(array_diff($segments, array('')));

    return $segments;
  }

}