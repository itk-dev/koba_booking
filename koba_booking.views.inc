<?php
/**
 * @file
 * Integration with Views.
 */

/**
 * Implements hook_views_data_alter().
 */
function koba_booking_views_data_alter(array &$data) {
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      // Extend String filter handler with autocomplete capabilities.
      if (isset($field_data['filter']['id']) && $field_data['filter']['id'] == 'string') {
        $data[$table_name][$field_name]['filter']['id'] = 'booking_autocomplete';
      }
    }
  }
}
