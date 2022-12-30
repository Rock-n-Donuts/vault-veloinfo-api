<?php

namespace Rockndonuts\Hackqc;

use \Imagick;

class FileHelper
{
    private array $validUploads = [];
    private array $errors = [];
    private array $allowedUploadMimes = [
        'image/jpeg',
        'image/jpg',
	'image/png',
	'image/heic',
	'image/heif'
    ];

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
        if (!$mime = $this->isValidUploadMime($image)) {
            return [];
        }

        $imagick = new Imagick($image);

        $uploadPath = $this->getUploadPath($imageData);
        $fullUploadPath = $uploadPath['dir'].$uploadPath['filename'];
        $sizes = $imagick->getImageGeometry();

        $newHeight = $sizes['height'];
        $width = $sizes['width'];

        if ($sizes['width'] > $_ENV['MAX_IMAGE_WIDTH']) {
            $imgRatio = $sizes['width'] / $sizes['height'];
            $newHeight = (int)$_ENV['MAX_IMAGE_WIDTH'] * $imgRatio;

            $width = $_ENV['MAX_IMAGE_WIDTH'];
            $imagick->resizeImage($_ENV['MAX_IMAGE_WIDTH'], (int)$newHeight, imagick::FILTER_UNDEFINED, 1, true);
            $newHeight = $imagick->getImageGeometry()['height'];
        }

        if (!in_array($mime, ['image/jpeg', 'image/jpg'])) {
            $realFilename = pathinfo($uploadPath['filename'], PATHINFO_FILENAME);
            $jpgFile = new Imagick();

            $jpgFile->newImage($_ENV['MAX_IMAGE_WIDTH'], (int)$newHeight, "white");
            $jpgFile->compositeimage($imagick, Imagick::COMPOSITE_OVER, 0, 0);
            $jpgFile->setImageFormat('jpg');
            $jpgFile->setCompressionQuality(80);
            $jpgFile->writeImage($uploadPath['dir'].$realFilename.'.jpg');
            $uploadPath['filename'] = $realFilename.'.jpg';
        } else {
            $imagick->setCompressionQuality(80);
            $imagick->writeImage($fullUploadPath);
        }

        return [
            'path'  =>  $uploadPath['filename'],
            'width'  =>  (int)$width,
            'height'  =>  (int)$newHeight,
        ];
    }

    private function isValidUploadMime(mixed $image)
    {
        $mime = mime_content_type($image);
        if (!in_array($mime, $this->allowedUploadMimes, true)) {
            return false;
        }

        return $mime;
    }

}
