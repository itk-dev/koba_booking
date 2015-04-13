<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingSettingsForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

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
    return 'dokk_resource_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('koba_booking.settings');
    $account = $this->currentUser();
    $tokens_description = t('Available tokens are: [booking:name], [booking:description], [booking:resource], [booking:status], [booking:date], [booking:from_time], [booking:to_time], [booking:email], [booking:id]');

    // Filter settings.
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
    );


    // Admin settings tab.
    $form['admin_settings'] = array(
      '#title' => $this->t('Admin settings'),
      '#type' => 'details',
      '#group' => 'settings',
      '#weight' => '0',
      '#access' => $account->hasPermission('configure booking api settings'),
    );

    $form['admin_settings']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set API Key'),
      '#default_value' => $config->get('koba_booking.api_key'),
    );

    $form['admin_settings']['admin_settings_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save admin settings'),
      '#weight' => 1,
      '#submit' => array('::admin_settings_submit'),
    );


    // General settings tab.
    $form['general_settings'] = array(
      '#title' => $this->t('General settings'),
      '#type' => 'details',
      '#group' => 'settings',
      '#weight' => '1',
    );

    $form['general_settings']['planning'] = array(
      '#prefix' => '<div class="messages messages--warning">Bookings possible until ' . date('d/m/Y', $config->get('koba_booking.last_booking_date')) . '</div>',
      '#title' => $this->t('Planning'),
      '#type' => 'details',
      '#weight' => '0',
      '#open' => TRUE,
    );

    $form['general_settings']['planning']['planning_state'] = array(
      '#type' => 'select',
      '#title' => t('Set the planning state'),
      '#options' => array(
        'first half year open'=> t('1st half year open'),
        'second half year open' => t('2nd half year open'),
        'request phase' => t('Request phase'),
        'planning phase' => t('Planning phase'),
      ),
      '#empty_value' => TRUE,
      '#description' => t('Change the planning state and which period is open for booking.</br>This setting needs to be continously updated, and operates within half years.</br>The booking periods can be opened before these dates by selecting above.'),
      '#weight' => '0',
      '#default_value' => $config->get('koba_booking.planning_state'),
    );

    $form['general_settings']['messages'] = array(
      '#title' => $this->t('Messages'),
      '#type' => 'details',
      '#weight' => '1',
      '#open' => TRUE,
    );

    $form['general_settings']['messages']['booking_created'] = array(
      '#type' => 'text_format',
      '#title' => t('Created booking'),
      '#description' => t('The message displayed to the user when the booking is created.') . ' ' . $tokens_description,
      '#default_value' => $config->get('koba_booking.created_booking_message')['value'],
    );

    $form['general_settings']['general_settings_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save general settings'),
      '#weight' => 2,
      '#submit' => array('::general_settings_submit'),
    );


    // Pending email settings.
    $form['pending_email'] = array(
      '#title' => $this->t('Booking pending email settings'),
      '#type' => 'details',
      '#weight' => '2',
      '#group' => 'settings',
    );

    $form['pending_email']['pending_email_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email title'),
      '#default_value' => $config->get('koba_booking.email_pending_title'),
    );

    $form['pending_email']['pending_email_body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('koba_booking.email_pending_body')['value'],
      '#description' => $tokens_description,
    );

    $form['pending_email']['pending_email_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save pending email settings'),
      '#weight' => 1,
      '#submit' => array('::pending_email_submit'),
    );


    // Accepted email settings.
    $form['accepted_email'] = array(
      '#title' => $this->t('Booking accepted email settings'),
      '#type' => 'details',
      '#weight' => '3',
      '#group' => 'settings',
    );

    $form['accepted_email']['accepted_email_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email title'),
      '#default_value' => $config->get('koba_booking.email_accepted_title'),
    );

    $form['accepted_email']['accepted_email_body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('koba_booking.email_accepted_body')['value'],
      '#description' => $tokens_description,
    );

    $form['accepted_email']['accepted_email_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save accepted email settings'),
      '#weight' => 1,
      '#submit' => array('::accepted_email_submit'),
    );


    // Denied email settings.
    $form['denied_email'] = array(
      '#title' => $this->t('Booking denied email settings'),
      '#type' => 'details',
      '#weight' => '3',
      '#group' => 'settings',
    );

    $form['denied_email']['denied_email_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email title'),
      '#default_value' => $config->get('koba_booking.email_denied_title'),
    );

    $form['denied_email']['denied_email_body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('koba_booking.email_denied_body')['value'],
      '#description' => $tokens_description,
    );

    $form['denied_email']['denied_email_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save denied email settings'),
      '#weight' => 1,
      '#submit' => array('::denied_email_submit'),
    );


    // Cancelled email settings.
    $form['cancelled_email'] = array(
      '#title' => $this->t('Booking cancelled email settings'),
      '#type' => 'details',
      '#weight' => '3',
      '#group' => 'settings',
    );

    $form['cancelled_email']['cancelled_email_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email title'),
      '#default_value' => $config->get('koba_booking.email_cancelled_title'),
    );

    $form['cancelled_email']['cancelled_email_body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('koba_booking.email_cancelled_body')['value'],
      '#description' => $tokens_description,
    );

    $form['cancelled_email']['cancelled_email_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save cancelled email settings'),
      '#weight' => 1,
      '#submit' => array('::cancelled_email_submit'),
    );

    return $form;
  }


  /**
   * Form submission handler for admin config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function admin_settings_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('Admin settings saved');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.api_key', $form_state->getValue('api_key'))
      ->save();
  }

  /**
   * Form submission handler for general config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function general_settings_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('General settings saved');
    // Set last possible date for booking.
    $last_booking_date = setLastBookingDate($form_state);
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.planning_state', $form_state->getValue('planning_state'))
      ->set('koba_booking.last_booking_date', $last_booking_date)
      ->set('koba_booking.created_booking_message', $form_state->getValue('booking_created'))
      ->save();
  }

  /**
   * Form submission handler for pending email config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function pending_email_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('Pending email settings saved');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.email_pending_title', $form_state->getValue('pending_email_title'))
      ->set('koba_booking.email_pending_body', $form_state->getValue('pending_email_body'))
      ->save();
  }


  /**
   * Form submission handler for accepted email config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function accepted_email_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('Accepted email settings saved');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.email_accepted_title', $form_state->getValue('accepted_email_title'))
      ->set('koba_booking.email_accepted_body', $form_state->getValue('accepted_email_body'))
      ->save();
  }


  /**
   * Form submission handler for denied email config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function denied_email_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('Denied email settings saved');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.email_denied_title', $form_state->getValue('denied_email_title'))
      ->set('koba_booking.email_denied_body', $form_state->getValue('denied_email_body'))
      ->save();
  }


  /**
   * Form submission handler for cancelled email config.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function cancelled_email_submit(array $form, FormStateInterface $form_state) {
    drupal_set_message('Cancelled email settings saved');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.email_cancelled_title', $form_state->getValue('cancelled_email_title'))
      ->set('koba_booking.email_cancelled_body', $form_state->getValue('cancelled_email_body'))
      ->save();
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

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
  $planning_state = $form_state->getValue('planning_state');

  // If currently 1st half year.
  if ($current_month < 7) {
    $last_booking_date = strtotime('30-6-' . date('Y'));
    // If the system is set to request phase or has opened for the next half year.
    if ($planning_state == 'second half year open' || $planning_state == 'request phase') {
      $last_booking_date = strtotime('31-12-' . date('Y'));
    }
  } else {
    // If currently 2nd half year.
    $last_booking_date = strtotime('31-12-' . date('Y'));
    // If the system is set to request phase or has opened for the next half year.
    if ($planning_state == 'first half year open' || $planning_state == 'request phase') {
      $last_booking_date = strtotime('30-06-' . date('Y', strtotime('+1 year')));
    }
  }

  return $last_booking_date;
}
