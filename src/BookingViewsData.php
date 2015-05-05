<?php

/**
 * @file
 * Contains \Drupal\koba_booking\BookingViewsData.
 */

namespace Drupal\koba_booking;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the user entity type.
 */
class BookingViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['booking']['table']['base']['access query tag'] = 'user_access';
    $data['booking']['table']['wizard_id'] = 'bookings';

    $data['booking']['booking_status']['field']['id'] = 'standard';
    $data['booking']['booking_group_id']['field']['id'] = 'standard';
    $data['booking']['booking_exchange_id']['field']['id'] = 'standard';
    $data['booking']['booking_change_key']['field']['id'] = 'standard';
    $data['booking']['booking_name']['field']['id'] = 'standard';
    $data['booking']['booking_email']['field']['id'] = 'standard';
    $data['booking']['booking_phone']['field']['id'] = 'standard';
    $data['booking']['booking_usage']['field']['id'] = 'standard';

    $data['booking']['booking_resource']['field']['id'] = 'standard';

    $data['booking']['booking_from_date']['field']['id'] = 'date';
    $data['booking']['booking_from_date']['field']['argument'] = 'date';
    $data['booking']['booking_from_date']['field']['filter'] = 'date';
    $data['booking']['booking_from_date']['field']['sort'] = 'date';

    $data['booking']['booking_to_date']['field']['id'] = 'date';
    $data['booking']['booking_to_date']['field']['argument'] = 'date';
    $data['booking']['booking_to_date']['field']['filter'] = 'date';
    $data['booking']['booking_to_date']['field']['sort'] = 'date';

    $data['booking']['booking_message']['field']['id'] = 'standard';
    $data['booking']['booking_usage']['field']['id'] = 'standard';

    $data['booking']['name']['field']['id'] = 'standard';

    $data['booking']['booking_bulk_form'] = array(
      'title' => t('Bulk update'),
      'help' => t('Add a form element that lets you run operations on multiple bookings.'),
      'field' => array(
        'id' => 'booking_bulk_form',
      ),
    );

    return $data;
  }

}
