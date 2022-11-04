<?php

namespace Rockndonuts\Hackqc\Controllers;

class Controller
{
    /**
     * Retrieves data from the request
     * @return array
     */
    public function getPostData(): array
    {
        $data = file_get_contents("php://input");

        $headers = getallheaders();
        if (!empty($headers['Content-type']) && $headers['Content-type'] === 'application/json') {
            try {
                $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $data = [];
            }
        } else if (empty($data)) {
            $data = match (strtolower($_SERVER['REQUEST_METHOD'])) {
                "post"  =>  $_POST,
                default => $_GET
            };
        }

        return $data;
    }
}