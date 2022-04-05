<?php

namespace Drupal\sparkle_integration\Commands;

use Drush\Commands\DrushCommands;

class TestCommands extends DrushCommands {

    /**
     * Test Discord integration.
     * 
     * @command sparkle:discord
     * @aliases sparkle-discord
     * 
     * @usage sparkle:discord
     */
    public function testDiscord() {
        \Drupal::service('sparkle_integration.social_announcement')->postAppcastToDiscord();
    }
}