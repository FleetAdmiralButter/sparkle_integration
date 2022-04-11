<?php

namespace Drupal\sparkle_integration\Socials;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;
use GuzzleHttp\Client;


class FeedHelper {

    public function __construct() {

    }

    public function updateFeed($message) {
        $homepage = \Drupal::entityTypeManager()->getStorage('node')->load(1);
        $feed = $homepage->get('body')->getString();
        $feed = $message . $feed;
        $homepage->set('body', ['value' => $feed, 'format' => 'markdown']);
        $homepage->save();
    }

    public function templateChangelogEntries($changelogEntries) {
        $result = "";
        foreach ($changelogEntries as $changelogEntry) {
            $result .= '• ' . $changelogEntry . "<br>";
        }
        return $result;
    }

    public function templateMessage($version, $description, $date) {
        return <<<EOT
        \r\n
        <strong>$date:</strong><br><br>
        <b>Delta updates</b> <i>(Updated through application)</i><br><br>

        Delta Changelog: $version Beta

        $description

        ---
        \r\n
        EOT;
    }
}