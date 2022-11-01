<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Models\User;
use Rockndonuts\Hackqc\NonceProvider;

class UserController
{
    public function createUser(): void
    {
        $postData = json_decode(file_get_contents("php://input"), true);

        if (empty($postData) || empty($postData['uuid'])) {
            http_response_code(403);
            echo 'nooooo no no';die;
        }
        $uid = $postData['uuid'];

        $user = new User();
        $existing = $user->findBy([
            'user_id'   =>  $uid,
        ]);


        if (empty($existing)) {
            $user->insert([
                'user_id'   =>  $uid,
            ]);
            $existing = $user->findBy([
                'user_id'   =>  $uid,
            ]);
            $existing = $existing[0];
        } else {
            $existing = $existing[0];
        }

        $token = (new NonceProvider())->getNT();
        header('Content-type: application/json');
        echo json_encode(['user_id'=>$existing['id'], 'token'=>$token]);
        exit;
    }
}