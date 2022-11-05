<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Transformers;

use JsonException;
use Rockndonuts\Hackqc\Logger;

class TronconTransformer
{
    /**
     * @param array $rawData
     * @return ?array
     */
    public function transform(array $rawData): ?array
    {
        $coords = [];

        try {
            $coords = json_decode($rawData['troncon_lines'], false, 512, JSON_THROW_ON_ERROR);
        } catch(JsonException $e) {
            Logger::log($e->getMessage());
            return null;
        }

        return [
            'id'               => $rawData['id'],
            'trc_id'           => $rawData['id_trc'],
            'length'           => $rawData['length'],
            'winter'           => $rawData['four_seasons'],
            'winter_protected' => $rawData['protected_four_seasons'],
            'updated_at'       => $rawData['updated_at'],
            'coords'           => $coords,
            'side_one_state'   => 0,
            'side_two_state'   => 0,
        ];
    }

    /**
     * @param array $troncons
     * @return array
     */
    public function transformMany(array $troncons): array
    {
        $parsed = [];
        foreach ($troncons as $troncon) {
            $parsed[] = $this->transform($troncon);
        }

        return array_filter($parsed, static fn (?array $element) => !is_null($element));
    }
}