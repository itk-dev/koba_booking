<?php
/**
 * @file
 * Contains Drupal\koba_booking\Form\BookingSettingsForm.
 */

namespace Drupal\koba_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class KobaBookingSettingsForm.
 * @package Drupal\koba_booking\Form
 * @ingroup koba_booking
 */
class AdminCancelledListForm extends FormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'koba_booking_cancelled_list';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get fields related to the booking entity..
    // @todo - Why the required field id input. Whichever field inputted the ouput seems to be the the same.
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('koba_booking_booking', 'booking_resource');

    // Set search period notice.
    $config = \Drupal::config('koba_booking.settings');
    if ($config->get('koba_booking.search_phase') > 0) {
      $search_period_message = t('Notice! Search period is active, remember to deactivate the setting when planning starts');
      drupal_set_message($search_period_message, $type = 'warning');
    }

    // Fetch all entities.
    $query = \Drupal::entityQuery('koba_booking_booking')
      ->condition('booking_status', 'cancelled', '=');
    $entities = $query->execute();

    // Create the filter group, and filter input fields.
    $form['filters'] = array(
      '#title' => $this->t('Filters'),
      '#type' => 'details',
      '#weight' => '0',
      '#open' => TRUE,
    );

    // The options are fetched from the booking entity field definitions.
    $options = $field_definitions['booking_resource']->getItemDefinition()->getSettings()['allowed_values'];
    $form['filters']['resource'] = array(
      '#type' => 'select',
      '#title' => t('Resource'),
      '#options' => $options,
      '#weight' => 1,
    );

    // Using autocomplete see KobaAutoCompleteController.
    $form['filters']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#maxlength' => 60,
      '#autocomplete_route_name' => 'koba_booking.autocomplete',
      '#autocomplete_route_parameters' => array(
        'entity_type' => 'koba_booking_booking',
        'field_name' => 'name',
      ),
      '#weight' => 1,
    );

    $form['filters']['author_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#maxlength' => 60,
      '#autocomplete_route_name' => 'koba_booking.autocomplete',
      '#autocomplete_route_parameters' => array(
        'entity_type' => 'koba_booking_booking',
        'field_name' => 'booking_name',
      ),
      '#weight' => 2,
    );

    $form['filters']['filter_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Filter'),
      '#weight' => 20,
      '#submit' => array('::filter'),
    );

    // Dropdown selections for bulk updating. (See entity definition for possible values) -This varies depending on which list we display.
    $form['actions_select'] = array(
      '#type' => 'select',
      '#title' => t('Update multiple bookings'),
      '#options' => array(
        'accepted'=> t('Accept'),
        'refused' => t('Refuse'),
        'cancelled' => t('Cancel'),
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

    // Define table header.
    $header = array(
      'title' => array(
        'data' => 'Title',
        'name' => 'name',
        'specifier' => 'name',
        'sort' => 'desc'
      ),
      'resource' => array(
        'data' => 'Resource',
        'name' => 'booking_resource',
        'specifier' => 'booking_resource'
      ),
      'name' => t('Name'),
      'date' => t('Date'),
      'time' => t('Time'),
      'operations' => t('Operations')
    );

    // Table display.
    $form['koba_pending_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#tableselect' => TRUE,
      '#weight' => '2',
    );

    // List items.
    foreach ($entities as $id) {
      $booking = entity_load('koba_booking_booking', $id);
      $edit_url = Url::fromRoute('entity.koba_booking_booking.edit_form', array('koba_booking_booking' => $id));
      $accept_url = Url::fromRoute('koba_booking.action_accept', array('koba_booking_booking' => $id));
      $refuse_url = Url::fromRoute('koba_booking.action_refuse', array('koba_booking_booking' => $id));
      $cancel_url = Url::fromRoute('koba_booking.action_cancel', array('koba_booking_booking' => $id));

      // Some table columns containing raw markup.
      $form['koba_pending_table'][$id]['title'] = array(
        '#markup' =>\Drupal::l($booking->name->value, $edit_url),
      );
      $form['koba_pending_table'][$id]['resource'] = array(
        '#markup' => $booking->booking_resource->value,
      );
      $form['koba_pending_table'][$id]['name'] = array(
        '#markup' => $booking->booking_name->value,
      );
      $form['koba_pending_table'][$id]['date'] = array(
        '#markup' => date('d/m/Y', $booking->booking_from_date->value),
      );
      $form['koba_pending_table'][$id]['time'] = array(
        '#markup' => date('H:i', $booking->booking_from_date->value) . ' - ' .date('H:i', $booking->booking_to_date->value),
      );
      $form['koba_pending_table'][$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['koba_pending_table'][$id]['operations']['#links']['edit'] = array (
        'title' => t('Edit'),
        'url' => $edit_url,
      );
      $form['koba_pending_table'][$id]['operations']['#links']['accept'] = array (
        'title' => t('Accept'),
        'url' => $accept_url,
      );
      $form['koba_pending_table'][$id]['operations']['#links']['refuse'] = array (
        'title' => t('Refuse'),
        'url' => $refuse_url,
      );
      $form['koba_pending_table'][$id]['operations']['#links']['cancel'] = array (
        'title' => t('Cancel'),
        'url' => $cancel_url,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($this->values);
    foreach ($values['koba_pending_table'] as $selected) {
      if ($selected > 0) {
        // Change the value of the status field.
        $entity = entity_load('koba_booking_booking', $selected);
        $entity->set('booking_status', $values['actions_select']);
        $entity->save();

        // Notify user of action performed.
        drupal_set_message('Changed status of ' . $entity->name->value . ' to ' . $values['actions_select'] . '.');
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