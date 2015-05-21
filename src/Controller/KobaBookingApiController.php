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
use Drupal\koba_booking\Exception\ProxyException;
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
   * @TODO: Maybe use a cache as rooms don't change.
   *
   * @return JsonResponse
   */
  public function resources() {
    $rooms = array();

    // Get all rooms that is connected to a koba resource.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'room')
      ->condition('status', 1);
    $nids = $query->execute();

    // Load nodes and build response.
    $nodes = entity_load_multiple('node', $nids);
    foreach ($nodes as $node) {
      $rooms[] = array(
        'id' => $node->nid->value,
        'name' => $node->title->value,
        'mail' => $node->field_resource->value,
      );
    }

    return new JsonResponse($rooms, 200);
  }

  /**
   * Get bookings for a resource.
   *
   * @param Request $request
   *   Represents an HTTP request.
   * @return JsonResponse
   */
  public function bookings(Request $request) {
    $resource_id = $request->query->get('res');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    // Get proxy service.
    $proxy =  \Drupal::service('koba_booking.api.proxy');

    try {
      $data = $proxy->getResourceBookings($resource_id, $from, $to);
      return new JsonResponse($data, 200);
    }
    catch (ProxyException $exception) {
      return new JsonResponse(array('message' => $exception->getMessage(), 500));
    }
  }

  /**
   * Handle callback from koba.
   *
   * @TODO: Send mail to client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP post request.
   */
  public function callback(Request $request) {
    $params = array();
    $content = $request->getContent();
    if (!empty($content)) {
      // 2nd param to get as array
      $params = json_decode($content, TRUE);
    }

    $status = $params['status'];
    $entity_id = $params['client_booking_id'];

    // Load booking entity.
    $booking = entity_load('koba_booking_booking', $entity_id);

    \Drupal::logger('booking')->debug($status . ' -> ' . $entity_id);

    if ($booking) {
      // For efficiency manually save the original booking before applying any
      // changes.
      $booking->original = clone $booking;

      // Change booking state.
      if ($status == 'ACCEPTED') {
        $booking->set('booking_status', 'accepted');
      }
      else {
        $booking->set('booking_status', 'rejected');
      }

      $booking->save();
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
      'resource' => $resource_id,
      'from' => $from,
      'to' => $to,
    ) + $data;

    // Store information in session.
    \Drupal::service('session')->set('koba_booking_request', $data);

    // Check if the user has authenticated with WAYF and .
    if (empty($data['uuid']) || !\Drupal::moduleHandler()->moduleExists('wayf_dk_login')) {
      // Redirect to WAYF login.
      return $this->redirect('wayf_dk_login.consume');
    }

    // No need to login once more, so send the user to add booking.
    return $this->redirect('koba_booking.booking_add');
  }

  /**
   * Logout of WAYF using an redirection.
   *
   * @TODO: This is really not the right place for this function, as it has
   *        about wayf.
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
