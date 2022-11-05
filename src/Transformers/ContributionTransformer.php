<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Transformers;

use Rockndonuts\Hackqc\FileHelper;
use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Models\ContributionVote;

class ContributionTransformer
{
    private ContributionReply $replies;
    private ContributionVote $votes;

    public function __construct()
    {
        $this->replies  = new ContributionReply();
        $this->votes    = new ContributionVote();
    }

    /**
     * @param array $contribution
     * @return array
     */
    public function transform(array $contribution): array
    {
        $contribution['replies'] = $this->replies->findBy(
            ['contribution_id' => $contribution['id']],
            ['id', 'user_id', 'name', 'message', 'created_at']
        );

        $contribution['coords'] = explode(",", $contribution['location']);
        unset($contribution['location']);

        $contribution['score'] = $this->votes->getScore($contribution['id']);

        foreach ($contribution['coords'] as &$coord) {
            $coord = (float)$coord;
        }
        unset($coord);

        $lastUpdated = $contribution['created_at'];

        if (!empty($contribution['replies'])) {
            $updated = array_column($contribution['replies'], 'created_at');
            if (!is_null($contribution['score']['last_vote_date'])) {
                $updated[] = $contribution['score']['last_vote_date'];
            }
            sort($updated);
            $lastUpdated = end($updated);
        } else if (!is_null($contribution['score']['last_vote_date'])) {
            $lastUpdated = $contribution['score']['last_vote_date'];
        }

        $contribution['updated_at'] = $lastUpdated;
        if (!empty($contribution['photo_path'])) {
            $contribution['photo_path'] = FileHelper::getUploadUrl($contribution['photo_path']);
        }

        return $contribution;
    }

    /**
     * @param array $contributions
     * @return array
     */
    public function transformMany(array $contributions): array
    {
        $parsed = [];
        foreach ($contributions as $contribution) {
            $parsed[] = $this->transform($contribution);
        }

        return $parsed;
    }
}
