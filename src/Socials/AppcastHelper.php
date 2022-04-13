<?php

namespace Drupal\sparkle_integration\Socials;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;
use Drupal\Component\Utility\Xss;
use GuzzleHttp\Client;

class AppcastHelper {

    public function __construct() {

    }

    public function parseAppcast() {
        $xml = new \SimpleXMLElement(data: 'public://update_data/xivonmac_appcast.xml', dataIsURL: TRUE);
        $xml->registerXPathNamespace('sparkle', 'http://www.andymatuschak.org/xml-namespaces/sparkle');
        $changelogEntries = $this->getChangelogContents($xml->channel->item[0]->description->__toString());
        $version = Xss::filter($xml->channel->item[0]->title);

        return [
            'version' => $version,
            'changelogEntries' => $changelogEntries,
        ];
    }

    private function getChangelogContents($description) {
        return $this->tagContents($description, '<li>', '</li>');
    }

    private function tagContents($string, $tag_open, $tag_close){
        foreach (explode($tag_open, $string) as $key => $value) {
            if(strpos($value, $tag_close) !== FALSE){
                 $result[] = Xss::filter(substr($value, 0, strpos($value, $tag_close)));
            }
        }
        return $result;
     }

}
