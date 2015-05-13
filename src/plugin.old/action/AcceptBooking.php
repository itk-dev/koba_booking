<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Plugin\Action\AcceptBooking.
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
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->access('edit status accepted', $account, TRUE);

    return $return_as_object ? $access : $access->isAllowed();
  }
}
