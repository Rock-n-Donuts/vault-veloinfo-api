<?php

namespace Rockndonuts\Hackqc\Jobs;

use Composer\Autoload\ClassLoader;
use Rockndonuts\Hackqc\Models\Troncon;

if (!class_exists(ClassLoader::class)) {
    require __DIR__ . '/../../vendor/autoload.php';
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../'); // server, set file out of webroot
    $dotenv->load();
}

class InfoNeigeJob
{
    public function run()
    {
        $postdata = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:ser="https://servicesenlignedev.ville.montreal.qc.ca">
                <soapenv:Header/>
                <soapenv:Body>
                    <ser:GetPlanificationsForDate>
                        <getPlanificationsForDate>
                            <fromDate>2020-01-01T00:00:00</fromDate>
                            <tokenString>jn77-d6c589b8-6205-4eed-aa16-7cac618c3b4e-92f5f3c0-9ce4-4423-ad1f-e7b7ffc277b0-71107204-a9d8-4b63-819d-b8fcd5ceb4cd-110b632a-0781-4c3b-9d7f-90e033a24b84-2b310208-0063-472f-8ad6-563e6417dac1</tokenString>
                        </getPlanificationsForDate>
                    </ser:GetPlanificationsForDate>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        $opts = ['http'=>['method'=>'POST', 'header'=>'Content-type: text/xml', 'content'=>$postdata]];
        $context = stream_context_create($opts);

        $result = file_get_contents('https://servicesenligne2.ville.montreal.qc.ca/api/infoneige/sim/InfoneigeWebService?wsdl', false, $context);
        $clean = str_replace('S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"', 'envelope', $result);
        $clean = str_replace('S:Body', 'body', $clean);
        $clean = str_replace('ns0:GetPlanificationsForDateResponse xmlns:ns0="https://servicesenlignedev.ville.montreal.qc.ca"', 'getPlanification', $clean);
        $clean = str_replace('ns0:GetPlanificationsForDateResponse', 'getPlanification', $clean);
        $clean = str_replace('S:Envelope', 'envelope', $clean);
        $xml = simplexml_load_string($clean);
        $json = json_decode(json_encode($xml), true);

        $troncon = new Troncon();
        $ids = "SELECT id, id_trc, street_side_one_state, street_side_two_state FROM troncons";
        $data = $troncon->executeQuery($ids);
        $ids = array_column($data, 'id_trc');
        $keyedData = array_combine($ids, $data);
        $update = [];
        foreach ($json['body']['getPlanification']['planificationsForDateResponse']['planifications']['planification'] as $child) {
            $coteRue = substr($child['coteRueId'], -1, 1);
            $trId = rtrim($child['coteRueId'], '12');
            if (!array_key_exists((int)$trId, $keyedData)) {
                print_r($child);
                die;
                continue;
            }
            if (!array_key_exists($trId, $update)) {
                $update[$trId] = ['same_count'=>0, 'id'=>$keyedData[$trId]['id'], 'street_side_one_state' => $keyedData[$trId]['street_side_one_state'], 'street_side_two_state'=>$keyedData[$trId]['street_side_two_state']];
            }
            if ($coteRue === '1') {
                if ((int)$keyedData[$trId]['street_side_one_state'] !== (int)$child['etatDeneig']) {
                    $update[$trId]['same_count'] += 1;
                }
                $update[$trId]['street_side_one_state'] = (int)$child['etatDeneig'];
            } else if ($coteRue === '2') {
                if ((int)$keyedData[$trId]['street_side_two_state'] !== (int)$child['etatDeneig']) {
                    $update[$trId]['same_count'] += 1;
                }
                $update[$trId]['street_side_two_state'] = (int)$child['etatDeneig'];
            }
        }

        $updateQuery = "";
        $updateQueryTemplate = "UPDATE troncons SET `street_side_one_state` = %d, `street_side_two_state` = %d, `updated_at` = '%s' WHERE id = %d";

        $date = date('Y-m-d H:i:s');
        foreach ($update as $row) {
            if ($row['same_count'] === 2) {
                continue;
            }
            $updateQ = $updateQueryTemplate;
            $updateQ = sprintf($updateQ, (int)$row['street_side_one_state'], (int)$row['street_side_one_state'], $date, $row['id']);
            $updateQuery .= $updateQ.";";
        }

        $troncon->executeQuery($updateQuery);
    }
}

$infoNeige = new InfoNeigeJob();
$infoNeige->run();