<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc;

class FileHelper
{
    /**
     * @param string|null $filename
     * @return string
     */
    public static function getUploadUrl(?string $filename = null): string
    {
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? '/uploads/';
        return $_ENV['SITE_URL'] . $uploadPath . $filename;
    }

    /**
     * Handles file upload, return a filename or false
     * @param $file
     * @return string|bool
     */
    public function upload($file): string|bool
    {
        $uploadDir = $_ENV['UPLOAD_PATH'] ?? '/uploads/';

        $dir = APP_PATH.$uploadDir;
        $pathInfo = pathinfo($file['name']);

        $filename = md5($pathInfo['filename'] . time()) . '.'. $pathInfo['extension'];
        $uploadPath = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return false;
        }

        return $filename;
    }
}
