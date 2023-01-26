<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Controllers;

use DateTime;
use JsonException;
use Rockndonuts\Hackqc\FileHelper;
use Rockndonuts\Hackqc\PolygonHelper;
use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Managers\MailManager;
use Rockndonuts\Hackqc\Middleware\AuthMiddleware;
use Rockndonuts\Hackqc\Models\Contribution;
use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Models\ContributionVote;
use Rockndonuts\Hackqc\Transformers\ContributionTransformer;

class ContributionController extends Controller
{
    /**
     * Determines whether the user can vote or not
     * @param int|null $id
     * @return void
     * @throws JsonException
     */
    public function getUserVoteStatus(int $id = null): void
    {
        $user = AuthMiddleware::getUser();

        // Failsafe in case for some reason it gets here, should not because route is protected
        if (!$user) {

            $contributionId = $id;
            $contribution = new Contribution();
            $existing = $contribution->findBy(['id' => $contributionId]);

            if (empty($existing)) {
                (new Response(['error' => 'contribution.not_exists'], 500))->send();
            }

            $contrib = $existing[0];

            $contribTransformer = new ContributionTransformer();
            $contrib = $contribTransformer->transform($contrib);
            (new Response(['success' => true, 'contribution' => $contrib], 200))->send();
            exit;
        }

        $userId = (int)$user['id'];

        $contribution = new Contribution();
        $contrib = $contribution->findOneBy(['id' => $id]);

        if (empty($contrib)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $responseData = ['can_vote' => true];
        if ($userId === $contrib['user_id']) {
            $responseData['can_vote'] = false;
        }

        $vote = new ContributionVote();
        $alreadyVoted = $vote->findBy(['contribution_id' => $id]);

        $users = array_column($alreadyVoted, 'user_id');
        if (in_array($userId, $users, true)) {
            $responseData['can_vote'] = false;
        }

        (new Response($responseData, 200))->send();
        exit;
    }

    /**
     * @throws JsonException
     * @todo sanitize
     */
    public function createContribution(): void
    {
        $data = $_POST;
        if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== "local") {
            $captcha = AuthMiddleware::validateCaptcha($data);
            if (!$captcha) {
                (new Response(['success' => false, 'error' => "a cap-chat"], 403))->send();
                exit;
            }
        }

        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $contribution = new Contribution();
        $data = $this->getRequestData();

        /**
         * @todo implement request & validation
         */
//        $contribRequest = new ContributionRequest($data);
//        if (!$contribRequest->isValid()) {
//
//        }
        if (!empty($data['coords']) && is_array($data['coords'])) {
            $location = implode(",", $data['coords']);
        } else {
            $location = $data['coords'];
        }

        $createdAt = new DateTime();

        $comment = $data['comment'];

        $issueId = $data['issue_id'];
        $name = null;
        if (!empty($data['name'])) {
            $name = $data['name'];
        }
        $quality = $data['quality'] ?? null;

        $fileInfo = ['path'=>null, 'width'=>null, 'height'=>null];
        if (!empty($_FILES['photo'])) {
            $fileHelper = new FileHelper();
            $fileInfo = $fileHelper->resizeAndUpload($_FILES['photo']);
        }

        $polyHelper = new PolygonHelper;
        $boroughName = $polyHelper->getBoroughNameFromLocation($location);

        $contribId = $contribution->insert([
            'location'   => $location,
            'comment'    => $comment,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'issue_id'   => $issueId,
            'user_id'    => $userId,
            'name'       => $name,
            'photo_path' => $fileInfo['path'],
            'photo_width'      => $fileInfo['width'],
            'photo_height'     => $fileInfo['height'],
            'quality'    => $quality,
            'borough_name'    => $boroughName,
        ]);

        $ogContrib = $contribution->findOneBy(['id' => $contribId]);
        if (empty($ogContrib)) {
            (new Response(['error' => 'contrib.not_exists'], 404))->send();
            exit;
        }

        $contribTransformer = new ContributionTransformer();
        $contrib = $contribTransformer->transform($ogContrib);

        try {
            if ($issueId === 1) {
                $manager = new MailManager();
                $manager->contributionNotification($ogContrib);
            }

        } catch (\Exception $e) {
            // silence
        }

        (new Response(['success' => true, 'contribution' => $contrib], 200))->send();
    }

