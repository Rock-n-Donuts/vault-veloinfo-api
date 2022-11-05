<?php

namespace Rockndonuts\Hackqc\Models;

class ContributionVote extends DB
{
    public const TABLE_NAME = "contribution_votes";

    /**
     * Calculates the score of a given contribution
     * @param int $contributionId
     * @return array
     */
    public function getScore(int $contributionId): array
    {
        $score = ['positive' => 0, 'negative' => 0];

        $query = <<<SQL
            SELECT (SELECT count(score)
                FROM contribution_votes
            WHERE score = 1 AND contribution_id = $contributionId GROUP BY score ) as positive,
            (SELECT count(score)
                FROM contribution_votes
            WHERE score = -1 AND contribution_id = $contributionId GROUP BY score) as negative
            FROM contribution_votes 
                WHERE contribution_id = $contributionId
                LIMIT 1;
        SQL;

        $results = $this->executeQuery($query);
        if (!empty($results)) {
            $score = $results[0];
            if (is_null($score['positive'])) {
                $score['positive'] = 0;
            }
            if (is_null($score['negative'])) {
                $score['negative'] = 0;
            }
        }

        $lastVote = $this->findLast($contributionId);
        if (!empty($lastVote)) {
            $score['last_vote'] = $lastVote[0]['score'];
            $score['last_vote_date'] = $lastVote[0]['created_at'];
            $lastVoteDate = $lastVote[0]['created_at'];
        } else {
            $score['last_vote'] = null;
            $score['last_vote_date'] = null;
        }

        return $score;
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