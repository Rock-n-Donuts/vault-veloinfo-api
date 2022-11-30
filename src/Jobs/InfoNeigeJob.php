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
     * @todo use real SoapClient to parse data
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
        curl_setopt($ch, CURLOPT_URL, "https://servicesenligne2.ville.montreal.qc.ca/api/infoneige/InfoneigeWebService");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: text/xml',
        ));
        $xmlRaw = curl_exec($ch);
        curl_close($ch);

        $troncon = new Troncon();
        $ids = "SELECT id, id_trc, street_side_one_state, street_side_two_state FROM troncons";
        $data = $troncon->executeQuery($ids);
        $ids = array_column($data, 'id_trc');
        $keyedData = array_combine($ids, $data);
        $update = [];

        $xml = simplexml_load_string($xmlRaw);
        foreach ($xml->children("http://schemas.xmlsoap.org/soap/envelope/") as $body) {
            foreach ($body->children("https://servicesenlignedev.ville.montreal.qc.ca")->GetPlanificationsForDateResponse->children() as $response) {
                
                if (!isset($response->planifications)) {
                    die('2 fast');
                }

                foreach ($response->planifications->planification as $planif) {
                    $coteRue = substr($planif->coteRueId, -1, 1);
                    $trId = substr($planif->coteRueId, 0, -1);

                    if (!array_key_exists($trId, $keyedData)) {
                        continue;
                    }

                    if (!array_key_exists($trId, $update)) {
                        $update[$trId] = [
                            'id' => $keyedData[$trId]['id'],
                            'street_side_one_state' => null,
                            'street_side_two_state' => null
                        ];
                    }

                    if ($coteRue === '1') {
                        if ((int)$keyedData[$trId]['street_side_one_state'] !== (int)$planif->etatDeneig) {
                            $update[$trId]['street_side_one_state'] = $planif->etatDeneig;
                        }
                    } else if ($coteRue === '2') {
                        if ((int)$keyedData[$trId]['street_side_two_state'] !== (int)$planif->etatDeneig) {
                            $update[$trId]['street_side_two_state'] = $planif->etatDeneig;
                        }
                    }
                }
            }
        }

        $updateQuery = "";
        $date = date('Y-m-d H:i:s');

        foreach ($update as $trId => $row) {
            $hasS1 = !is_null($row['street_side_one_state']);
            $hasS2 = !is_null($row['street_side_two_state']);

            if (!$hasS1 && !$hasS2) {
                continue;
            }

            $updateQ = "UPDATE troncons SET ";
            $args = [];

            if ($hasS1) {
                $updateQ .= "`street_side_one_state` = %d, ";
                $args[] = $row['street_side_one_state'];
            }

            if ($hasS2) {
                $updateQ .= "`street_side_two_state` = %d, ";
                $args[] = $row['street_side_two_state'];
            }

            $updateQ .= "`updated_at` = '%s' WHERE id_trc = '%d'";
            $args[] = $date;
            $args[] = $trId;

            $updateQ = vsprintf($updateQ, $args);
            $updateQuery .= $updateQ . ";";
        }

        // print_r($updateQuery);

        if (strlen($updateQuery) > 0) {
            $troncon->executeQuery($updateQuery);
        }
    }
}

$infoNeige = new InfoNeigeJob();
$infoNeige->run();
