<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Url;
use Drupal\koba_booking\BookingInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * KobaBookingController.
 */

class KobaBookingController extends ControllerBase  {

  /**
   * Setup template for frontend for calendar page.
   *
   * @return array
   *   Render array for calendar page.
   */
  public function calendarPage() {
    $defaults = \Drupal::service('session')->get('koba_booking_request');

    $build = array(
      '#type' => 'markup',
      '#theme' => 'booking_calendar_page',
      '#attached' => array(
        'library' => array(
          'koba_booking/angular',
        ),
        'drupalSettings' => array(
          'koba_booking' => array(
            'module_path' => \Drupal::moduleHandler()->getModule('koba_booking')->getPath(),
            'theme_path' => \Drupal::theme()->getActiveTheme()->getPath(),
            'resource' => $defaults['resource'],
            'from' => $defaults['from'],
            'to' => $defaults['to'],
          ),
        ),
      ),
    );
    return $build;
  }

  /**
   * Booking receipt page.
   *
   * @param string $hash
   *   Hash value that identifies a given booking.
   *
   * @return array
   *   Render array for calendar page.
   */
  public function receipt($hash) {
    // Load entity base on hash value.
    $query = \Drupal::entityQuery('koba_booking_booking')
      ->condition('booking_hash', $hash);
    $nids = $query->execute();

    if (!empty($nids)) {
      // Load booking.
      $booking = entity_load('koba_booking_booking', array_pop($nids));

      // Setup template for frontend.
      $build = array(
        '#theme' => 'booking_receipt',
        '#booking' => $booking,
      );

      return $build;
    }

    // Build sub-request with page not found.
    $subrequest = Request::create('/system/404', 'GET');
    $response = \Drupal::service('httpKernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);

    return $response;
  }

  /**
   * Pending action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionPending(Request $request, BookingInterface $koba_booking_booking) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'pending');
    $koba_booking_booking->save();

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }

  /**
   * Accept action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionAccept(Request $request, BookingInterface $koba_booking_booking) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'accepted');
    $koba_booking_booking->save();

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }

  /**
   * Refuse action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionRefuse(Request $request, BookingInterface $koba_booking_booking) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'refused');
    $koba_booking_booking->save();

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }

  /**
   * Cancel action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionCancel(Request $request, BookingInterface $koba_booking_booking = NULL) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'cancelled');
    $koba_booking_booking->save();

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }
}
