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
    function XMLToArrayFlat($xml, &$return, $path = '', $root = false)
    {
        $children = array();
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            if ($root) { // we're at root
                $path .= '/' . $xml->getName();
            }
        }
        if (count($children) == 0) {
            $return[$path] = (string)$xml;
            return;
        }
        $seen = array();
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement) ? $child->getName() : $child;
            if (!isset($seen[$childname])) {
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path . '/' . $child . '[' . $seen[$childname] . ']');
        }
    }

    public function validateGeobase()
    {

        $soapClient = new \SoapClient("https://servicesenligne2.ville.montreal.qc.ca/api/infoneige/sim/InfoneigeWebService?wsdl");

        print_r($soapClient->__soapCall('GetPlanificationInfosForDate', [
                'getPlanificationsForDate' =>
                    [
                        'fromDate'    => '2022-10-31T15:00:00',
                    ]
            ]
        ));
        die;
        die;
//        $d = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
//        $allIds = "SELECT id_trc FROM troncons";
//        $ids = (DB()->executeQuery($allIds));
//        $ids = array_column($ids, 'id_trc');
//
//        foreach ($d['features'] as $dd) {
//            if (in_array($dd['properties']['ID_TRC'], $ids)) {
//                if (str_ends_with($dd['properties']['COTE_RUE_ID'], '1') === true) {
//                    $streetId = $dd['properties']['COTE_RUE_ID'];
//
//                    $query = "UPDATE troncons SET street_side_one =  ";
//
//                }
//            }
//        }
    }

    /**
     * Returns update data
     * @return void
     * @throws \JsonException
     */
    public function updateData(): void
    {
        $data = $_GET;

        $contribution = new Contribution();

        if (isset($data['from'])) {
            $contributions = $contribution->findBy(['created_at' => $data['from']]);
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

        (new Response(['contributions' => $contributions, 'troncons' => $troncons], 200))->send();
    }

    public function getTroncons(): void
    {
        $troncons = (new Troncon())->findAllWithBoroughs(['id', 'id_trc', 'borough_id']);

        $toSend = (new TronconTransformer())->transformMany($troncons);

        (new Response($toSend, 200))->send();
    }
}