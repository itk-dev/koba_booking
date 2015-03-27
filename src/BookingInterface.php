<?php
/**
 * @file
 * Contains \Drupal\koba_booking\BookingInterface.
 */

namespace Drupal\koba_booking;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Booking entity.
 * @ingroup koba_booking
 */
interface BookingInterface extends ContentEntityInterface, EntityOwnerInterface {

}
