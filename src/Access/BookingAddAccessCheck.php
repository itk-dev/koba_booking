<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Access\BookingAddAccessCheck.
 */

namespace Drupal\koba_booking\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for displaying booking/add page.
 */
class BookingAddAccessCheck implements AccessInterface {

  /**
   * Confirm that a booking has been filled in.
   *
   * @return AccessResult
   */
  public function access() {
    // Load information from the current session (selection from step 1 and WAYF info).
    $defaults = \Drupal::service('session')->get('koba_booking_request');

    if (!empty($defaults)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }
}