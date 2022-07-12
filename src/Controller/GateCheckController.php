<?php

namespace Drupal\sparkle_integration\Controller;

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
}