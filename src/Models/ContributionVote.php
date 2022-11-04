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
            WHERE score = 1 AND contribution_id = $contribId GROUP BY score ) as positive,
            (SELECT count(score)
                FROM contribution_votes
            WHERE score = -1 AND contribution_id = $contribId GROUP BY score) as negative
            FROM contribution_votes 
                WHERE contribution_id = $contribId
                LIMIT 1;
        SQL;

        return $this->executeQuery($query);

    }

    public function findLast(int $contributionId)
    {
        $query = <<<SQL
            SELECT score, created_at FROM contribution_votes WHERE contribution_id = $contributionId ORDER BY id DESC LIMIT 1
        SQL;

        return $this->executeQuery($query);
    }
}