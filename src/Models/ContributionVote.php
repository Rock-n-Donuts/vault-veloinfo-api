<?php

namespace Rockndonuts\Hackqc\Models;

class ContributionVote extends DB
{
    public const TABLE_NAME = "contribution_votes";

    /**
     * Calculates the score of a given contribution
     * @param int $contribId
     * @return array
     */
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

    /**
     * Finds the latest vote for a given contribution
     * @param int $contributionId
     * @return bool|array
     */
    public function findLast(int $contributionId): bool|array
    {
        $query = <<<SQL
            SELECT score, created_at FROM contribution_votes WHERE contribution_id = $contributionId ORDER BY id DESC LIMIT 1
        SQL;

        return $this->executeQuery($query);
    }
}