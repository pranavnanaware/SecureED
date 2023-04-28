<?php
class FileUploader {
    private $file;
    private $fileName;
    private $uploadPath;

    public function __construct($file) {
        $this->file = $file;
        $this->fileName = basename($file['name']);
        $this->uploadPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public function validateFileType($allowedTypes) {
        $fileType = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        return in_array($fileType, explode(",", $allowedTypes));
    }

    public function uploadFile() {
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0777, true);
        }

        move_uploaded_file($this->file['tmp_name'], $this->uploadPath . $this->fileName);
    }

    public function getFilePath() {
        return $this->uploadPath . $this->fileName;
    }
}
?>