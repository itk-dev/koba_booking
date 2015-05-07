<?php

/**
 * @file
 * Definition of Drupal\koba_booking\Plugin\views\field\Link.
 */

namespace Drupal\koba_booking\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\EntityInterface;

/**
 * Field handler to present a link to the booking.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("koba_entity_link")
 */
class EntityLink extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['id'] = 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer users') || $account->hasPermission('access user profiles');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      $ref = $entity->booking_resource;
      $entity = array_pop($ref->referencedEntities());
      return $this->renderLink($entity, $values);
    }
  }

  /**
   * Alters the field to render a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\views\ResultRow $values
   *   The current row of the views result.
   *
   * @return string
   *   The actual rendered text (without the link) of this field.
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    $text = $entity->get('title')->value;

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $entity->urlInfo();

    return $text;
  }

}
