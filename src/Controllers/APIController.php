<?php

namespace Rockndonuts\Hackqc\Controllers;

use Rockndonuts\Hackqc\Models\Borough;
use Rockndonuts\Hackqc\Models\Troncon;
use Rockndonuts\Hackqc\Transformers\TronconTransformer;
use function getSeason;

class APIController
{

    public function getTroncons(): void
    {
        $troncons = (new Troncon())->findAllWithBoroughs(['id', 'id_trc', 'borough_id']);

        $toSend = (new TronconTransformer())->transformMany($troncons);
        header('Content-type: application/json');
        echo json_encode($toSend);
        die;
    }

    public function getCyclableData()
    {
        $boroughs = (new Borough())->findAll();
        $boroughNames = array_column($boroughs, 'name');
        $keyedBoroughs = array_combine($boroughNames, $boroughs);

        $content = file_get_contents(APP_PATH . '/data/reseau_cyclable.geojson');
        $parsedContent = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $data = $parsedContent['features'];
        $filtered = [];

        $counter = 0;
        if (getSeason() === "winter") {
            foreach ($data as $element) {

                if (!array_key_exists('ID_TRC', $element['properties']) || $element['properties']['ID_TRC'] == 0) {
                    continue;
                }
                $filtered[] = $element['properties'];
            }
        }

        foreach ($filtered as $troncon) {
            // Pas Montréal, dont care
            if (strtolower($troncon['VILLE_MTL_R']) !== 'oui') {
                continue;
            }

            if ($troncon['NOM_ARR_VILLE_R'] === "") {
                continue;
            }

            $t = new Troncon();
            $toInsert = [
                'id2020'                 =>  $troncon['ID2020'],
                'id_trc'                 =>  $troncon['ID_TRC'],
                'type'                   =>  $troncon['TYPE_VOIE_R'],
                'type2'                  =>  $troncon['TYPE_VOIE2_R'],
                'length'                 =>  $troncon['LONGUEUR'],
                'id_cycl'                =>  $troncon['ID_CYCL'],
                'nb_lanes'               =>  $troncon['NBR_VOIE'],
                'splitter'              =>  !empty($troncon['SEPARATEUR_R']) ? $troncon['SEPARATEUR_R'] : null,
                'four_seasons'           =>  strtolower($troncon['SAISONS4_R']) === 'oui' ? 1 : 0,
                'protected_four_seasons' =>  strtolower($troncon['PROTEGE_4S_R']) === 'oui' ? 1 : 0,
                'borough_id'             =>  $keyedBoroughs[$troncon['NOM_ARR_VILLE_R']]['id'],
            ];

            $t->insert($toInsert);
        }
        return $filtered;
    }
}