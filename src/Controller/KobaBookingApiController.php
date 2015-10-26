<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingApiController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
      ->condition('status', 1)
      ->condition('field_resource', '_none', '<>');
    $nids = $query->execute();

    // Load nodes and build response.
    $nodes = \Drupal::entityManager()->getStorage('node')->loadMultiple($nids);
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

    $data = $proxy->getResourceBookings($resource_id, $from, $to);
    return new JsonResponse($data, 200);
  }

  /**
   * Handle callback from koba.
   *
   * @TODO: Send mail to client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP post request.
   * @return JsonResponse
   */
  public function callback(Request $request) {
    // Get JSON parameters.
    $params = array();
    $content = $request->getContent();
    if (!empty($content)) {
      // 2nd param to get as array
      $params = json_decode($content, TRUE);
    }

    // Load booking entity.
    $booking = \Drupal::entityManager()->loadEntityByUuid('koba_booking_booking', $params['client_booking_id']);

    // Check if entity was loaded.
    if ($booking) {
      $mailer =  \Drupal::service('koba_booking.mailer');

      // For efficiency manually save the original booking before applying any
      // changes.
      $booking->original = clone $booking;

      // Change booking state.
      switch (strtoupper($params['status'])) {
        case 'ACCEPTED':
          $booking->set('booking_status', 'accepted');
          $mailer->send('accepted', $booking);
          break;

        case 'CANCELLED':
          $booking->set('booking_status', 'cancelled');
          $mailer->send('cancelled', $booking);
          break;

        case 'UNCONFIRMED':
          // If unconfirmed, leave it in pending.
          $booking->set('booking_status', 'unconfirmed');
          break;

        case 'NOT CANCELLED':
          $booking->set('booking_status', 'accepted');
          $mailer->send('cancelled', $booking);
          break;

        default:
          $booking->set('booking_status', 'refused');
          $mailer->send('rejected', $booking);
      }
      $booking->save();
    }
    else {
      \Drupal::logger('koba_booking')->error('No entity with uuid: ' . $params['client_booking_id']);
    }

    return new JsonResponse(array(), 200);
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

    $data = \Drupal::service('session')->get('koba_booking_request');
    if (empty($data)) {
      // Create new data array, as nothing was store in current session.
      $data = array();
    }

    // Get configuration (session expire).
    $config = \Drupal::config('koba_booking.settings');

    // Set newest booking information.
    $data = array(
      'resource' => $resource_id,
      'from' => $from,
      'to' => $to,
      'expire' => REQUEST_TIME + $config->get('koba_booking.session.expire'),
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
