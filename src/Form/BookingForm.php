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

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    $form['#attached']['library'][] = 'koba_booking/angular';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Get input values.
    $values = $form_state->getValue($this->values);
    $from_date = $values['booking_from_date']['0']['value'];

    // Explode date and time input.
    $date_elements = getDate($from_date);
    $from_time_elements = getTime($values['booking_from_time']);
    $to_time_elements = getTime($values['booking_to_time']);

    // Create timestamps.
    $stamp_from = mktime($from_time_elements['0'], $from_time_elements['1'], '00', $date_elements['1'], $date_elements['0'], $date_elements['2']);
    $stamp_to = mktime($to_time_elements['0'], $to_time_elements['1'], '00', $date_elements['1'], $date_elements['0'], $date_elements['2']);

    // Redirect after submit.
    $form_state->setRedirect('koba_booking.booking');
    $entity = $this->getEntity();

    // Save data.
    $entity->set('booking_from_date', $stamp_from);
    $entity->set('booking_to_date', $stamp_to);

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
