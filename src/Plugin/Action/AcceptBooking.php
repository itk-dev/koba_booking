<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Plugin\Action\AcceptBooking.
 */

namespace Drupal\koba_booking\Plugin\Action;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Http\Client;
use Drupal\Core\Session\AccountInterface;
use Drupal\koba_booking\BookingInterface;
use GuzzleHttp\Exception\RequestException;

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
  public function execute(BookingInterface $booking = NULL) {
    // Load configuration.
    $config = \Drupal::config('koba_booking.settings');

    // Get the room/resource.
    $room = entity_load('node', array_pop($booking->booking_resource->getValue())['target_id']);

    // Get Drupal http client.
    $client = new Client();

    // Build request.
    $requestBody = json_encode(array(
      'subject' => SafeMarkup::checkPlain(array_pop($booking->name->getValue())['value']),
      'description' => SafeMarkup::checkPlain(array_pop($booking->booking_message->getValue())['value']),
      'name' => SafeMarkup::checkPlain(array_pop($booking->booking_name->getValue())['value']),
      'mail' => SafeMarkup::checkPlain(array_pop($booking->booking_email->getValue())['value']),
      'phone' => SafeMarkup::checkPlain(array_pop($booking->booking_phone->getValue())['value']),
      'start_time' => array_pop($booking->booking_from_date->getValue())['value'],
      'end_time' => array_pop($booking->booking_to_date->getValue())['value'],
      'resource' => array_pop($room->field_resource->getValue())['value'],
      'client_booking_id' => array_pop($booking->uuid->getValue())['value'],
      'group_id' => 'default',
      'apikey' => $config->get('koba_booking.api_key'),
    ));

    try {
      // Send request to koba.
      $response = $client->post($config->get('koba_booking.path') . '/api/bookings', array(
        'body' => $requestBody,
      ));

      // Check response to ensure the proxy got it.
      if ($response->getStatusCode() == 201) {
        // For efficiency manually save the original booking before applying any
        // changes.
        $booking->original = clone $booking;
        $booking->set('booking_status', 'pending');
        $booking->save();
      }
      else {
        // Sent error message as response was not correct.
        drupal_set_message(t('Something happened at the booking service, that should not have happened. Please contact support.'), 'error');
      }
    }
    catch (RequestException $exception) {
      drupal_set_message(t('Something happened at the booking service, that should not have happened. Please contact support.'), 'error');
      drupal_set_message($exception->getMessage(), 'error');
      print_r($exception->getMessage());
    }
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
