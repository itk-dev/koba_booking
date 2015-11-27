<?php
/**
 * @file
 * Contains a mock of the Proxy service.
 */

namespace Drupal\koba_booking\Service;

use Drupal\koba_booking\BookingInterface;

class ProxyMock {
  /**
   * Default construct.
   */
  public function __construct() {
  }

  /**
   * Get bookings for a resource in a give time interval.
   *
   * @param $resource_id
   *   The resource id (mail address) at the proxy.
   * @param $from
   *   The unix timestamp from.
   * @param $to
   *   The unix timestamp to.
   *
   * @return mixed
   *   Returns the booking information for the resource or throws ProxyException
   *   on error.
   */
  public function getResourceBookings($resource_id, $from, $to) {
    // Hack for testing.
    $now = time();
    return array(
        (object) array(
          "start" => $now - $now % 3600,
          "end" => $now - $now % 3600 + 3600
        ),
        (object) array(
          "start" => $now - $now % 3600 + 3600 * 2,
          "end" => $now - $now % 3600 + 3600 * 3
        ),
    );
  }

  /**
   * Loads available resources from the proxy.
   *
   * @return mixed
   *   Array with resource_id and name or throws an ProxyException on error.
   */
  public function getResources() {
    return array(
      (object) array(
        'mail' => "DOKK1-lokale-test1@aarhus.dk",
        'name' => "DOKK1-lokale-test1",
        'alias' => "Lokale test 1",
        'display' => "FREE_BUSY",
      )
    );
  }

  /**
   * Send booking request to the proxy.
   *
   * @param \Drupal\koba_booking\BookingInterface $booking
   *  The booking to build request based on.
   *
   * @return bool
   *   If booking was sent TRUE else ProxyException is thrown on error.
   */
  public function sendBooking(BookingInterface $booking) {
    return TRUE;
  }

  /**
   * Send delete/cancel request.
   *
   * @param \Drupal\koba_booking\BookingInterface $booking
   *   The booking to delete.
   *
   * @return bool
   *   If booking was sent TRUE else ProxyException is thrown on error.
   */
  public function deleteBooking(BookingInterface $booking) {
    return TRUE;
  }

  /**
   * Send confirm booking request.
   *
   * @param \Drupal\koba_booking\BookingInterface $booking
   *   The booking to confirm.
   *
   * @return bool
   *   If booking was sent TRUE else ProxyException is thrown on error.
   */
  public function confirmBooking(BookingInterface $booking) {
    return TRUE;
  }
}
