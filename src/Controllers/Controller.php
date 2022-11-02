<?php

namespace Rockndonuts\Hackqc\Controllers;

class Controller
{
    public function getPostData(): array
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}