<?php

/**
 * @file
 * Definition of Drupal\koba_booking\Plugin\views\filter\BookingAutocomplete.
 */

namespace Drupal\koba_booking\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Autocomplete for basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("booking_autocomplete")
 */
class BookingAutocomplete extends StringFilter {

  // Exposed filter options.
  var $alwaysMultiple = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['required'] = array('default' => FALSE, 'bool' => TRUE);
    $options['expose']['contains'] += array(
      'autocomplete_filter' => array('default' => 0),
      'autocomplete_route_name' => array('default' => ''),
      'autocomplete_min_chars' => array('default' => 0),
      'autocomplete_items' => array('default' => 10),
      'autocomplete_field' => array('default' => ''),
      'autocomplete_raw_suggestion' => array('default' => TRUE),
      'autocomplete_raw_dropdown' => array('default' => TRUE),
      'autocomplete_dependent' => array('default' => FALSE),
    );

    return $options;
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($this->canExpose() && !empty($form['expose'])) {
      $field_options_all = $this->view->display_handler->getFieldLabels();
      // Limit options to fields with the same name.
      foreach ($this->view->display_handler->getHandlers('field') as $id => $handler) {
        if ($handler->realField == $this->realField) {
          $field_options[$id] = $field_options_all[$id];
        }
      }
      if (empty($field_options)) {
        $field_options[''] = $this->t('Add some fields to view');
      }
      elseif (empty($this->options['expose']['autocomplete_field']) && !empty($field_options[$this->options['id']])) {
        $this->options['expose']['autocomplete_field'] = $this->options['id'];
      }

      // Build form elements for the right side of the exposed filter form
      $states = array(
        'visible' => array('
            :input[name="options[expose][autocomplete_filter]"]' => array('checked' => TRUE),
        ),
      );
      $form['expose'] += array(
         'autocomplete_filter' => array(
          '#type' => 'checkbox',
          '#title' => $this->t('Use Autocomplete'),
          '#default_value' => $this->options['expose']['autocomplete_filter'],
          '#description' => $this->t('Use Autocomplete for this filter.'),
        ),
        'autocomplete_route_name' => array(
          '#type' => 'textfield',
          '#title' => $this->t('The autocomplete callback to use'),
          '#default_value' => $this->options['expose']['autocomplete_route_name'],
          '#description' => $this->t('Enter route name to use callback'),
          '#states' => $states,
        ),
      );
    }
  }

  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $exposed = $form_state->get('exposed');
    if (!$exposed || empty($this->options['expose']['autocomplete_filter'])) {
      // It is not an exposed form or autocomplete is not enabled.
      return;
    }

    if (empty($form['value']['#type']) || $form['value']['#type'] !== 'textfield') {
      // Not a textfield.
      return;
    }

    // Add autocomplete path to the exposed textfield.
    $view_args = !empty($this->view->args) ? implode('||', $this->view->args) : 0;

    $form['value']['#autocomplete_route_name'] = $this->options['expose']['autocomplete_route_name'];
    $form['value']['#autocomplete_route_parameters'] = array(
      'entity_type' =>  $this->options['entity_type'],
      'field_name' => $this->options['entity_field'],
      'view_args' => $view_args,
    );
  }
}
