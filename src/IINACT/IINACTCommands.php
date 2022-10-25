<?php

namespace Drupal\sparkle_integration\IINACT;

use Drush\Commands\DrushCommands;

class IINACTCommands extends DrushCommands {
  /**
   * Get the latest plugin version
   *
   * @command sparkle:iinact-update
   *
   * @usage sparkle:iinact-update
   */
  public function pluginGetLatest() {
    \Drupal::logger('sparkle_integration')->debug('IINACT: Caches scheduled for sync.');
    \Drupal::service('sparkle_integration.iinact_update_manager')->pluginGetLatest();
  }
}