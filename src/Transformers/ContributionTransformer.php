<?php

namespace Rockndonuts\Hackqc\Transformers;

use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Models\ContributionVote;

class ContributionTransformer
{
    private ContributionReply $replies;
    private ContributionVote $votes;

    public function __construct()
    {
        $this->replies = new ContributionReply();
        $this->votes = new ContributionVote();
    }

    public function transform(array $contribution): array
    {
        $replies = $this->replies->findBy(['contribution_id' => $contribution['id']]);
        $score = $this->votes->getScore($contribution['id']);
        
        if (!empty($score)) {
            if (array_key_exists('positive', $score)) {
                if (is_null($score['positive'])) {
                    $score['positive'] = 0;
                }
                if (is_null($score['negative'])) {
                    $score['negative'] = 0;
                }
            } else {
                $score = $score[0];
                if (is_null($score['positive'])) {
                    $score['positive'] = 0;
                }
                if (is_null($score['negative'])) {
                    $score['negative'] = 0;
                }
            }
        } else {
            $score = ['positive' => 0, 'negative' => 0];
        }
        
        foreach ($replies as &$reply) {
            unset($reply['contribution_id']);
        }
        unset($reply);
        $contribution['replies'] = $replies;

        $contribution['coords'] = explode(",", $contribution['location']);
        $contribution['score'] = $score;
        $lastVote = $this->votes->findLast($contribution['id']);
        $lastVoteDate = null;
        if (!empty($lastVote)) {
            $contribution['score']['last_vote'] = $lastVote[0]['score'];
            $contribution['score']['last_vote_date'] = $lastVote[0]['created_at'];
            $lastVoteDate = $lastVote[0]['created_at'];
        } else {
            $contribution['score']['last_vote'] = null;
            $contribution['score']['last_vote_date'] = null;
        }
        foreach ($contribution['coords'] as &$coord) {
            $coord = (float)$coord;
        }
        unset($coord);

        $lastUpdated = $contribution['created_at'];
        if (!empty($replies)) {
            $updated = array_column($replies, 'created_at');
            if (!is_null($lastVoteDate)) {
                $updated[] = $lastVoteDate;
            }
            sort($updated);
            $lastUpdated = end($updated);
        } else if ($lastVoteDate) {
            $lastUpdated = $lastVoteDate;
        }

        $contribution['updated_at'] = $lastUpdated;
        if (!empty($contribution['photo_path'])) {
            $contribution['photo_path'] = "https://hackqc.parasitegames.net/uploads/" . $contribution['photo_path'];
        }
        unset($contribution['location']);


        return $contribution;
    }

    public function transformMany(array $contributions): array
    {
        $parsed = [];
        foreach ($contributions as $contribution) {
            $parsed[] = $this->transform($contribution);
        }

        return $parsed;
    }
}
