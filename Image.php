<?php
namespace Project\Helpers;
 
class Image
{
  private $image;
  private $type;
  private $width;
  private $height;

  public function load($filePath)
  {
    if (!is_file($filePath) || !file_exists($filePath)) {
      throw new \Exception('Image doesn\'t exist');
    }

    list($this->width, $this->height, $this->type) = getimagesize($filePath);

    switch ($this->type)
    {
      case IMAGETYPE_JPEG : {
        $this->image = imagecreatefromjpeg($filePath);
        break;
      }
      case IMAGETYPE_GIF : {
        $this->image = imagecreatefromgif($filePath);
        break;
      }
      case IMAGETYPE_PNG : {
        $this->image = imagecreatefrompng($filePath);
        break;
      }
      default : {
        throw new \Exception('File is not image');
      }
    }

    if (!$this->image) {
      throw new \Exception('Error on loading image.');
    }

    return true;
  }

  public function resize($width, $height)
  {
    $newImage = imagecreatetruecolor($width, $height);
    if (!$newImage) {
      throw new \Exception('Error on resizing image.');
    }
    $resizeResult = imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
    if ($resizeResult) {
      $this->image = $newImage;
      $this->width = $width;
      $this->height = $height;
    }
    else {
      throw new \Exception('Error on resizing image.');
    }
  }

  public function resizeToWidth($width)
  {
    $height = $this->height * $width / $this->width;
    $this->resize($width, $height);
  }

  public function resizeToHeight($height)
  {
    $width = $this->width * $height / $this->height;
    $this->resize($width, $height);
  }

  public function crop($rect)
  {
    $newImage = imagecrop($this->image, $rect);
    if ($newImage) {
      $this->image = $newImage;
      $this->width = $rect['width'];
      $this->height = $rect['height'];
    }
    else {
      throw new \Exception('Error on cropping image.');
    }
  }

  public function save($filePath)
  {
    switch ($this->type)
    {
      case IMAGETYPE_JPEG : {
        $saveResult = imagejpeg($this->image, $filePath, 100);
        break;
      }
      case IMAGETYPE_GIF : {
        $saveResult = imagegif($this->image, $filePath);
        break;
      }
      case IMAGETYPE_PNG : {
        $saveResult = imagepng($this->image, $filePath);
        break;
      }
      default : {
        $saveResult = false;
      }
    }

    if (!$saveResult) {
      throw new \Exception('Error on saving image.');
    }
  }

  public function getWidth()
  {
    return $this->width;
  }

  public function getHeight()
  {
    return $this->height;
  }

}