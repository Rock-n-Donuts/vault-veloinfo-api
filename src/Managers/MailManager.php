<?php

namespace Rockndonuts\Hackqc\Managers;

use DateTimeZone;
use GuzzleHttp\Client as GuzzleClient;
use IntlDateFormatter;
use InvalidArgumentException;
use JsonException;
use Rockndonuts\Hackqc\FileHelper;
use Rockndonuts\Hackqc\Models\Contribution;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;

class MailManager
{
    /**
     * @param Contribution $contribution
     * @return bool|string
     * @throws \Exception
     */
    public function contributionNotification(array $contribution): bool|string
    {
        if (empty($_ENV['SENDINBLUE_KEY'])) {
            throw new \Exception('API Key not set');
        }

        $key = $_ENV['SENDINBLUE_KEY'];
        // Set key
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $key);

        // Create client
        $client = new TransactionalEmailsApi(
            new GuzzleClient(),
            $config
        );

        $formatter = new IntlDateFormatter(
            'fr_CA',
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            new DateTimeZone('America/New_York')
        );

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $contribution['created_at']);
        $dateFormatted = $formatter->format($date);

        $quality = $contribution['quality'];
        $sectorName = "";
        if (!empty($contribution['plowing_sector'])) {
            $sectorName = " (".$contribution['plowing_sector'].") ";
        }
        $subject = ($quality > 0 ? '🟢' : ($quality < 0 ? '🔴' : '🟡')).' '.$contribution['borough_name']. $sectorName . ' - '.$dateFormatted;
        $emailContent = $this->buildHtmlContent($contribution, $contribution['borough_name'], $dateFormatted);

        // Email object
        $email = new SendSmtpEmail([
            'sender'      => ['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']],
            'to'          => [['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']]],
            'replyTo'     => ['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']],
            'htmlContent' => $emailContent,
            'subject'     => $subject,
        ]);

        try {
            $client->sendTransacEmail($email);
        } catch (ApiException $e) {
            return false;
        }

        return true;
    }

    private function buildHtmlContent(array $contribution, string $boroughName, string $dateFormatted): string
    {
        $color = "%23439666";
        if ($contribution['quality'] == 0) {
            $color = "%23f09035";
        } elseif ($contribution['quality'] == -1) {
            $color = "%23ff0000";
        }
        $imageUrl = null;
        if (!empty($contribution['photo_path'])) {
            $imageUrl = FileHelper::getUploadUrl($contribution['photo_path']);
        }
        $coords = explode(",", $contribution['location']);
        $coords = array_reverse($coords);
        $coords = implode(",", $coords);
        //        $mapsUrl = "https://www.google.com/maps/search/?api=1&query=$coords";

        $mapsUrlTemplate = "https://maps.geoapify.com/v1/staticmap?style=osm-carto&width=600&height=400&center=lonlat:%s&zoom=14&apiKey=%s&marker=lonlat:%s;color:%s;size:large;type:awesome;icon:snowflake;iconsize:large;whitecircle:no";
        $mapsUrl = sprintf(
            $mapsUrlTemplate,
            $contribution['location'],
            $_ENV['GEOAPIFY_KEY'],
            $contribution['location'],
            $color
        );
        $contribLink = "https://veloinfo.ca/contribution/" . $contribution['id'];
        
        
        $mailContent = "";

        $mailContent .= "<p>Arrondissement: " . $boroughName . "</p>";
        if (!empty($contribution['plowing_sector'])) {
            $mailContent .= "<p>Secteur: " . $contribLink['plowing_sector'] . "</p>";
        }
        $mailContent .= "<p>Nom: " . $contribution['name'] . "</p>";
        $mailContent .= "<p>Message: " . $contribution['comment'] . "</p>";
        $mailContent .= "<p>Date: " . $dateFormatted . "</p>";
        $mailContent .= "<p>Carte: <a target='_blank' href='" . $contribLink . "'>$contribLink</a></p>";
        if (!is_null($imageUrl)) {
            $mailContent .= "<p><img src='" . $imageUrl . "'></p>";
        }
        $mailContent .= "<p><img src='" . $mapsUrl . "'></p>";


        return $mailContent;
    }
}
