<?php

namespace Rockndonuts\Hackqc\Middleware;

use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Models\User;
use Rockndonuts\Hackqc\NonceProvider;
use RuntimeException;

class AuthMiddleware
{
    public static function auth(): void
    {
        $user = static::getUser();
        if (!$user) {
            throw new RuntimeException('fdfds');
        }
    }

    public static function getUser()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return false;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        $user = new User();
        $existing = $user->findBy(['token'=>$token]);
        if (empty($existing)) {
            return false;
        }

        $u = $existing[0];

        $np = new NonceProvider();
        if (!$np->verify($token)) {
            return false;
        }

        return $u;
    }

    public static function unauthorized()
    {
        (new Response(['error'=>'token.invalid'], 401))->send();
        exit;
    }
}