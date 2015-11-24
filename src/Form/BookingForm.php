<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Component\Utility\Crypt;
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

    // Get information for the current session to fill in default values. Only
    // the ones need in the form is set here, reset in page process functions.
    if ($this->getOperation() == 'add') {
      $defaults = \Drupal::service('session')->get('koba_booking_request');
      if (!empty($defaults)) {
        if (!empty($defaults['mail'])) {
          // Set mail.
          $form['booking_email']['widget'][0]['value']['#default_value'] = $defaults['mail'];

          // Set read-only.
          $form['booking_email']['widget']['0']['value']['#attributes']['readonly'] = 'readonly';
          $form['booking_email']['widget']['0']['value']['#attributes']['class'][] = 'booking--readonly';
        }
      }

      $config = \Drupal::config('koba_booking.settings');
      $why_email = check_markup($config->get('koba_booking.why_email'), 'editor_format');
      $why_title = check_markup($config->get('koba_booking.why_title'), 'editor_format');

      // Added link to "why" pop-up.
      $form['booking_email']['widget']['0']['value']['#description'] = $why_email;

      // Set name.
      $form['booking_name']['widget'][0]['value']['#default_value'] = $defaults['name'];

      // Set name to read-only and add link to description pop-up.
      $form['name']['widget']['0']['value']['#description'] = $why_title;
      $form['booking_name']['widget']['0']['value']['#attributes']['readonly'] = 'readonly';
      $form['booking_name']['widget']['0']['value']['#attributes']['class'][] = 'booking--readonly';
      $form['booking_association']['#prefix'] = '<div class="js-booking-type-toggle">';
      $form['booking_association']['#suffix'] = '</div>';

      // Add another theme function for the add booking form.
      $form['#theme'] = array('booking_add_booking');
    }
    else {
      // Hide fields on edit form.
      $form['booking_status']['#access'] = FALSE;
      $form['booking_public']['#access'] = FALSE;
      $form['booking_resource']['#access'] = FALSE;
      $form['booking_from_date']['#access'] = FALSE;
      $form['booking_to_date']['#access'] = FALSE;
      $form['booking_type']['#access'] = FALSE;
      $form['booking_association']['#access'] = FALSE;
      $form['langcode']['#access'] = FALSE;
    }

    // Attach overlays with more information about the fields.
    $form['#attached']['library'][] = 'koba_booking/angular';

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Validate the users name and mail fields.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Load configuration and session defaults.
    $config = \Drupal::config('koba_booking.settings');
    $defaults = \Drupal::service('session')->get('koba_booking_request');

    // Set message.
    $message_text = is_array($config->get('koba_booking.created_booking_message')) ? $config->get('koba_booking.created_booking_message')['value'] : $config->get('koba_booking.created_booking_message');
    drupal_set_message($message_text);

    // Generate hash value (based on name, mail, date).
    $hash = Crypt::hashBase64(Crypt::randomBytes(1024));

    // Get the entity.
    $entity = $this->getEntity();

    // Store hash value on the booking.
    $entity->booking_hash->setValue(array($hash));

    // On first save set the booking state.
    if ($entity->isNew()) {
      $entity->set('booking_status', 'request');

      // Store resource.
      $entity->booking_resource->setValue(array($defaults['resource']));

      // Store dates.
      $entity->booking_from_date->setValue(array($defaults['from']));
      $entity->booking_to_date->setValue(array($defaults['to']));
    }

    $entity->save();

    if ($this->getOperation() == 'add') {
      // Send mail with request received information.
      $mailer = \Drupal::service('koba_booking.mailer');
      $mailer->send('request', $entity);
    }

    // Redirect after submit.
    $form_state->setRedirect('koba_booking.receipt', array('hash' => $hash));

    // Remove booking information from session (comment out during tests).
    \Drupal::service('session')->remove('koba_booking_request');
  }
}
