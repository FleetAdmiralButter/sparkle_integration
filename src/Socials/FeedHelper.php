<?php

namespace Drupal\sparkle_integration\Socials;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\Serializer\Encoder;
use GuzzleHttp\Client;

class FeedHelper {

    public function __construct() {

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
}