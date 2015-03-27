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
class AdminListForm extends FormBase {
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
      ->condition('booking_status', 'pending', '=');
      $entities = $query->execute();

    // Dropdown selections for bulk updating. (See entity definition for possible values)
    $form['actions_select'] = array(
      '#type' => 'select',
      '#title' => t('Update multiple bookings'),
      '#options' => array(
        'request' => t('Revert to request'),
        'accepted'=> t('Accept'),
        'denied' => t('Deny'),
        'surpassed' => t('Delete'),
      ),
      '#empty_value' => TRUE,
      '#description' => t('Change the status of multiple bookings.'),
      '#weight' => '0',
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
}