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

    /**
     * Test Discord integration.
     * 
     * @command sparkle:feed
     * @aliases sparkle-feed
     * 
     * @usage sparkle:feed
     */
    public function testFeedUpdate() {
        \Drupal::service('sparkle_integration.social_announcement')->postAppcastToFeed();
    }

    /**
     * Poll SE's Appcast feed.
     * 
     * @command sparkle:secheck
     * @aliases sparkle-secheck
     * 
     * @usage sparkle:secheck
     */
    public function SECheck() {
        \Drupal::service('sparkle_integration.se_update_scanner')->checkAndNotify();
    }
}