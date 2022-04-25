<?php

namespace Drupal\sparkle_integration\Socials;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;
use GuzzleHttp\Client;

class SEUpdateScanner {

    private $webhook_url;
    private $se_appcast_hash;
    private $http_client;
    public function __construct(Client $http_client) {
        // Read the webhook URL from the environment.
        $this->webhook_url = Settings::get('sparkle_integration.yshtola_webhook_url');
        $this->se_appcast_hash = Settings::get('sparkle_integration.se_appcast_hash');
        $this->http_client = $http_client;
    }

    public function checkAndNotify() {
        $appcast = $this->downloadSEAppcast();
        $appcast_hash = hash('sha256', $appcast);
        if ($appcast_hash !== $this->se_appcast_hash) {
            $this->postDiscordMessage();
        } else {
            \Drupal::messenger()->addMessage('No changes detected, hash matches.');
        }
    }

    private function downloadSEAppcast() {
        $se_appcast = 'https://mac-dl.ffxiv.com/cw/finalfantasy-mac.xml';
        return $this->http_client->get($se_appcast)->getBody()->getContents();
    }

    private function postDiscordMessage() {
        $body = [
            'content' => $this->messageTemplate(),
            'username' => "Y'shtola",
            'avatar_url' => "https://content.xivmac.com/sites/default/files/2022-04/yshtola_discord_profile.jpeg",
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

    private function messageTemplate() {
        return <<<EOT
        Pray, be vigilant! I've observed a change to Square's update feed!

        Please investigate: https://mac-dl.ffxiv.com/cw/finalfantasy-mac.xml
        EOT;
    }

}