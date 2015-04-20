<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingSettingsForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentEntityExampleSettingsForm.
 * @package Drupal\koba_booking\Form
 * @ingroup koba_booking
 */
class BookingSettingsForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'koba_booking_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('koba_booking.settings');
    $account = $this->currentUser();

    // Set first and second half year strings.
    $half_years  = getHalfYears();
    $first_half_year = $half_years[0];
    $second_half_year = $half_years[1];

    // Print message.
    $input = $form_state->getUserInput();
    $search_period_message = t('Notice! Search period is active, remember to deactivate the setting when planning starts');

    // If form changed.
    if (!empty($input)) {
      // Search period changed
      if ($input['search_period'] == 1) {
        drupal_set_message($search_period_message, $type = 'warning');
      }
    }
    // If form did not change.
    else {
      // If Search phase value is true.
      if ($config->get('koba_booking.search_phase') > 0) {
        drupal_set_message($search_period_message, $type = 'warning');
      }
    }

    // Admin settings tab.
    $form['admin_settings'] = array(
      '#title' => $this->t('Admin settings'),
      '#type' => 'details',
      '#weight' => '1',
      '#access' => $account->hasPermission('configure booking api settings'),
      '#open' => TRUE,
    );

    $form['admin_settings']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set API Key'),
      '#default_value' => $config->get('koba_booking.api_key'),
    );

    $form['booking_status'] = array(
      '#prefix' => '<div class="messages messages--status">Bookings possible until ' . date('d/m/Y', $config->get('koba_booking.last_booking_date')) . '</div>',
      '#title' => $this->t('Booking status'),
      '#type' => 'details',
      '#weight' => '0',
      '#open' => TRUE,
    );

    $form['booking_status']['half_year'] = array(
      '#type' => 'radios',
      '#options' => array(
        'first half year open'=> t('Booking open') . ' ' . $first_half_year,
        'second half year open' => t('Booking open') . ' ' . $second_half_year,
      ),
      '#empty_value' => TRUE,
      '#weight' => '0',
      '#default_value' => $config->get('koba_booking.planning_state'),
    );

    $form['booking_status']['half_year']['first half year open'] = array(
      '#type' => 'radio',
      '#description' => t('Users can book until end of June'),
    );

    $form['booking_status']['half_year']['second half year open'] = array(
      '#type' => 'radio',
      '#description' => t('Users can book until end of December'),
    );

    $form['search_period_wrapper'] = array(
      '#title' => $this->t('Search period'),
      '#type' => 'details',
      '#weight' => '0',
      '#open' => TRUE,
    );

    $form['search_period_wrapper']['search_period'] = array(
      '#type' => 'checkbox',
      '#title' => t('Search period'),
      '#default_value' => $config->get('koba_booking.search_phase'),
      '#description' => t('When the search period is active, the users will be informed of their booking state after the planning phase, if the bookings are in the next half year period.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#weight' => '3',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Settings saved');
    $last_booking_date = setLastBookingDate($form_state);
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.planning_state', $form_state->getValue('half_year'))
      ->set('koba_booking.search_phase', $form_state->getValue('search_period'))
      ->set('koba_booking.last_booking_date', $last_booking_date)
      ->set('koba_booking.api_key', $form_state->getValue('api_key'))
      ->save();
  }
}


/**
 * Creates a last date for possible bookings based on current month and which planning state the system is set to.
 *
 * @param $form_state
 *   The current state of the form.
 * @return int
 */
function setLastBookingDate($form_state) {
  $current_month = date('n');
  $planning_state = $form_state->getValue('half_year');

  // If currently 1st half year.
  if ($current_month < 7) {
    $last_booking_date = strtotime('30-6-' . date('Y'));
    // If the system is set to request phase or has opened for the next half year.
    if ($planning_state == 'second half year open') {
      $last_booking_date = strtotime('31-12-' . date('Y'));
    }
  } else {
    // If currently 2nd half year.
    $last_booking_date = strtotime('31-12-' . date('Y'));
    // If the system is set to request phase or has opened for the next half year.
    if ($planning_state == 'first half year open') {
      $last_booking_date = strtotime('30-06-' . date('Y', strtotime('+1 year')));
    }
  }

  return $last_booking_date;
}


/**
 * Creates an array of current and next half year strings depending on current time.
 *
 * @return array
 */
function getHalfYears() {
  $half_years = array();
  $current_month = date('n');

  if ($current_month < 7) {
    $half_years[] = t('1st half year') . ' ' . date('Y');
    $half_years[] = t('2nd half year') . ' ' . date('Y');
  } else {
    $half_years[] = t('1st half year') . ' ' . date('Y', strtotime('+1 year'));
    $half_years[] = t('2nd half year') . ' ' . date('Y');
  }

  return $half_years;
}