    /**
     * @param int|null $id
     * @return void
     * @throws JsonException
     */
    public function vote(int $id = null): void
    {
        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $contribution = new Contribution();
        $contrib = $contribution->findOneBy(['id' => $id]);

        if (empty($contrib)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $data = $this->getRequestData();

        $vote = new ContributionVote();
        $alreadyVoted = $vote->findBy(['contribution_id' => $id]);

        $users = array_column($alreadyVoted, 'user_id');
        if (in_array($userId, $users, true)) {
            (new Response(['error' => 'already_voted'], 403))->send();
            exit;
        }

        $vote->insert([
            'user_id'         => $userId,
            'contribution_id' => $id,
            'score'           => $data['score'],
        ]);

        $contrib = $contribution->findOneBy(['id' => $id]);
        $transformer = new ContributionTransformer();
        $contrib = $transformer->transform($contrib);

        (new Response(['success' => true, 'contribution' => $contrib], 200))->send();
    }

    /**
     * @param int|null $id
     * @return void
     * @throws JsonException
     */
    public function reply(int $id = null): void
    {
        $data = $this->getRequestData();
        if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== "local") {
            $captcha = AuthMiddleware::validateCaptcha($data);
            if (!$captcha) {
                (new Response(['success' => false, 'error' => "a cap-chat"], 403))->send();
                exit;
            }
        }

        $user = AuthMiddleware::getUser();

        if (!$user) {
            AuthMiddleware::unauthorized();
            exit;
        }

        $userId = (int)$user['id'];

        $data = $this->getRequestData();
        $contribution = new Contribution();
        $contrib = $contribution->findOneBy(['id' => $id]);

        if (empty($contrib)) {
            (new Response(['error' => 'contribution.not_exists'], 500))->send();
        }

        $d = new DateTime();
        $name = null;
        if (!empty($data['name'])) {
            $name = $data['name'];
        }
        $reply = new ContributionReply();
        $replyId = $reply->insert([
            'user_id'         => $userId,
            'contribution_id' => $contrib['id'],
            'message'         => $data['comment'],
            'created_at'      => $d->format('Y-m-d H:i:s'),
            'name'            => $name,
        ]);
        $createdReply = $reply->findBy(['id' => $replyId]);

        $contrib = $contribution->findOneBy(['id' => $id]);
        $transformer = new ContributionTransformer();
        $contrib = $transformer->transform($contrib);

        (new Response(['success' => true, 'reply' => $createdReply[0], 'contribution' => $contrib], 200))->send();
    }

    public function import()
    {
        $data = $this->getRequestData();

        if ($data['key'] !== $_ENV['IMPORT_KEY']) {
            (new Response(['error' => 'pas le droit'], 403))->send();
            exit;
        }
        $toImport = $data['contributions'];

        $contrib = new Contribution();
        foreach ($toImport as $import) {
            $contribution = null;

            $externalId = null;
            if (isset($import['external_id'])) {
                $contribution = $contrib->findOneBy(['external_id' => $import['external_id']]);
                $externalId = $import['external_id'];
            }

            $isExternal = 0;
            $externalImage = null;

            if (isset($import['is_external'])) {
                $isExternal = 1;
                $externalImage = $import['external_photo'];
            }

            if (!$contribution) {
                if (!empty($import['coords']) && is_array($import['coords'])) {
                    $location = implode(",", $import['coords']);
                } else {
                    $location = $import['coords'];
                }

                $createdAt = new DateTime();
                if (!empty($import['created_at'])) {
                    $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $import['created_at']);
                }
                $comment = $import['comment'];

                $issueId = $import['issue_id'];
                $name = null;
                if (!empty($import['name'])) {
                    $name = $import['name'];
                }
                $quality = $import['quality'] ?? null;

                $contrib->insert([
                    'location'       => $location,
                    'comment'        => $comment,
                    'created_at'     => $createdAt->format('Y-m-d H:i:s'),
                    'issue_id'       => $issueId,
                    'user_id'        => 0,
                    'name'           => $name,
                    'photo_path'     => null,
                    'quality'        => $quality,
                    'external_id'    => $externalId,
                    'is_external'    => $isExternal,
                    'external_photo' => $externalImage,
                    'is_photo_external'=>   !empty($import['external_photo']) ? 1 : 0,
                    'photo_height'   =>  $import['height'],
                    'photo_width'    =>  $import['width'],
                ]);
            } else {
                $toUpdate = [];
                if (!empty($import['coords']) && is_array($import['coords'])) {
                    $location = implode(",", $import['coords']);
                    $toUpdate['location'] = $location;
                }

                if (!empty($import['comment'])) {
                    $toUpdate['comment'] = $import['comment'];
                }

                if (!empty($import['issue_id'])) {
                    $toUpdate['issue_id'] = $import['issue_id'];
                }

                if (!empty($import['name'])) {
                    $toUpdate['name'] = $import['name'];
                }
                if (!empty($quality)) {
                    $toUpdate['quality'] = $import['quality'];
                }

                $externalImage = null;

                if (isset($import['is_external'])) {
                    $toUpdate['is_external'] = true;
                    $toUpdate['external_photo'] = $import['external_photo'];
                }
                $contrib->update($contribution['id'], $toUpdate);
            }
        }

        (new Response(['success' => 'ben oui toi'], 200))->send();
    }
}