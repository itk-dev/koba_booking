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
  $variables['manchet'] = $config->get('koba_booking.create_booking_description');
  $variables['submit_header'] = '/' . $config->get('koba_booking.add_booking_header');
  $variables['app_dir'] = '/' .  drupal_get_path('module', 'koba_booking') . '/app';
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
  $variables['manchet'] = $config->get('koba_booking.create_booking_description');

  // Extract information about the booking.
  $booking = $variables['booking'];
  $variables['id'] = array_pop($booking->id->getValue())['value'];

  // Load resource and set room information.
  $room = entity_load('node', array_pop($booking->booking_resource->getValue())['target_id']);
  $variables['room'] = array(
    'name' => array_pop($room->title->getValue())['value'],
    'price' => array_pop($room->field_price->getValue())['value'],
    'url' => $room->url(),
  );

  // Set date information.
  $variables['day'] = format_date(array_pop($booking->booking_from_date->getValue())['value'], 'dokk1_booking_dato');
  $variables['timeInterval'] = format_date(array_pop($booking->booking_from_date->getValue())['value'], 'dokk1_booking_time') . ' - ' . format_date(array_pop($booking->booking_to_date->getValue())['value'], 'dokk1_booking_time');

  // Set user information.
  $variables['name'] = \Drupal\Component\Utility\SafeMarkup::checkPlain(array_pop($booking->booking_name->getValue())['value']);
  $variables['mail'] = \Drupal\Component\Utility\SafeMarkup::checkPlain(array_pop($booking->booking_email->getValue())['value']);
  $variables['phone'] = \Drupal\Component\Utility\SafeMarkup::checkPlain(array_pop($booking->booking_phone->getValue())['value']);
  $variables['type'] = \Drupal\Component\Utility\SafeMarkup::checkPlain(array_pop($booking->booking_usage->getValue())['value']);
  $variables['message'] = \Drupal\Component\Utility\SafeMarkup::checkPlain(array_pop($booking->booking_message->getValue())['value']);
}

/**
 * Implements hook_preprocess_booking_add_booking().
 *
 * @TODO: There must be a better way to get values out of a field.
 *
 * @param $variables
 */
function template_preprocess_booking_add_booking(&$variables) {
  $defaults = \Drupal::service('session')->get('koba_booking_request');
  if (!empty($defaults)) {
    // Load "lokale" entity information.
    $defaults['room'] = entity_load('node', $defaults['resource']);

    $variables['booking'] = array(
      'room' => array(
        'name' => array_pop($defaults['room']->title->getValue())['value'],
        'price' => array_pop($defaults['room']->field_price->getValue())['value'],
        'url' => $defaults['room']->url(),
      ),
      'day' => format_date($defaults['from'], 'dokk1_booking_dato'),
      'timeIntreval' => format_date($defaults['from'], 'dokk1_booking_time') . ' - ' . format_date($defaults['to'], 'dokk1_booking_time'),
      'raw' => $defaults,
    );
  }
}
