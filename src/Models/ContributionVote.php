<?php

namespace Rockndonuts\Hackqc\Models;

class ContributionVote extends DB
{
    public const TABLE_NAME = "contribution_votes";

    public function getScore(int $contribId): array
    {
        $query = <<<SQL
            SELECT (SELECT count(score)
                FROM contribution_votes
            WHERE score = 1 GROUP BY score) as positive,
            (SELECT count(score)
                FROM contribution_votes
            WHERE score = -1 GROUP BY score) as negative
            FROM contribution_votes LIMIT 1;
        SQL;

        return $this->executeQuery($query);

    }
}