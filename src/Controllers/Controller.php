<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Controllers;

use JsonException;

class Controller
{
    /**
     * Retrieves data from the request
     * @return array
     */
    public function getRequestData(): array
    {
        $data = file_get_contents("php://input");

        if (!empty($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {

            try {
                $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $data = [];
            }
        } else {
            $data = match (strtolower($_SERVER['REQUEST_METHOD'])) {
                "post"  =>  $_POST,
                default => $_GET
            };
        }

        return $data;
    }
}