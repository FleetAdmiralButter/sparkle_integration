<?php

namespace Drupal\sparkle_integration\Socials;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;
use GuzzleHttp\Client;

class DiscordHelper {

    private $webhook_url;
    private $http_client;
    public function __construct(Client $http_client) {
        // Read the webhook URL from the environment.
        $this->webhook_url = Settings::get('sparkle_integration.webhook_url');
        $this->http_client = $http_client;
    }

    public function postDiscordMessage($message) {
        $body = [
            'content' => $message,
            'username' => 'www.xivmac.com',
            'avatar_url' => "https://content.xivmac.com/sites/default/files/2022-04/discord_bot.png",
        ];
        $body = Json::encode($body);
        $request_params = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $body
        ];
        try {
            $this->http_client->post($this->webhook_url, $request_params);
        } catch (\Exception) {
            \Drupal::service('messenger')->addError('Discord webhook error.');
        }
        
    }

    public function templateChangelogEntries($changelogEntries) {
        $result = "";
        foreach ($changelogEntries as $changelogEntry) {
            $result .= '• ' . $changelogEntry . "\n";
        }
        return $result;
    }

    public function templateMessage($version, $description) {
        return <<<EOT
        :star: LATEST RELEASE:  Delta Changelog: $version Beta

        Changelog:
        $description
        ---
        Deltas updates will be come through the application.
        EOT;
    }

}