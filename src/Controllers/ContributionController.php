<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\FileHelper;
use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Middleware\AuthMiddleware;
use Rockndonuts\Hackqc\Models\Contribution;
use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Models\ContributionVote;
use Rockndonuts\Hackqc\Transformers\ContributionTransformer;

class ContributionController extends Controller
{
    public function get(int $id = null): void
    {
        $user = AuthMiddleware::getUser();

        $contributionId = $id;
        $contribution = new Contribution();
        $existing = $contribution->findBy(['id' => $contributionId]);

        if (empty($existing)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $contrib = $existing[0];
        $responseData = ['can_vote' => true];
        if ($user['user_id'] === $contrib['user_id']) {
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
        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $contribution = new Contribution();

        $data = $_POST;
//        $data = $this->getPostData();

        if (is_array($data['coords'])) {
            $location = implode(",", $data['coords']);
        } else {
            $location = $data['coords'];
        }

        $createdAt = new \DateTime();
        $comment = $data['comment'];

        $issueId = $data['issue_id'];
        $name = $data['name'];
        $quality = $data['quality'] ?? null;

        $fileHelper = new FileHelper();
        $path = $fileHelper->upload($_FILES['photo']);

        $contribId = $contribution->insert([
            'location'   => $location,
            'comment'    => $comment,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'issue_id'   => $issueId,
            'user_id'    => $userId,
            'name'       => $name,
            'photo_path' => $path,
            'quality'    => $quality,
        ]);

        $contrib = $contribution->findBy(['id' => $contribId]);
        if (empty($contrib)) {
            // wat
        }

        $contrib = $contrib[0];

        $contribTransformer = new ContributionTransformer();
        $contrib = $contribTransformer->transform($contrib);
        /**
         * @todo send contribution
         */
        (new Response(['success' => true, 'contribution' => $contrib], 200))->send();
    }

    public function vote(int $id = null): void
    {
        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $contribution = new Contribution();
        $existing = $contribution->findBy(['id' => $id]);

        if (empty($existing)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $data = $this->getPostData();
        $contrib = $existing[0];

        $vote = new ContributionVote();
        $alreadyVoted = $vote->findBy(['user_id' => $userId]);

        if (!empty($alreadyVoted)) {
            (new Response(['error' => 'already_voted'], 401))->send();
            exit;
        }

        $vote->insert([
            'user_id'         => $userId,
            'contribution_id' => $id,
            'score'           => $data['score'],
        ]);

        (new Response(['success' => true], 200))->send();
    }

    public function reply(int $id = null): void
    {
        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $data = $this->getPostData();
        $contribution = new Contribution();
        $existing = $contribution->findBy(['id' => $id]);

        if (empty($existing)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $contrib = $existing[0];

        $d = new \DateTime();

        $reply = new ContributionReply();
        $replyId = $reply->insert([
            'user_id'         => $user['id'],
            'contribution_id' => $contrib['id'],
            'message'         => $data['message'],
            'created_at'      => $d->format('Y-m-d H:i:s')
        ]);
        $createdReply = $reply->findBy(['id' => $replyId]);

        (new Response(['reply' => $createdReply[0]], 200))->send();
    }
}