<?php

/**
 * @file
 * Builds placeholder replacement tokens for booking entity.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_token_info().
 */
function koba_booking_token_info() {
  $types['booking'] = array(
    'name' => t("Bookings"),
    'description' => t("Tokens related to bookings."),
    'needs-data' => 'booking',
  );

  // Taxonomy term related variables.
  $booking['id'] = array(
    'name' => t("Booking ID"),
    'description' => t("The unique ID of the booking."),
  );
  $booking['name'] = array(
    'name' => t("Name"),
    'description' => t("The name of the booking."),
  );
  $booking['email'] = array(
    'name' => t("Email"),
    'description' => t("The email of the booking"),
  );
  $booking['status'] = array(
    'name' => t("Booking status"),
    'description' => t("The state of the booking."),
  );
  $booking['description'] = array(
    'name' => t("Booking description"),
    'description' => t("The booking description."),
  );
  $booking['resource'] = array(
    'name' => t("Booking resource"),
    'description' => t("The booked resource / room."),
  );

  $booking['date'] = array(
    'name' => t("Booking date"),
    'description' => t("The date of the booking"),
  );

  $booking['from_time'] = array(
    'name' => t("Booking from time"),
    'description' => t("The from time of the booking"),
  );
  $booking['to_time'] = array(
    'name' => t("Booking to time"),
    'description' => t("The to time of the booking"),
  );
  return array(
    'types' => $types,
    'tokens' => array('booking' => $booking),
  );
}

/**
 * Implements hook_tokens().
 *
 * Usage:
 *  // Load an entity.
 *  $booking = entity_load('koba_booking_booking', '1');
 *
 *  // Initiate the token service
 *  $token_service = \Drupal::token();
 *
 *  // Fetch current language for language options.
 *  $language_interface = \Drupal::languageManager()->getCurrentLanguage();
 *
 *  // Output the content with tokens replaced.
 *  $output = $token_service->replace($content->get('koba_email.email_pending_body'), array('booking' => $booking), array('langcode' => $language_interface->getId()));
 */
function koba_booking_tokens($type, $tokens, array $data = array(), array $options = array()) {

  $replacements = array();
  $sanitize = !empty($options['sanitize']);

  if ($type == 'booking' && !empty($data['booking'])) {
    $booking = $data['booking'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $booking->id();
          break;

        case 'name':
          $replacements[$original] = $sanitize ? Html::escape($booking->booking_name->value) : $booking->booking_name->value;
          break;

        case 'email':
          $replacements[$original] = $sanitize ? Html::escape($booking->booking_email->value) : $booking->booking_email->value;
          break;

        case 'status':
          $replacements[$original] = $sanitize ? Html::escape($booking->booking_status->value) : $booking->booking_status->value;
          break;

        case 'description':
          $replacements[$original] = $sanitize ? Html::escape($booking->booking_short_description->value) : $booking->booking_short_description->value;
          break;

        case 'resource':
          $replacements[$original] = $sanitize ? Html::escape($booking->booking_resource->value) : $booking->booking_resource->value;
          break;

        case 'date':
          $replacements[$original] = $sanitize ? Html::escape(date('d-m-Y', strtotime($booking->from_date->value))) : date('d-m-Y', strtotime($booking->from_date->value));
          break;

        case 'from_time':
          $replacements[$original] = $sanitize ? Html::escape(date('G:i', strtotime($booking->from_date->value))) : date('G:i', strtotime($booking->from_date->value));
          break;

        case 'to_time':
          $replacements[$original] = $sanitize ? Html::escape(date('G:i', strtotime($booking->to_date->value))) : date('G:i', strtotime($booking->to_date->value));
          break;
      }
    }
  }

  return $replacements;
}
