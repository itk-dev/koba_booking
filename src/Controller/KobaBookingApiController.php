<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingApiController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\koba_booking\BookingInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Exception\RequestException;

/**
 * KobaBookingApiController.
 */
class KobaBookingApiController extends ControllerBase {
  public function resources() {
    // Fetch module config settings.
    $config = \Drupal::config('koba_booking.settings');
    $apikey = $config->get('koba_booking.api_key', '');
    $path = $config->get('koba_booking.path', '');

    $url = $path . "/api/resources/group/default?apikey=" . $apikey;

    // Instantiates a new guzzle client.
    $client = new \GuzzleHttp\Client();
    // Get google.com’s main page as a response object.
    $response = $client->get($url);

    $body = json_decode($response->getBody());

    return new JsonResponse($body, $response->getStatusCode());
  }

  public function resource(Request $request) {
    $resource = $request->query->get('res');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    // Fetch module config settings.
    $config = \Drupal::config('koba_booking.settings');
    $apikey = $config->get('koba_booking.api_key', '');
    $path = $config->get('koba_booking.path', '');

    $url = $path . '/api/resources/' . $resource . '/group/default/bookings/from/' . $from . '/to/' . $to . '?apikey=' . $apikey;

    // Instantiates a new guzzle client.
    $client = new \GuzzleHttp\Client();
    // Get google.com’s main page as a response object.
    $response = $client->get($url);

    $body = json_decode($response->getBody());

    return new JsonResponse($body, $response->getStatusCode());
  }
}
