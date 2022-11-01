<?php

namespace Rockndonuts\Hackqc\Transformers;

use Rockndonuts\Hackqc\Models\Troncon;

class TronconTransformer
{
    public function transform(array $rawData): array
    {
        return [
            'id'            =>  $rawData['id_trc'],
            'city_state'    =>  0, //$rawData['state'],
            'users_state'   =>  0, //$this->getUserStateFromTronconId($rawData['id']),
            'contributions' =>  [], //$this->getCommentsFromTronconId($rawData['id']),
        ];
    }

    public function transformMany(array $troncons)
    {
        $parsed = [];
        foreach ($troncons as $troncon) {
            $parsed[] = $this->transform($troncon);
        }

        return $parsed;
    }
    private function getUserStateFromTronconId(int $id): array
    {
    }

    private function getCommentsFromTronconId(int $id): array
    {
        $comments = Troncon::getComments($id);

        return (new CommentTransformer())->transformMany($comments);
    }
}