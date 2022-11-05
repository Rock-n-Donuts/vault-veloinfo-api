<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Middleware\AuthMiddleware;
use Rockndonuts\Hackqc\Models\User;
use Rockndonuts\Hackqc\NonceProvider;

class UserController extends Controller
{
    public function createUser(): void
    {
        $postData = $this->getRequestData();

        if (empty($postData) || empty($postData['uuid'])) {
            (new Response(['error'=>'no_user'], 403))->send();
            exit;
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
            $existing = $existing[0];
            if (!AuthMiddleware::getUser()) {
                $user->update($existing['id'], ['token'=>$token]);
            } else {
                $token = $existing['token'];
            }
        }

        (new Response(['user_id'=>$existing['id'], 'token'=>$token], 200))->send();
        exit;
    }
}