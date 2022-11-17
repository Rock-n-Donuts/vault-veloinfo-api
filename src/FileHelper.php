<?php

namespace Rockndonuts\Hackqc;

use \Imagick;

class FileHelper
{
    private array $validUploads = [];
    private array $errors = [];

    /**
     * @param string|null $filename
     * @return string
     */
    public static function getUploadUrl(?string $filename = null): string
    {
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? '/uploads/';
        return $_ENV['SITE_URL'] . $uploadPath . $filename;
    }

    public function clear(): static
    {
        $this->validUploads = [];

        return $this;
    }

    public function upload($file): mixed
    {
        error_reporting(E_ALL);

        $dir = APP_PATH."/uploads/";
        $pathInfo = pathinfo($file['name']);

        $filename = md5($pathInfo['filename'] . time()) . '.'. $pathInfo['extension'];
        $uploadPath = $dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $filename;
        }
        return false;
    }

    public function getUploadPath($file)
    {
        $uploadDir = $_ENV['UPLOAD_PATH'] ?? '/uploads/';

        $dir = APP_PATH.$uploadDir;
        $pathInfo = pathinfo($file['name']);

        $filename = md5($pathInfo['filename'] . time()) . '.'. $pathInfo['extension'];
        return ['dir' => $dir, 'filename' => $filename];
    }

    /**
     * @throws \ImagickException
     */
    public function resizeAndUpload(?array $imageData = null)
    {
        $image = $imageData['tmp_name'];
        $imagick = new Imagick($image);


        $uploadPath = $this->getUploadPath($imageData);
        $fullUploadPath = $uploadPath['dir'].$uploadPath['filename'];
        $sizes = $imagick->getImageGeometry();

        $newHeight = $sizes['height'];
        $width = $sizes['width'];

        if ($sizes['width'] > 1040) {
            $imgRatio = $sizes['width'] / $sizes['height'];
            $newHeight = 1040 * $imgRatio;

            $width = 1040;
            $imagick->scaleImage(1040, (int)$newHeight, true);
        }

        $imagick->writeImage($fullUploadPath);

        return [
            'path'  =>  $uploadPath['filename'],
            'width'  =>  (int)$width,
            'height'  =>  (int)$newHeight,
        ];
    }

}