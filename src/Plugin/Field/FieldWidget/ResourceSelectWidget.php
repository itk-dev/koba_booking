<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Plugin\Field\FieldWidget\OptionsSelectWidget.
 */

namespace Drupal\koba_booking\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\koba_booking\Exception\ProxyException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Plugin implementation of the 'koba_resource_select' widget.
 *
 * @FieldWidget(
 *   id = "koba_resource_select",
 *   label = @Translation("Resource select list"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ResourceSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Textfield size: !size', array('!size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions() {
    if (!isset($this->options)) {
      $options = array();
      // Add an empty option if the widget needs one.
      if ($empty_label = $this->getEmptyLabel()) {
        $options = ['_none' => $empty_label] + $options;
      }

      // Get proxy service.
      $proxy = \Drupal::service('koba_booking.api.proxy');

      try {
        // Get resources from the proxy.
        $resources = $proxy->getResources();
        foreach ($resources as $resource) {
          $options[$resource->mail] = $resource->name;
        }
      }
      catch (ProxyException $exception) {
        drupal_set_message(t($exception->getMessage()), 'error');
      }

      // Ensure that the labels are HTML safe.
      array_walk_recursive($options, array($this, 'sanitizeLabel'));

      $this->options = $options;
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '_none';
    $element += array(
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#default_value' => array($value),
      '#multiple' => FALSE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (isset($this->multiple) && $this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!isset($this->required) || !$this->multiple) {
        return t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!isset($this->required) || !$this->required) {
        return t('- None -');
      }
      if (!isset($this->has_value) || !$this->has_value) {
        return t('- Select a value -');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }
}
