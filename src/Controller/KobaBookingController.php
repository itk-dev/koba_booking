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

  public function angular() {

    // Setup template for frontend.
    $build = array(
      '#type' => 'markup',
      '#theme' => 'angular_test',
      '#attached' => array(
        'library' =>  array(
          'koba_booking/angular'
        ),
      ),
    );

    return $build;
  }

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
