<?php

/**
 * @file
 * Angular page callbacks.
 */

use Drupal\file\Entity\File;
use Drupal\Core\Config;

/**
 * Prepares variables for booking calendar page.
 *
 * @param array $variables
 *   An array with the following elements:
 *    - title
 *    - top_img
 *    - manchet
 *    - submit_header : path
 */
function template_preprocess_booking_calendar_page(&$variables) {
  // Fetch module config settings.
  $config = \Drupal::config('koba_booking.settings');

  // Fetch header top file.
  $top_image_id = $config->get('koba_booking.create_booking_top_image');
  $file = ($top_image_id) ? File::load($top_image_id) : FALSE;

  // @todo caching af image.
  // Set variables
  $variables['title'] = $config->get('koba_booking.create_booking_title');
  $variables['top_img'] = ($file) ? $file->url() : '';
  $variables['manchet'] = check_markup($config->get('koba_booking.create_booking_description'), 'editor_format');
  $variables['submit_header'] = '/' . $config->get('koba_booking.add_booking_header');
  $variables['app_dir'] = '/' .  drupal_get_path('module', 'koba_booking') . '/js/app';
}

/**
 * Prepares variables for a receipt.
 *
 * @param array $variables
 *   An array with the following elements:
 *    - title
 *    - top_img
 *    - manchet
 */
function template_preprocess_booking_receipt(&$variables) {
  // Fetch module config settings.
  $config = \Drupal::config('koba_booking.settings');

  // Fetch header top file.
  $top_image_id = $config->get('koba_booking.create_booking_top_image');
  $file = ($top_image_id) ? File::load($top_image_id) : FALSE;

  // @todo caching af image.
  // Set variables
  $variables['title'] = $config->get('koba_booking.create_booking_title');
  $variables['top_img'] = ($file) ? $file->url() : '';
  $variables['manchet'] = check_markup($config->get('koba_booking.create_booking_description'), 'editor_format');

  // Extract information about the booking.
  $booking = $variables['booking'];
  $variables['id'] = $booking->id->value;

  // Load resource and set room information.
  $room = $booking->getRoomEntity();
  $variables['room'] = array(
    'name' => $room->title->value,
    'price' =>$room->field_price->value,
    'url' => $room->url(),
  );

  // Set date information.
  $variables['day'] = \Drupal::service('date.formatter')->format($booking->booking_from_date->value, 'dokk1_booking_dato');
  $variables['timeInterval'] = \Drupal::service('date.formatter')->format($booking->booking_from_date->value, 'dokk1_booking_time') . ' - ' . \Drupal::service('date.formatter')->format($booking->booking_to_date->value, 'dokk1_booking_time');

  // Set user information.
  $variables['name'] = \Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_name->value);
  $variables['mail'] = \Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_email->value);
  $variables['phone'] = \Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_phone->value);
  $variables['usage'] = koba_booking_room_usage()[\Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_usage->value)];
  $variables['type'] = koba_booking_type()[\Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_type->value)];
  $variables['association'] = \Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_association->value);
  $variables['message'] = \Drupal\Component\Utility\SafeMarkup::checkPlain($booking->booking_message->value);
}


/**
 * Implements hook_preprocess_booking_add_booking().
 */
function template_preprocess_booking_add_booking(&$variables) {
  // Load information form the current session (selection from step 1 and WAYF info).
  $defaults = \Drupal::service('session')->get('koba_booking_request');

  if (!empty($defaults)) {
    // Load "lokale" entity information.
    $defaults['room'] = \Drupal\node\Entity\Node::load($defaults['resource']);

    $variables['booking'] = array(
      'room' => array(
        'name' => $defaults['room']->title->value,
        'price' => $defaults['room']->field_price->value,
        'url' => $defaults['room']->url(),
      ),
      'day' => \Drupal::service('date.formatter')->format($defaults['from'], 'dokk1_booking_dato'),
      'timeIntreval' => \Drupal::service('date.formatter')->format($defaults['from'], 'dokk1_booking_time') . ' - ' . \Drupal::service('date.formatter')->format($defaults['to'], 'dokk1_booking_time'),
      'raw' => $defaults,
    );
  }

  // Fetch module config settings.
  $config = \Drupal::config('koba_booking.settings');

  // Fetch header top file.
  $top_image_id = $config->get('koba_booking.create_booking_top_image');
  $file = ($top_image_id) ? File::load($top_image_id) : FALSE;

  // Set variables
  $variables['title'] = $config->get('koba_booking.create_booking_title');
  $variables['top_img'] = ($file) ? $file->url() : '';
  $variables['search_phase'] = $config->get('koba_booking.search_phase');
}
