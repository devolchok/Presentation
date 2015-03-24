<?php
namespace Project\Core;

class View
{

  protected $layoutFilePath;
  protected $templatesFolderPath;
  protected $pageTitle;
  protected $viewId;
  protected $css = array();
  protected $js = array();

  protected $user;
  protected $messages;

  public function __construct($layoutFilePath = null)
  {
    if ($layoutFilePath) {
      $this->setLayoutFilePath($layoutFilePath);
    }
  }

  public function setLayoutFilePath($layoutFilePath)
  {
    $this->layoutFilePath = $layoutFilePath;
  }

  public function setTemplatesFolderPath($templatesFolderPath)
  {
    $this->templatesFolderPath = $templatesFolderPath;
  }

  public function getTemplatesFolderPath()
  {
    return $this->templatesFolderPath;
  }

  public function setPageTitle($pageTitle)
  {
    $this->pageTitle = $pageTitle;
  }

  public function registerCss($uri)
  {
    $this->css[] = $uri;
  }

  public function registerJs($uri, $place = 'bottom')
  {
    $this->js[$place][] = $uri;
  }

  public function renderPage($template, $data = array(), $templateFolderPath = null)
  {
    $content = $this->renderTemplate($template, $data, $templateFolderPath);
    $pageContent = $this->render($this->layoutFilePath, array('content' => $content));

    return $pageContent;
  }

  public function renderTemplate($template, $data = array(), $templateFolderPath = null)
  {
    $templateFolderPath = $templateFolderPath ? $templateFolderPath : $this->templatesFolderPath;
    $templateFilePath = $templateFolderPath . '/' . $template . '.php';

    return $this->render($templateFilePath, $data);
  }

  public function templateExists($template, $templateFolderPath = null)
  {
    $templateFolderPath = $templateFolderPath ? $templateFolderPath : $this->templatesFolderPath;
    $templateFilePath = $templateFolderPath . '/' . $template . '.php';

    return file_exists($templateFilePath);
  }

  public function renderCss()
  {
    $css = '';
    foreach ($this->css as $cssUri) {
      $css .= '<link href="' . $cssUri . '" rel="stylesheet" media="screen">' . PHP_EOL;
    }

    return $css;
  }

  public function renderJs($place)
  {
    $js = '';
    if (isset($this->js[$place])) {
      foreach ($this->js[$place] as $jsUri) {
        $js .= '<script src="' .$jsUri . '"></script>' . PHP_EOL;
      }
    }

    return $js;
  }

  private function render($file, $data)
  {
    extract($data);
    ob_start();
    if (!file_exists($file)) {
      throw new \Exception('Template file doesn\'t exist: ' . $file);
    }
    require($file);
    $content = ob_get_contents();
    ob_end_clean();
    return  $content;
  }

  public function setUser($user)
  {
    $this->user = $user;
  }

  public function setMessages($messages)
  {
    $this->messages = $messages;
  }

  public function setViewId($viewId)
  {
    $this->viewId = $viewId;
  }

}
