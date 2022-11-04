<?php

namespace Rockndonuts\Hackqc\Middleware;

use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Models\User;
use Rockndonuts\Hackqc\NonceProvider;
use RuntimeException;

class AuthMiddleware
{
    /**
     * Tries to get the user, throws an exception if not found
     * @return void
     */
    public static function auth(): void
    {
        $user = static::getUser();
        if (!$user) {
            throw new RuntimeException('token.invalid');
        }
    }

    /**
     * Retrieves the user, return false if user not found
     * @return false|mixed
     */
    public static function getUser(): mixed
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return false;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        $user = new User();

        try {
            $foundUser = $user->findOneBy(['token'=>$token]);
        } catch (\RuntimeException $e) {
            return false;
        }

        if (empty($foundUser)) {
            return false;
        }

        $np = new NonceProvider();
        if (!$np->verify($token)) {
            return false;
        }

        return $foundUser;
    }

    public static function unauthorized()
    {
        (new Response(['error'=>'token.invalid'], 401))->send();
        exit;
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function validateCaptcha(array $data): bool
    {
        if (empty($data['token'])) {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $_ENV['RECPATCHA_SECRET'],
            'response' => $data['token']
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $success = null;

        try {
            $success = json_decode($result, false, 512, JSON_THROW_ON_ERROR)->success;
        } catch (\JsonException $e) {
            $success = false;
        }

        return $success;
    }
}