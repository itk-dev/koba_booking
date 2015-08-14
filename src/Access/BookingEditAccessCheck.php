<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Access\BookingEditAccessCheck.
 */

namespace Drupal\koba_booking\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for displaying booking/edit page.
 */
class BookingEditAccessCheck implements AccessInterface {

  /**
   * Confirm that a booking has been filled in.
   *
   * @return AccessResult
   *   Allowed or forbidden.
   */
  public function access() {
    $booking_status = FALSE;

    // Load information from the current session (selection from step 1 and WAYF info).
    $path = explode('/', $_SERVER['REQUEST_URI']);
    $booking_id = $path[2];

    if (is_numeric($booking_id)) {
      $booking_status = \Drupal::entityManager()->getStorage('koba_booking_booking')->load($booking_id)->booking_status->value;
    }


    if ($booking_status == 'request') {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }
}
