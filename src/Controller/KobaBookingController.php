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
class KobaBookingController extends ControllerBase {
  /**
   * @TODO: Add documentation.
   *
   * @return array
   */
  public function calendarPage() {
    // Setup template for frontend.
    $build = array(
      '#type' => 'markup',
      '#theme' => 'booking_calendar_page',
      '#attached' => array(
        'library' => array(
          'koba_booking/angular'
        ),
      ),
    );

    return $build;
  }

  /**
   * @TODO: Add documentation.
   *
   * @return array
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
   * @TODO: Add documentation.
   *
   * @param BookingInterface $koba_booking_booking
   * @param Request $request
   */
  public function actionAccept(BookingInterface $koba_booking_booking = NULL, Request $request) {
    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);

    // Change booking state.
    $koba_booking_booking->set('booking_status', 'accepted');
    $koba_booking_booking->save();
    $response->send();

    return;
  }

  /**
   * @TODO: Add documentation.
   *
   * @param BookingInterface $koba_booking_booking
   * @param Request $request
   */
  public function actionRefuse(BookingInterface $koba_booking_booking = NULL, Request $request) {
    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);

    // Change booking state.
    $koba_booking_booking->set('booking_status', 'refused');
    $koba_booking_booking->save();
    $response->send();

    return;
  }

  /**
   * @TODO: Add documentation.
   *
   * @param BookingInterface $koba_booking_booking
   * @param Request $request
   */
  public function actionCancel(BookingInterface $koba_booking_booking = NULL, Request $request) {
    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);

    // Change booking state.
    $koba_booking_booking->set('booking_status', 'cancelled');
    $koba_booking_booking->save();
    $response->send();

    return;
  }
}
