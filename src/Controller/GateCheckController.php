<?php

namespace Drupal\sparkle_integration\Controller;

use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class GateCheckController extends ControllerBase {

  private $http_client;
  public function __construct(Client $http_client) {
    $this->http_client = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  public function checkGate() {
    $gate_json = $this->http_client->get('https://frontier.ffxiv.com/worldStatus/gate_status.json')->getBody()->getContents();
    $response = json_decode($gate_json, TRUE);

    if ($response['status'] == '1') {
      return new JsonResponse(['message' => 'Gate reports OK.']);
    } else {
      return new JsonResponse(['message' => 'Gate reports maintenance.'], 418);
    }
  }

  public function checkLogin() {
    $login_json = $this->http_client->get('https://frontier.ffxiv.com/worldStatus/login_status.json')->getBody()->getContents();
    $response = json_decode($login_json, TRUE);

    if ($response['status'] == '1') {
      return new JsonResponse(['message' => 'Login server reports OK.']);
    } else {
      return new JsonResponse(['message' => 'Login server reports maintenance.'], 418);
    }
  }

  public function checkDalamud() {
    try {
      $thaliak_response = $this->http_client->get('https://thaliak.xiv.dev/api/versions/4e9a232b/latest')->getBody()->getContents();
      $thaliak_response = json_decode($thaliak_response, TRUE);
      $latest_game_ver = $thaliak_response[0]['version'];
      $dalamud_meta_url = 'https://kamori.goats.dev/Dalamud/Release/VersionInfo?track=release&appId=xom';
      $dalamud_meta = $this->http_client->get($dalamud_meta_url)->getBody()->getContents();
      $dalamud_meta = json_decode($dalamud_meta, TRUE);
      $dalamud_supported_game_ver = $dalamud_meta['supportedGameVer'];
    } catch (\Exception $exception) {
      \Drupal::logger('sparkle_integration')->error($exception->getMessage());
      return new JsonResponse([
        'result' => 'Error polling Dalamud or Thaliak API.'
      ], 500);
    }

    if ($latest_game_ver == $dalamud_supported_game_ver) {
      return new JsonResponse([
        'result' => 'Latest game version is supported by Dalamud.',
        'latest_game_ver' => $latest_game_ver,
        'dalamud_supported_game_ver' => $dalamud_meta['supportedGameVer']
      ]);
    } else {
      return new JsonResponse([
        'message' => 'Dalamud is not yet available for the latest game version.',
        'latest_game_ver' => $latest_game_ver,
        'dalamud_supported_game_ver' => $dalamud_meta['supportedGameVer']
      ], 418);
    }

  }
}