<?php
/**
 * @file
 * Contains Drupal\dokk_resource\Form\BookingSettingsForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;

/**
 * Class DokkResourceSettingsForm.
 * @package Drupal\dokk_resource\Form
 * @ingroup dokk_resource
 */
class AdminRequestListForm extends FormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'koba_booking_admin_list';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::entityQuery('koba_booking_booking')
      ->condition('booking_status', 'request', '=');
      $entities = $query->execute();

    // Dropdown selections for bulk updating. (See entity definition for possible values)
    $form['actions_select'] = array(
      '#type' => 'select',
      '#title' => t('Update multiple bookings'),
      '#options' => array(
        'accepted'=> t('Accept'),
        'denied' => t('Deny'),
        'surpassed' => t('Delete'),
      ),
      '#empty_value' => TRUE,
      '#description' => t('Change the status of multiple bookings.'),
      '#weight' => '0',
    );

    $form['filters'] = array(
      '#title' => $this->t('Filters'),
      '#type' => 'details',
      '#weight' => '0',
      '#open' => TRUE,
    );

    $form['filters']['author_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#maxlength' => 60,
      '#autocomplete_route_name' => 'koba_booking.autocomplete',
      '#autocomplete_route_parameters' => array(
        'entity_type' => 'koba_booking_booking',
        'field_name' => 'booking_email',
      ),
      '#weight' => -1,
    );

    $form['filters']['email_filter_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Filter'),
      '#weight' => 20,
      '#submit' => array('::filter'),
    );

    // Perform selected action.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#tableselect' => TRUE,
      '#weight' => '1',
    );

    // Table display.
    $form['koba_pending_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Description'), t('Name'), t('Email'), t('Resource'), t('Status'), t('Operations')),
      '#tableselect' => TRUE,
      '#weight' => '2',
    );

    // List items.
    foreach ($entities as $id) {
      $booking = entity_load('koba_booking_booking', $id);
      $edit_url = Url::fromRoute('entity.koba_booking_booking.edit_form', array('koba_booking_booking' => $id));

      // Some table columns containing raw markup.
      $form['koba_pending_table'][$id]['descriptions'] = array(
        '#markup' => $booking->booking_short_description->value,
      );
      $form['koba_pending_table'][$id]['name'] = array(
        '#markup' => $booking->booking_name->value,
      );
      $form['koba_pending_table'][$id]['email'] = array(
        '#markup' => $booking->booking_email->value,
      );
      $form['koba_pending_table'][$id]['resource'] = array(
        '#markup' => $booking->booking_resource->value,
      );
      $form['koba_pending_table'][$id]['status'] = array(
        '#markup' => $booking->booking_status->value,
      );
      $form['koba_pending_table'][$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['koba_pending_table'][$id]['operations']['#links']['edit'] = array (
        'title' => t('Edit'),
        'url' => $edit_url,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('koba_booking.pending');
    $values = $form_state->getValue($this->values);
    foreach ($values['koba_pending_table'] as $selected) {
      if ($selected > 0) {
        // Change the value of the status field.
        $entity = entity_load('koba_booking_booking', $selected);
        $entity->set('booking_status', $values['actions_select']);
        $entity->save();

        // Notify user of action performed.
        drupal_set_message('Changed status of ' . $entity->booking_short_description->value . ' to ' . $values['actions_select'] . '.');
      }
    }
  }


  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function filter(array $form, FormStateInterface $form_state) {
    drupal_set_message('Working');
  }
}