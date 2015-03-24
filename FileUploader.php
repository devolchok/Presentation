<?php
namespace Project\Helpers;
 
class FileUploader
{
  private $file;
  private $maxFileSize;
  private $allowedExtensions;
  private $disallowedExtensions;
  private $allowedMimeTypes;
  private $disallowedMimiTypes;
  private $error;
  private $overrideFileFlag = true;

  public function upload(array $file, $filePath)
  {
    if ($file == null || !isset($file['name'])) {
      throw new \Exception('Invalid file parameter');
    }

    $this->file = $file;

    if ($this->file['error'] !== 0) {
       throw new \Exception('Error on file uploading. Code of error: ' . $this->file['error']);
    }

    $this->validateFile();
    if ($this->hasError()) {
      return false;
    }

    if (!$this->overrideFileFlag) {
      if (file_exists($filePath)) {
        $this->setError('file_exists');
        return false;
      }
    }

    if (!move_uploaded_file($this->file['tmp_name'], $filePath)) {
       throw new \Exception('Error on moving of uploaded file.');
    }

    return true;
  }

  private function validateFile()
  {
    $fileSize = $this->file['size'];

    if (isset($this->maxFileSize)) {
      if ($fileSize > $this->maxFileSize) {
        $this->setError('large_file_size');
        return false;
      }
    }

    $fileExtension = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));

    if (isset($this->allowedExtensions)) {
      if (!in_array($fileExtension, $this->allowedExtensions)) {
        $this->setError('disallowed_extension');
        return false;
      }
    }

    if (isset($this->disallowedExtensions)) {
      if (in_array($fileExtension, $this->disallowedExtensions)) {
        $this->setError('disallowed_extension');
        return false;
      }
    }

    $mimeType = $this->file['type'];

    if (isset($this->allowedMimeTypes)) {
      if (!in_array($mimeType, $this->allowedMimeTypes)) {
        $this->setError('disallowed_mime_type');
        return false;
      }
    }

    if (isset($this->disallowedMimeTypes)) {
      if (in_array($mimeType, $this->disallowedMimeTypes)) {
        $this->setError('disallowed_mime_type');
        return false;
      }
    }

    return true;
  }

  public function getError()
  {
    return $this->error;
  }

  public function setError($error)
  {
    $this->error = $error;
  }

  public function hasError()
  {
    return isset($this->error);
  }

  public function setAllowedExtensions(array $allowedExtensions)
  {
    $this->allowedExtensions = $allowedExtensions;
  }

  public function setDisallowedExtensions(array $disallowedExtensions)
  {
    $this->disallowedExtensions = $disallowedExtensions;
  }

  public function setAllowedMimeTypes(array $allowedMimeTypes)
  {
    $this->allowedMimeTypes = $allowedMimeTypes;
  }

  public function setDisallowedMimiTypes(array $disallowedMimiTypes)
  {
    $this->disallowedMimiTypes = $disallowedMimiTypes;
  }

  public function setMaxFileSize($maxSize)
  {
    $this->maxFileSize = $maxSize*1024*1024;
  }

  public function setOverrideFileFlag($flag)
  {
    $this->overrideFileFlag = $flag;
  }

}