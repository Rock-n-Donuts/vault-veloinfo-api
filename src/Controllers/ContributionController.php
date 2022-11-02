<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Models\Contribution;

class ContributionController
{
    public function get(): void
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $contribution = new Contribution();
        $existing = $contribution->findBy(['id' => $data['contribution_id']]);

        if (empty($existing)) {
            (new Response(['error'=>'contribution.not_exists'], 500))->send();
        }

        $contrib = $existing[0];
        $responseData = ['can_vote' =>  true];
        if ($data['user_id'] === $contrib['user_id']) {
            $responseData['can_vote'] = false;
        }

        (new Response($responseData, 200))->send();
    }

    /**
     * @throws \JsonException
     * @todo sanitize
     */
    public function createContribution(): void
    {
        $contribution = new Contribution();

        $data = file_get_contents("php://input");
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $location = implode(",", $data['coords']);

        $createdAt = new \DateTime();
        $comment = $data['comment'];

        $issueId = $data['issue_id'];
        $userId = $data['user_id'];
        $name = $data['name'];

        $contribution->insert([
            'location'   => $location,
            'comment'    => $comment,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'issue_id'   => $issueId,
            'user_id'    => $userId,
            'name'       => $name,
        ]);

        (new Response(['success'=>true], 200))->send();
    }

    public function vote(): void
    {
        $contribution = new Contribution();

        $data = file_get_contents("php://input");
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $contribution = new Contribution();
        $existing = $contribution->findBy(['id' => $data['contribution_id']]);

        if (empty($existing)) {
            (new Response(['error'=>'contribution.not_exists'], 500))->send();
        }

        $contrib = $existing[0];
    }
}