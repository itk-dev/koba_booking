<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Plugin\views\row\BookingRow.
 */

namespace Drupal\koba_booking\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * A row plugin which renders a user.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:koba_booking",
 * )
 */
class BookingRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode']['default'] = 'full';

    return $options;
  }

}
