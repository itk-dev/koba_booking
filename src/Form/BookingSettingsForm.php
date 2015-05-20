<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingSettingsForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;


/**
 * Class ContentEntityExampleSettingsForm.
 * @package Drupal\koba_booking\Form
 * @ingroup koba_booking
 */
class BookingSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'koba_booking_settings';
  }

  /**
   * {@inheritdoc}
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
      // Search period changed.
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

    // Booking status.
    $form['booking_status'] = array(
      '#prefix' => '<div class="messages messages--status">Bookings possible until ' . date('d/m/Y', $config->get('koba_booking.last_booking_date')) . '</div>',
      '#title' => $this->t('Booking status'),
      '#type' => 'details',
      '#weight' => '1',
      '#open' => TRUE,
    );

    $form['booking_status']['half_year'] = array(
      '#type' => 'radios',
      '#options' => array(
        'first half year open' => t('Booking open') . ' ' . $first_half_year,
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

    // Search period.
    $form['search_period_wrapper'] = array(
      '#title' => $this->t('Search period'),
      '#type' => 'details',
      '#weight' => '2',
      '#open' => TRUE,
    );

    $form['search_period_wrapper']['search_period'] = array(
      '#type' => 'checkbox',
      '#title' => t('Search period'),
      '#default_value' => $config->get('koba_booking.search_phase'),
      '#description' => t('When the search period is active, the users will be informed of their booking state after the planning phase, if the bookings are in the next half year period.'),
    );

    // Add booking wrapper.
    $form['create_booking_wrapper'] = array(
      '#title' => $this->t('Create booking pages'),
      '#type' => 'details',
      '#weight' => '3',
      '#open' => TRUE,
    );

    $form['create_booking_wrapper']['create_booking_title'] = array(
      '#title' => $this->t('Pages title'),
      '#type' => 'textfield',
      '#default_value' => $config->get('koba_booking.create_booking_title'),
      '#weight' => '1',
      '#open' => TRUE,
    );

    $form['create_booking_wrapper']['create_booking_description'] = array(
      '#title' => $this->t('Pages description'),
      '#type' => 'text_format',
      '#default_value' => $config->get('koba_booking.create_booking_description'),
      '#weight' => '2',
      '#open' => TRUE,
    );

    $fids = array();
    if (!empty($input)) {
      if (!empty($input['create_booking_top_image'])) {
        $fids[0] = $form_state->getValue('create_booking_top_image');
      }
    }
    else {
      $fids[0] = $config->get('koba_booking.create_booking_top_image', '');
    }

    $form['create_booking_wrapper']['create_booking_top_image'] = array(
      '#title' => $this->t('Top image'),
      '#type' => 'managed_file',
      '#default_value' => ($fids[0]) ? $fids : '',
      '#upload_location' => 'public://',
      '#weight' => '3',
      '#open' => TRUE,
      '#description' => t('The image used at the top of the create booking pages.'),
    );


    // Admin settings tab.
    $form['admin_settings'] = array(
      '#title' => $this->t('Admin settings'),
      '#type' => 'details',
      '#weight' => '4',
      '#access' => $account->hasPermission('configure booking api settings'),
      '#open' => TRUE,
    );

    $form['admin_settings']['add_booking_header'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set add booking header'),
      '#default_value' => $config->get('koba_booking.add_booking_header'),
      '#description' => t('The header to use after date/time has been selected in booking add form.</br>Can be used to enable wayf.'),
    );

    // Admin settings tab.
    $form['koba_settings'] = array(
      '#title' => $this->t('Booking proxy settings'),
      '#type' => 'details',
      '#weight' => '5',
      '#access' => $account->hasPermission('configure booking api settings'),
      '#open' => TRUE,
    );

    $form['koba_settings']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set API Key'),
      '#default_value' => $config->get('koba_booking.api_key'),
    );

    $form['koba_settings']['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set the path to KOBA'),
      '#default_value' => $config->get('koba_booking.path'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#weight' => '6',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Settings saved');

    // Fetch the file id previously saved.
    $config = $this->config('koba_booking.settings');
    $old_fid = $config->get('koba_booking.create_booking_top_image', '');

    // Load the file set in the form.
    $value = $form_state->getValue('create_booking_top_image');
    $form_fid = count($value) > 0 ? $value[0] : 0;
    $file = ($form_fid) ? File::load($form_fid) : FALSE;

    // If a file is set.
    if ($file) {
      $fid = $file->id();
      // Check if the file has changed.
      if ($fid != $old_fid) {

        // Remove old file.
        if ($old_fid) {
          removeFile($old_fid);
        }

        // Add file to file_usage table.
        \Drupal::service('file.usage')->add($file, 'koba_booking', 'user', '1');
      }
    }
    else {
      // If old file exists but no file set in form, remove old file.
      if ($old_fid) {
        removeFile($old_fid);
      }
    }

    // Set the last possible date for booking.
    $last_booking_date = setLastBookingDate($form_state);

    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.create_booking_title', $form_state->getValue('create_booking_title'))
      ->set('koba_booking.create_booking_description', $form_state->getValue('create_booking_description')['value'])
      ->set('koba_booking.create_booking_top_image', $file ? $file->id() : NULL)
      ->set('koba_booking.planning_state', $form_state->getValue('half_year'))
      ->set('koba_booking.search_phase', $form_state->getValue('search_period'))
      ->set('koba_booking.last_booking_date', $last_booking_date)
      ->set('koba_booking.api_key', $form_state->getValue('api_key'))
      ->set('koba_booking.path', $form_state->getValue('path'))
      ->set('koba_booking.add_booking_header', $form_state->getValue('add_booking_header'))
      ->save();
  }
}


/**
 * Creates a last date for possible bookings based on current month and which planning state the system is set to.
 *
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @return int
 *   Last booking date possible.
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
  }
  else {
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
 *   An array containing the which two half years are upcoming.
 */
function getHalfYears() {
  $half_years = array();
  $current_month = date('n');

  if ($current_month < 7) {
    $half_years[] = t('1st half year') . ' ' . date('Y');
    $half_years[] = t('2nd half year') . ' ' . date('Y');
  }
  else {
    $half_years[] = t('1st half year') . ' ' . date('Y', strtotime('+1 year'));
    $half_years[] = t('2nd half year') . ' ' . date('Y');
  }

  return $half_years;
}


/**
 * Deletes a a file from file usage table.
 *
 * @param int $fid
 *   The file id of the file to delete.
 */
function removeFile($fid) {
  // Load and delete old file.
  $file = File::load($fid);
  \Drupal::service('file.usage')->delete($file, 'koba_booking', 'user', '1', '1');
}
