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

/**
 * KobaBookingApiController.
 */
class KobaBookingApiController extends ControllerBase {
  /**
   * @return JsonResponse
   */
  public function resource(Request $request) {
    $resource = $request->query->get('res');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    return new JsonResponse(array(
      'resource' => $resource,
      'from' => $from,
      'to' => $to
    ), 200);
  }
}
