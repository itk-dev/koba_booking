<?php
/**
 * @file
 * Contains key/value storage for KOBA booking content.
 */

namespace Drupal\koba_booking\State;

use Drupal\Core\KeyValueStore\DatabaseStorage;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

class BookingContent extends DatabaseStorage {
  /**
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(SerializationInterface $serializer, Connection $connection) {
    parent::__construct('koba_booking.booking_content', $serializer, $connection);
  }
}
