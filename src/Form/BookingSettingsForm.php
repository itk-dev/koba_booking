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
    return 'dokk_resource_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('koba_booking.settings');
    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set API Key'),
      '#default_value' => $config->get('koba_booking.api_key')
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.koba_booking_booking.requests');
    $this->configFactory()->getEditable('koba_booking.settings')
      ->set('koba_booking.api_key', $form_state->getValue('api_key'))
      ->save();
  }
}