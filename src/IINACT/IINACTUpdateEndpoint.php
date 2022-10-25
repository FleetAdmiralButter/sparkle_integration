<?php

namespace Drupal\sparkle_integration\IINACT;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class IINACTUpdateEndpoint
 * Faster than advancedcombattracker.com :)
 */
class IINACTUpdateEndpoint extends ControllerBase {

  private $cache;
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default')
    );
  }

  public function serve() {
    $hit = 'HIT';
    $version = $this->fetchFromCache('iinact_plugin_latest');
    if (!$version) {
      $hit = 'MISS';
      \Drupal::service('sparkle_integration.iinact_update_manager')->pluginGetLatest();
      $version = $this->fetchFromCache('iinact_plugin_latest');
    }
    return new Response($version, 200, ['X-IINACT-Origin-Cache-Status' => $hit]);
  }

  public function serveDownload() {
    $hit = 'HIT';
    $url = $this->fetchFromCache('iinact_plugin_latest_url');
    if (!$url) {
      $hit = 'MISS';
      \Drupal::service('sparkle_integration.iinact_update_manager')->pluginGetLatest();
      $url = $this->fetchFromCache('iinact_plugin_latest_url');
    }
    return new TrustedRedirectResponse($url, 307, ['X-IINACT-Origin-Cache-Status' => $hit]);
  }

  private function fetchFromCache($cid) {
    return $this->cache->get($cid, TRUE)->data;
  }
}