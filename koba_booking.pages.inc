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
  $top_image_id = $config->get('koba_booking.create_booking_top_image', '');
  $file = ($top_image_id) ? File::load($top_image_id) : FALSE;

  // @todo caching af image.
  // Set variables
  $variables['title'] = $config->get('koba_booking.create_booking_title', '');
  $variables['top_img'] = ($file) ? $file->url() : '';
  $variables['manchet'] = $config->get('koba_booking.create_booking_description', '');
  $variables['submit_header'] = '/' . $config->get('koba_booking.add_booking_header', '');
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
  $top_image_id = $config->get('koba_booking.create_booking_top_image', '');
  $file = ($top_image_id) ? File::load($top_image_id) : FALSE;

  // @todo caching af image.
  // Set variables
  $variables['title'] = $config->get('koba_booking.create_booking_title', '');
  $variables['top_img'] = ($file) ? $file->url() : '';
  $variables['manchet'] = $config->get('koba_booking.create_booking_description', '');
}