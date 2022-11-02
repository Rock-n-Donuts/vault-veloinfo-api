<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Middleware\AuthMiddleware;
use Rockndonuts\Hackqc\Models\User;
use Rockndonuts\Hackqc\NonceProvider;

class UserController extends Controller
{
    public function createUser(): void
    {
        $postData = $this->getPostData();

        if (empty($postData) || empty($postData['uuid'])) {
            http_response_code(403);
            echo 'nooooo no no';die;
        }
        $uid = $postData['uuid'];

        $user = new User();
        $existing = $user->findBy([
            'user_id'   =>  $uid,
        ]);

        $token = (new NonceProvider())->getNT();

        if (empty($existing)) {
            $user->insert([
                'user_id'   =>  $uid,
                'token'     =>  $token,
            ]);
            $existing = $user->findBy([
                'user_id'   =>  $uid,
            ]);
            $existing = $existing[0];
        } else {

            if (!AuthMiddleware::getUser()) {
                $existing = $existing[0];
                $user->update($existing['id'], ['token'=>$token]);
            } else {
                $existing = $existing[0];
                $token = $existing['token'];
            }
        }

        header('Content-type: application/json');
        echo json_encode(['user_id'=>$existing['id'], 'token'=>$token]);
        exit;
    }
}