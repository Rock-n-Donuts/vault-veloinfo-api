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
    /**
     * @throws \JsonException
     *
     * @todo use real SoapClient to parse data and not str_replace
     */
    public function run()
    {
        $token = $_ENV['INFO_NEIGE_TOKEN'];
        $postdata = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:ser="https://servicesenlignedev.ville.montreal.qc.ca">
                <soapenv:Header/>
                <soapenv:Body>
                    <ser:GetPlanificationsForDate>
                        <getPlanificationsForDate>
                            <fromDate>2020-01-01T00:00:00</fromDate>
                            <tokenString>$token</tokenString>
                        </getPlanificationsForDate>
                    </ser:GetPlanificationsForDate>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://servicesenligne2.ville.montreal.qc.ca/api/infoneige/sim/InfoneigeWebService?wsdl");
// Following line is compulsary to add as it is:
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: text/xml',
        ));
        $data = curl_exec($ch);
        curl_close($ch);
//
        print_r($data);die;
//        $opts = ['http' => ['method' => 'POST', 'header' => 'Content-type: text/xml', 'content' => $postdata]];
//        $context = stream_context_create($opts);

//        $result = file_get_contents('https://servicesenligne2.ville.montreal.qc.ca/api/infoneige/sim/InfoneigeWebService?wsdl', false, $context);
        $troncon = new Troncon();
        $ids = "SELECT id, id_trc, street_side_one_state, street_side_two_state FROM troncons";
        $data = $troncon->executeQuery($ids);
        $ids = array_column($data, 'id_trc');
        $keyedData = array_combine($ids, $data);
        $update = [];

//        $result = file_get_contents(__DIR__.'/../../data/t.xml');
        $xml = simplexml_load_string($data);
        foreach ($xml->children("http://schemas.xmlsoap.org/soap/envelope/") as $body) {
            foreach ($body->children("https://servicesenlignedev.ville.montreal.qc.ca")->GetPlanificationsForDateResponse->children() as $response) {
                foreach ($response->planifications->planification as $planif) {
                    $coteRue = substr($planif->coteRueId, -1, 1);

                    $trId = rtrim($planif->coteRueId, '12');
                    if (!array_key_exists((int)$trId, $keyedData)) {
                        continue;
                    }
                    if (!array_key_exists($trId, $update)) {
                        $update[$trId] = ['same_count' => 0, 'id' => $keyedData[$trId]['id'], 'street_side_one_state' => $keyedData[$trId]['street_side_one_state'], 'street_side_two_state' => $keyedData[$trId]['street_side_two_state']];
                    }
                    if ($coteRue === '1') {
                        if ((int)$keyedData[$trId]['street_side_one_state'] !== (int)$planif->etatDeneig) {
                            ++$update[$trId]['same_count'];
                        }
                        $update[$trId]['street_side_one_state'] = (int)$planif->etatDeneig;
                    } else if ($coteRue === '2') {
                        if ((int)$keyedData[$trId]['street_side_two_state'] !== (int)$planif->etatDeneig) {
                            ++$update[$trId]['same_count'];
                        }
                        $update[$trId]['street_side_two_state'] = (int)$planif->etatDeneig;
                    }
                }
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
            $updateQ = sprintf($updateQ, (int)$row['street_side_one_state'], (int)$row['street_side_two_state'], $date, $row['id']);
            $updateQuery .= $updateQ . ";";
        }

        $troncon->executeQuery($updateQuery);
    }
}

$infoNeige = new InfoNeigeJob();
$infoNeige->run();