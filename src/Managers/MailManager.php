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

        $emailContent = $this->buildHtmlContent($contribution);

        // Email object
        $email = new SendSmtpEmail([
            'sender'      => ['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']],
            'to'          => [['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']]],
            'replyTo'     => ['name' => $_ENV['MAIL_FROM_NAME'], 'email' => $_ENV['MAIL_FROM']],
            'htmlContent' => $emailContent,
            'subject'     => 'Nouvelle contribution',
        ]);

        try {
            $client->sendTransacEmail($email);
        } catch (ApiException $e) {
            return false;
        }

        return true;
    }

    private function buildHtmlContent(array $contribution): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $contribution['created_at']);
        $hour = \DateTime::createFromFormat('Y-m-d H:i:s', $contribution['created_at'], new DateTimeZone('America/New_York'));

        $color = "%23439666";
        if ($contribution['quality'] === 0) {
            $color = "%23367c99";
        } elseif ($contribution['quality'] === -1) {
            $color = "%23f09035";
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

        $formatter = new IntlDateFormatter('fr_CA',
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            new DateTimeZone('America/New_York')
        );
        $mailContent = "<p>Carte: <a target='_blank' href='".$contribLink."'>$contribLink</a></p>";

        $mailContent .= "<p>Date: " . $formatter->format($date) . "</p>";
	$mailContent .= "<p>Nom: ". $contribution['name'] . "</p>";
        $mailContent .= "<p>Message: ". $contribution['comment']."</p>";
        if (!is_null($imageUrl)) {
            $mailContent .= "<p><img src='".$imageUrl."'></p>";
        }
        $mailContent .= "<p><img src='".$mapsUrl."'></p>";


        return $mailContent;
    }
}
