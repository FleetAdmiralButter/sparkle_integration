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

    public function parseAppcast() {
        $xml = new \SimpleXMLElement(data: 'public://update_data/xivonmac_appcast.xml', dataIsURL: TRUE);
        $xml->registerXPathNamespace('sparkle', 'http://www.andymatuschak.org/xml-namespaces/sparkle');
        $changelogEntries = $this->getChangelogContents($xml->channel->item[0]->description->__toString());
        $version = $xml->channel->item[0]->title;

        return [
            'version' => $version,
            'changelogEntries' => $changelogEntries,
        ];
    }

    public function getChangelogContents($description) {
        return $this->tagContents($description, '<li>', '</li>');
    }

    public function templateChangelogEntries($changelogEntries) {
        $result = "";
        foreach ($changelogEntries as $changelogEntry) {
            $result .= '• ' . $changelogEntry . "\n";
        }
        return $result;
    }

    public function templateFeedEntry($version, $description, $date) {
        return <<<EOT
        <strong>April 4th, 2022:</strong><br><br>
        <b>Delta updates</b> <i>(Updated through application)</i><br><br>

        -Delta Changelog: 3.4.5 Beta

        $description

        ---
        EOT;
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

    
    public function tagContents($string, $tag_open, $tag_close){
        foreach (explode($tag_open, $string) as $key => $value) {
            if(strpos($value, $tag_close) !== FALSE){
                 $result[] = substr($value, 0, strpos($value, $tag_close));
            }
        }
        return $result;
     }

}