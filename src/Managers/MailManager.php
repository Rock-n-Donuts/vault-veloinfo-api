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
            'sender'      => ['name' => 'VeloInfo Site', 'email' => 'notification.veloinfo@gmail.com'],
            'to'          => [['name' => 'VeloInfo Site', 'email' => 'notification.veloinfo@gmail.com']],
            'replyTo'     => ['name' => 'VeloInfo Site', 'email' => 'notification.veloinfo@gmail.com'],
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


        $imageUrl = FileHelper::getUploadUrl($contribution['photo_path']);
        $coords = explode(",", $contribution['location']);
        $coords = array_reverse($coords);
        $coords = implode(",", $coords);
        $mapsUrl = "https://www.google.com/maps/search/?api=1&query=$coords";

        $contribLink = "https://veloinfo.ca/contribution/" . $contribution['id'];

        $formatter = new IntlDateFormatter('fr_CA',
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE,
            new DateTimeZone('America/New_York')
        );
        $mailContent = "<p>Date: " . $formatter->format($date) . "</p>";
        $mailContent .= "<p>Heure: ". $hour->format('H:i')."</p>";
        $mailContent .= "<p>Message: ". $contribution['comment']."</p>";
        $mailContent .= "<p><img src='".$imageUrl."'></p>";
        $mailContent .= "<p><a target='_blank' href='".$mapsUrl."'>Lien Google maps</a></p>";
        $mailContent .= "<p><a target='_blank' href='".$contribLink."'>Lien Veloinfo</a></p>";

        return $mailContent;
    }
}