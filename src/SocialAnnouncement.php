<?php

namespace Drupal\sparkle_integration;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;

class SocialAnnouncement {

    private $webhook_url;
    private $http_client;
    public function __construct() {
        // Read the webhook URL from the environment.
        $this->webhook_url = Settings::get('sparkle_integration.webhook_url');
        $this->http_client = \Drupal::service('http_client');
    }

    public function postAppcastToDiscord() {
        $appcast = $this->parseAppcast();
        $description = $this->templateChangelogEntries($appcast['changelogEntries']);
        $message = $this->templateMessage($appcast['version'], $description);
        $this->postDiscordMessage($message);
    }

    private function postDiscordMessage($message) {
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

    private function parseAppcast() {
        $xml = new \SimpleXMLElement(data: 'public://update_data/xivonmac_appcast.xml', dataIsURL: TRUE);
        $xml->registerXPathNamespace('sparkle', 'http://www.andymatuschak.org/xml-namespaces/sparkle');
        $changelogEntries = $this->getChangelogContents($xml->channel->item[0]->description->__toString());
        $version = $xml->channel->item[0]->title;

        return [
            'version' => $version,
            'changelogEntries' => $changelogEntries,
        ];
    }

    private function getChangelogContents($description) {
        return $this->tagContents($description, '<li>', '</li>');
    }

    private function templateChangelogEntries($changelogEntries) {
        $result = "";
        foreach ($changelogEntries as $changelogEntry) {
            $result .= '- ' . $changelogEntry . "\n";
        }
        return $result;
    }

    private function templateMessage($version, $description) {
        return <<<EOT
        :star: LATEST RELEASE:  Delta Changelog: $version Beta

        Changelog:
        $description
        \n
        ---
        You can grab the full application @ http://www.xivmac.com/
        Deltas updates will be come through the application.
        EOT;
    }

    
    private function tagContents($string, $tag_open, $tag_close){
        foreach (explode($tag_open, $string) as $key => $value) {
            if(strpos($value, $tag_close) !== FALSE){
                 $result[] = substr($value, 0, strpos($value, $tag_close));
            }
        }
        return $result;
     }
}