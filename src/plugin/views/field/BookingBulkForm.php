<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\field\UserBulkForm.
 */

namespace Drupal\koba_booking\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\koba_booking\BookingInterface;
use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("koba_booking_bulk_form")
 */
class BookingBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   *
   * Provide a more useful title to improve the accessibility.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $booking = $result->_entity;
        if ($booking instanceof BookingInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update the booking %name', array('%name' => $booking->label()));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No booking selected.');
  }

}
