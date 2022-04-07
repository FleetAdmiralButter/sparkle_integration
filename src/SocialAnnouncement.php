<?php

namespace Drupal\sparkle_integration;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Drupal\sparkle_integration\Socials\DiscordHelper;
use Symfony\Component\Serializer\Encoder;
use GuzzleHttp\Client;

class SocialAnnouncement {

    private $discord_helper;
    private $feed_helper;
    public function __construct(DiscordHelper $discord_helper) {
        $this->discord_helper = $discord_helper;
    }

    public function postAppcastToDiscord() {
        $appcast = $this->discord_helper->parseAppcast();
        $description = $this->discord_helper->templateChangelogEntries($appcast['changelogEntries']);
        $message = $this->discord_helper->templateMessage($appcast['version'], $description);
        $this->discord_helper->postDiscordMessage($message);
    }

}