<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\koba_booking\BookingInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    $build = array(
      '#type' => 'markup',
      '#theme' => 'booking_calendar_page',
      '#attached' => array(
        'library' => array(
          'koba_booking/angular',
        ),
      ),
    );

    return $build;
  }

  /**
   * Booking receipt page.
   *
   * @return array
   *   Render array for calendar page.
   */
  public function receipt() {
    // Setup template for frontend.
    $build = array(
      '#type' => 'markup',
      '#theme' => 'booking_receipt',
    );

    return $build;
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
