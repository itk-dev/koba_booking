<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingApiController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\Client;
use Drupal\Core\Url;
use Drupal\koba_booking\BookingInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Exception\RequestException;

/**
 * KobaBookingApiController.
 */
class KobaBookingApiController extends ControllerBase {
  /**
   * Get available resources.
   *
   * @return JsonResponse
   */
  public function resources() {
    // Fetch module config settings.
    $config = \Drupal::config('koba_booking.settings');
    $apikey = $config->get('koba_booking.api_key');
    $path = $config->get('koba_booking.path');

    $url = $path . "/api/resources/group/default?apikey=" . $apikey;

    // Instantiates a new guzzle client.
    $client = new Client();

    try {
      $response = $client->get($url);
      $body = json_decode($response->getBody());

      return new JsonResponse($body, $response->getStatusCode());
    }
    catch (RequestException $e) {
      echo $e->getRequest() . "\n";
      if ($e->hasResponse()) {
        echo $e->getResponse() . "\n";
      }
    }
  }

  /**
   * Get bookings for a resource.
   *
   * @param Request $request
   *   Represents an HTTP request.
   * @return JsonResponse
   */
  public function bookings(Request $request) {
    $resource = $request->query->get('res');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    // Fetch module config settings.
    $config = \Drupal::config('koba_booking.settings');
    $apikey = $config->get('koba_booking.api_key', '');
    $path = $config->get('koba_booking.path', '');

    $url = $path . '/api/resources/' . $resource . '/group/default/freebusy/from/' . $from . '/to/' . $to . '?apikey=' . $apikey;

    // Instantiates a new guzzle client.
    $client = new Client();

    try {
      $response = $client->get($url);
      $body = json_decode($response->getBody());

      return new JsonResponse($body, $response->getStatusCode());
    }
    catch (RequestException $e) {
      echo $e->getRequest() . "\n";
      if ($e->hasResponse()) {
        echo $e->getResponse() . "\n";
      }
    }
  }

  /**
   * Save booking information in session to pre-fill form later.
   *
   * This also makes an redirect to WAYF login.
   *
   * @TODO: This is really not the right place for this function, as it has
   *        about wayf.
   *
   * @param Request $request
   *   Represents an HTTP request.
   * @return JsonResponse
   */
  public function login(Request $request) {
    // Get requested parameters.
    $resource_id = $request->query->get('res');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    /**
     * @TODO: Validate the request.
     */

    $data = $defaults = \Drupal::service('session')->get('koba_booking_request');
    if (empty($data)) {
      // Create new data array, as nothing was store in current session.
      $data = array();
    }

    // Set newest booking information.
    $data = array(
      'ressource' => $resource_id,
      'from' => $from,
      'to' => $to,
    ) + $data;

    // Store information in session.
    \Drupal::service('session')->set('koba_booking_request', $data);

    /**
     * @TODO: check if data exists in the session and then not redirect to login...
     */

    // Check if the user has authenticated with WAYF.
    if (empty($data['uuid'])) {
      // Redirect to WAYF login.
      return $this->redirect('wayf_dk_login.consume');
    }

    // No need to login once more, so send the user to add booking.
    return $this->redirect('koba_booking.booking_add');
  }

  /**
   * @TODO: This is really not the right place for this function, as it has
   *        about wayf.
   *
   */
  public function logout() {
    // Set destination (booking/add) and redirect til wayf logout.
    $generator = \Drupal::urlGenerator();
    $url = $generator->generateFromRoute('wayf_dk_login.logout', array(), array(
      'query' => array(
        'destination' => '/booking/add'
      )
    ));

    return (new RedirectResponse($url))->send();
  }
}
