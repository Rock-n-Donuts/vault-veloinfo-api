<?php

namespace Rockndonuts\Hackqc\Jobs;

if (!class_exists(ClassLoader::class)) {
    require __DIR__ . '/../../vendor/autoload.php';
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..'); // server, set file out of webroot
    $dotenv->load();
}

use Rockndonuts\Hackqc\Http\Client;
use Rockndonuts\Hackqc\Models\Borough;
use Rockndonuts\Hackqc\Models\Troncon;
use Composer\Autoload\ClassLoader;

class CyclableJob
{
    /** @todo make sure its not a changing url hash */
    public const DATA_ENDPOINT = "https://data.montreal.ca/dataset/5ea29f40-1b5b-4f34-85b3-7c67088ff536/resource/0dc6612a-be66-406b-b2d9-59c9e1c65ebf/download/";

    public const RESOURCE = "reseau_cyclable.geojson";

    /**
     * Imports data from reseau cycable
     * @return void
     * @throws \JsonException
     */
    public function getCyclableData(): array
    {
        $boroughs = (new Borough())->findAll();
        $boroughNames = array_column($boroughs, 'name');
        $keyedBoroughs = array_combine($boroughNames, $boroughs);

        $client = new Client(self::DATA_ENDPOINT);
        $content = $client->get(self::RESOURCE);

        $parsedContent = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $data = $parsedContent['features'];
        $filtered = [];

        $counter = 0;
        foreach ($data as $element) {

            if (!array_key_exists('ID_TRC', $element['properties']) || $element['properties']['ID_TRC'] == 0) {
                continue;
            }
            $filtered[] = $element['properties'];
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
            $tronconData = [
                'id2020'                 => $troncon['ID2020'],
                'id_trc'                 => $troncon['ID_TRC'],
                'type'                   => $troncon['TYPE_VOIE_R'],
                'type2'                  => $troncon['TYPE_VOIE2_R'],
                'length'                 => $troncon['LONGUEUR'],
                'id_cycl'                => $troncon['ID_CYCL'],
                'nb_lanes'               => $troncon['NBR_VOIE'],
                'splitter'               => !empty($troncon['SEPARATEUR_R']) ? $troncon['SEPARATEUR_R'] : null,
                'four_seasons'           => strtolower($troncon['SAISONS4_R']) === 'oui' ? 1 : 0,
                'protected_four_seasons' => strtolower($troncon['PROTEGE_4S_R']) === 'oui' ? 1 : 0,
                'borough_id'             => $keyedBoroughs[$troncon['NOM_ARR_VILLE_R']]['id'],
            ];
            $existing = $t->findBy(['id_trc' => $troncon['ID_TRC']]);
            if (empty($existing)) {
                $t->insert($tronconData);
            } else {
                $tId = $existing[0]['id'];
                $t->update($tId, $tronconData);
            }
        }

        return $filtered;
    }
}

try {
    (new CyclableJob())->getCyclableData();
} catch (\JsonException $e) {
}