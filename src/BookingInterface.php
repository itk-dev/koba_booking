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

  /**
   * Returns TRUE if the booking is new.
   *
   * @return bool
   *   Returns TRUE if the booking is new else FALSE.
   */
  public function isNew();

  /**
   * @return mixed
   */
  public function getCreatedTime();

  /**
   * @return mixed
   */
  public function getChangedTime();

  /**
   * Returns TRUE if the booking status is request.
   *
   * @return bool
   *   Returns TRUE if status is requested else FALSE.
   */
  public function isRequested();

  /**
   * Returns TRUE if the booking status is accepted.
   *
   * @return bool
   *   Returns TRUE if status is accepted else FALSE.
   */
  public function isAccepted();

  /**
   * Returns TRUE if the booking status is refused.
   *
   * @return bool
   *   Returns TRUE if status is refused else FALSE.
   */
  public function isRefused();

  /**
   * Returns TRUE if the booking status is pending.
   *
   * @return bool
   *   Returns TRUE if status is pending else FALSE.
   */
  public function isPending();

  /**
   * Returns TRUE if the booking status is cancelled.
   *
   * @return bool
   *   Returns TRUE if status is cancelled else FALSE.
   */
  public function isCancelled();

  /**
   * Returns TRUE if the booking status is cancelled.
   *
   * @return bool
   *   Returns TRUE if status is cancelled else FALSE.
   */
  public function isUnconfirmed();

  /**
   * Get room entity base on resource field.
   *
   * @return mixed
   *   Returns the node for the room or FALSE is non is set.
   */
  public function getRoomEntity();

  /**
   * @return bool
   */
  public function isPublic();
}
