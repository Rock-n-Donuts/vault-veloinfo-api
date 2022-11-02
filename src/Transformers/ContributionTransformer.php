<?php

namespace Rockndonuts\Hackqc\Transformers;

use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Models\ContributionVote;

class ContributionTransformer
{
    private ContributionReply $replies;

    public function __construct()
    {
        $this->replies = new ContributionReply();
        $this->votes = new ContributionVote();
    }

    public function transform(array $contribution): array
    {
        $replies = $this->replies->findBy(['contribution_id'=>$contribution['id']]);
        $score = $this->votes->getScore($contribution['id']);

        foreach ($replies as &$reply) {
            unset($reply['contribution_id']);
        }
        unset($reply);
        $contribution['replies'] = $replies;
        $lastUpdated = $contribution['created_at'];
        if (!empty($replies)) {
            $updated = array_column($replies, 'created_at');
            sort($updated);
            $lastUpdated = end($updated);
        }

        $contribution['updated_at'] = $lastUpdated;

        $contribution['coords'] = explode(",", $contribution['location']);
        $contribution['score'] = $score;

        foreach ($contribution['coords'] as &$coord) {
            $coord = (float)$coord;
        }
        unset($coord);

        $contribution['photo_path'] = "htps://hackqc.parasitegames.net/uploads/". $contribution['photo_path'];
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