<?php

/**
 * @file
 * Contains \Drupal\koba_bookinf\Plugin\Action\BlockUser.
 */

namespace Drupal\koba_booking\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\koba_booking\BookingInterface;

/**
 * Blocks a user.
 *
 * @Action(
 *   id = "koba_booking_accept_action",
 *   label = @Translation("Accept the selected booking(s)"),
 *   type = "koba_booking_booking"
 * )
 */
class AcceptBooking extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($booking = NULL) {
    // For efficiency manually save the original booking before applying any
    // changes.
    $booking->original = clone $booking;
    $booking->set('booking_status', 'accepted');
    $booking->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, BookingInterface $booking = NULL, $return_as_object = FALSE) {
    $access = $object->status->access('edit', $booking, TRUE)
      ->andIf($object->access('update', $booking, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }
}
