<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Controllers;

use DateTime;
use DateTimeZone;
use Exception;
use JsonException;
use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Models\Borough;
use Rockndonuts\Hackqc\Models\Contribution;
use Rockndonuts\Hackqc\Models\Troncon;
use Rockndonuts\Hackqc\Transformers\ContributionTransformer;
use Rockndonuts\Hackqc\Transformers\TronconTransformer;

class APIController extends Controller
{
    /**
     * Returns update data
     * @return void
     * @throws JsonException
     */
    public function updateData(): void
    {
        $data = $this->getRequestData();

        $contribution = new Contribution();
        $troncon = new Troncon();

        if (isset($data['from'])) {
            $timezone = new DateTimeZone('America/New_York');

            try {
                $date = new DateTime('@' . (int)$data['from'], $timezone);
            } catch (Exception $e) {
                $date = new DateTime('now', $timezone);
            }
            $data['from'] = $date->format('Y-m-d H:i:s');

            $contributions = $contribution->findUpdatedSince($data['from']);
            $troncons = $troncon->findBy(['updated_at' => $data['from']]);
        } else {
            $contributions = $contribution->findAll();
            $troncons = $troncon->findAll();
        }

        // Parse pour output
        $contribTransformer = new ContributionTransformer();
        $contributions = $contribTransformer->transformMany($contributions);

        // Parse pour output
        $tronconTransformer = new TronconTransformer();
        $troncons = $tronconTransformer->transformMany($troncons);

        (new Response(['contributions' => $contributions, 'troncons'=>$troncons, 'date' => time()], 200))->send();
    }
}
