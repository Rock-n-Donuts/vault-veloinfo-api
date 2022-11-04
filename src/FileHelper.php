<?php

namespace Rockndonuts\Hackqc;

class FileHelper
{
    private array $validUploads = [];
    private array $errors = [];

    public function clear(): static
    {
        $this->validUploads = [];

        return $this;
    }

    public function upload($file): mixed
    {
        $dir = APP_PATH."/uploads/";
        $pathInfo = pathinfo($file['name']);

        $filename = md5($pathInfo['filename'] . time()) . '.'. $pathInfo['extension'];
        $uploadPath = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return false;
        }

        return $filename;
    }


}