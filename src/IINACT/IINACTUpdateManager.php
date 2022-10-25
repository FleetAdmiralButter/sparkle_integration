<?php

namespace Drupal\sparkle_integration\IINACT;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use GuzzleHttp\Client;
use Drupal\Component\Utility\Xss;

class IINACTUpdateManager {

    private CacheBackendInterface $cache;
    private $http_client;
    public function __construct(CacheBackendInterface $cache, Client $http_client) {
        $this->cache = $cache;
        $this->http_client = $http_client;
    }

    public function pluginGetLatest() {
      try {
        \Drupal::logger('sparkle_integration')->debug('IINACT: Cache sync requested.');
        $release_json = $this->http_client->get('https://api.github.com/repos/ravahn/FFXIV_ACT_Plugin/releases/latest')->getBody()->getContents();
        $response = json_decode($release_json, TRUE);
        $latest_plugin_ver = $response['tag_name'];
        $latest_plugin_ver = Xss::filter($latest_plugin_ver);
        $url = $response['assets'][0]['browser_download_url'];
        $this->updateCachedPluginVersion($latest_plugin_ver, $url);
        \Drupal::logger('sparkle_integration')->debug('IINACT: Cache sync complete.');
      } catch (\Exception $e) {
        \Drupal::logger('sparkle_integration')->error('IINACT Plugin refresh failed, serving stale content.');
        \Drupal::logger('sparkle_integration')->error($e->getMessage());
      }
    }

    private function updateCachedPluginVersion($version, $url) {
      Cache::invalidateTags(['iinact_plugin_latest']);
      $this->cache->set('iinact_plugin_latest_url', $url, CacheBackendInterface::CACHE_PERMANENT, ['iinact_plugin_latest']);
      $this->cache->set('iinact_plugin_latest', $version, CacheBackendInterface::CACHE_PERMANENT, ['iinact_plugin_latest']);
    }
}