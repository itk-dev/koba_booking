<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the koba_booking entity edit forms.
 *
 * @ingroup koba_booking
 */
class BookingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\koba_booking\Entity\Booking */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Get information for the current session to fill in default values. Only
    // the ones need in the form is set here, reset in page process functions.
    if ($this->getOperation() == 'add') {
      $defaults = \Drupal::service('session')->get('koba_booking_request');
      if (!empty($defaults)) {
        // Set mail.
        if (isset($defaults['mail'])) {
          $form['booking_email']['widget'][0]['value']['#default_value'] = $defaults['mail'];
        }

        // Set name.
        $form['booking_name']['widget'][0]['value']['#default_value'] = implode(' ', $defaults['name']);
      }
    }

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    // Attach overlays with more information about the fields.
    $form['#attached']['library'][] = 'koba_booking/angular';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('koba_booking.settings');
    $message_text = is_array($config->get('koba_booking.created_booking_message')) ? $config->get('koba_booking.created_booking_message')['value'] : $config->get('koba_booking.created_booking_message');

    // Redirect after submit.
    $form_state->setRedirect('koba_booking.receipt', array(
      'hash' => '12345'));

    drupal_set_message($message_text);
    $entity = $this->getEntity();

    // On first save set the booking state.
    if ($form['#action'] == "/booking/add") {
      $entity->set('booking_status', 'request');
    }
    $entity->save();
  }
}


/**
 * Creates an array of date elements.
 * @param $input
 *  A string in the the expected format: d/m/Y
 *
 * @return array
 */
function getDate($input) {
  $date_elements = explode('/', $input);

  return $date_elements;
}


/**
 * Creates an array of time elements.
 * @param $input
 *  A string in the the expected format: H:i
 *
 * @return array
 */
function getTime($input) {
  $time_elements = explode(':', $input);

  return $time_elements;
}
