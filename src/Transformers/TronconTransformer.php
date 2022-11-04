<?php

namespace Rockndonuts\Hackqc\Transformers;

use Rockndonuts\Hackqc\Models\Troncon;

class TronconTransformer
{

    public function transform(array $rawData): array
    {
        return [
            'id'               => $rawData['id'],
            'trc_id'           => $rawData['id_trc'],
            'length'           => $rawData['length'],
            'winter'           => $rawData['four_seasons'],
            'winter_protected' => $rawData['protected_four_seasons'],
            'updated_at'       => $rawData['updated_at'],
            'coords'           => json_decode($rawData['troncon_lines']),
            'side_one_state'   => 0,
            'side_two_state'   => 0,
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