<?php

namespace Rockndonuts\Hackqc\Models;

class Contribution extends DB
{
    public const TABLE_NAME = "contributions";

    public function findUpdatedSince(mixed $from)
    {
        $table = self::TABLE_NAME;
        $query = <<<SQL
            SELECT c.id FROM $table c
            LEFT JOIN contribution_replies cr ON cr.contribution_id = c.id
            LEFT JOIN contribution_votes cv ON cv.contribution_id = c.id
            WHERE c.created_at >= '$from' OR  cr.created_at >= '$from' OR cv.created_at >= '$from'
        SQL;

        $idsResults = $this->executeQuery($query);
        if (empty($idsResults)) {
            return [];
        }
        $ids = array_column($idsResults, 'id');
        $ids = array_unique($ids);
        $idString = implode(",", $ids);
        $idString = rtrim($idString, ',');
        $fetchQuery = <<<SQL
            SELECT * FROM $table WHERE id IN ($idString)
        SQL;

        return $this->executeQuery($fetchQuery);
    }

}