<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Http\Response;
use Rockndonuts\Hackqc\Models\Borough;
use Rockndonuts\Hackqc\Models\Contribution;
use Rockndonuts\Hackqc\Models\Troncon;
use Rockndonuts\Hackqc\Transformers\ContributionTransformer;
use Rockndonuts\Hackqc\Transformers\TronconTransformer;
use function getSeason;

class APIController extends Controller
{
    /**
     * Returns update data
     * @return void
     * @throws \JsonException
     */
    public function updateData(): void
    {
        $data = $_GET;

        $contribution = new Contribution();

        $date = new \DateTime();
        if (isset($data['from'])) {
            $date = $date->setTimestamp((int)$data['from']);
            $data['from'] = $date->format('Y-m-d H:i:s');
        }

        if (isset($data['from'])) {
            $contributions = $contribution->findUpdatedSince($data['from']);
        } else {
            // If no date, select eeeeevvveerrryyyttthhhiiiinnnngg
            $contributions = $contribution->findAll();
        }

        // Parse pour output
        $contribTransformer = new ContributionTransformer();
        $contributions = $contribTransformer->transformMany($contributions);

        $troncon = new Troncon();
        if (isset($data['from'])) {
            $troncons = $troncon->findBy(['updated_at' => $data['from']]);
        } else {
            // If no date, select eeeeevvveerrryyyttthhhiiiinnnngg
            $troncons = $troncon->findAll();
        }

        // Parse pour output
        $tronconTransformer = new TronconTransformer();
        $troncons = $tronconTransformer->transformMany($troncons);

        (new Response(['contributions' => $contributions, 'troncons'=>$troncons, 'date' => time()], 200))->send();
    }
}
