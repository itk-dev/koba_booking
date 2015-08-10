<?php
/**
 * @file
 * Contains services to communicate with the KOBA proxy.
 */

namespace Drupal\koba_booking\Service;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Http\Client;
use Drupal\koba_booking\BookingInterface;
use Drupal\koba_booking\Exception\ProxyException;
use GuzzleHttp\Exception\RequestException;

class Proxy {

  private $configuration = NULL;
  private $apikey = NULL;
  private $path = NULL;

  /**
   * Default construct.
   *
   * Load koba configuration.
   */
  public function __construct() {
    // Load booking configuration.
    $this->configuration = \Drupal::config('koba_booking.settings');
    $this->apikey = $this->configuration->get('koba_booking.api_key');
    $this->path = $this->configuration->get('koba_booking.path');
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
    // Build url to request booking at the proxy.
    $url = $this->path . '/api/resources/' . $resource_id . '/group/default/freebusy/from/' . $from . '/to/' . $to . '?apikey=' . $this->apikey;

    // Instantiates a new drupal http client.
    $client = new Client();

    try {
      $response = $client->get($url);
      $json = json_decode($response->getBody());
      return $json;
    }
    catch (RequestException $exception) {
      throw new ProxyException($exception->getMessage());
    }
  }

  /**
   * Loads available resources from the proxy.
   *
   * @return mixed
   *   Array with resource_id and name or throws an ProxyException on error.
   */
  public function getResources() {
    // Build request url.
    $url = $this->path . "/api/resources/group/default?apikey=" . $this->apikey;

    // Instantiates a new drupal http client.
    $client = new Client();

    try {
      $response = $client->get($url);
      $resources = json_decode($response->getBody());

      return $resources;
    }
    catch (RequestException $exception) {
      throw new ProxyException($exception->getMessage());
    }
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
    // Get the room/resource.
    $room = $booking->getRoomEntity();

    // Get Drupal http client.
    $client = new Client();

    // Build request.
    $requestBody = json_encode(array(
      'subject' => SafeMarkup::checkPlain($booking->name->value),
      'description' => SafeMarkup::checkPlain($booking->booking_message->value),
      'name' => SafeMarkup::checkPlain($booking->booking_name->value),
      'mail' => SafeMarkup::checkPlain($booking->booking_email->value),
      'phone' => SafeMarkup::checkPlain($booking->booking_phone->value),
      'start_time' => $booking->booking_from_date->value,
      'end_time' => $booking->booking_to_date->value,
      'resource' => $room->field_resource->value,
      'client_booking_id' => $booking->uuid->value,
      'group_id' => 'default',
      'apikey' => $this->apikey,
    ));

    try {
      // Send request to koba.
      $response = $client->post($this->path . '/api/bookings', array(
        'body' => $requestBody,
      ));

      // Check response to ensure the proxy got it.
      if ($response->getStatusCode() != 201) {
        // Sent error message as response was not correct.
        throw new ProxyException('Something happened at the booking service, that should not have happened. Please contact support.');
      }
    }
    catch (RequestException $exception) {
      throw new ProxyException($exception->getMessage());
    }

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
    // Get Drupal http client.
    $client = new Client();

    // Get unique id for the booking.
    $id = $booking->uuid->value;

    try {
      // Send request to koba.
      $response = $client->delete($this->path . '/api/bookings/group/default/apikey/'. $this->apikey . '/booking/' . $id);

      // Check response to ensure the proxy got it.
      if ($response->getStatusCode() != 202) {
        // Sent error message as response was not correct.
        throw new ProxyException('Something happened at the booking service, that should not have happened. Please contact support.');
      }
    }
    catch (RequestException $exception) {
      throw new ProxyException($exception->getMessage());
    }

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
    // Get Drupal http client.
    $client = new Client();

    // Get unique id for the booking.
    $id = $booking->uuid->value;

    try {
      // Send request to koba.
      $response = $client->get($this->path . '/api/bookings/confirm/group/default/apikey/'. $this->apikey . '/booking/' . $id);

      // Check response to ensure the proxy got it.
      if ($response->getStatusCode() != 202) {
        // Sent error message as response was not correct.
        throw new ProxyException('Something happened at the booking service, that should not have happened. Please contact support.');
      }
    }
    catch (RequestException $exception) {
      throw new ProxyException($exception->getMessage());
    }

    return TRUE;
  }
}
