<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Entity\Controller\ContentEntityExampleController.
 */

namespace Drupal\koba_booking\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;


/**
 * Provides a list controller for koba_booking entity.
 *
 * @ingroup koba_booking
 */
class BookingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['table'] = parent::render();
    $config = \Drupal::config('koba_booking.settings');
    if ($config->get('koba_booking.search_phase') > 0) {
      $search_period_message = t('Notice! Search period is active, remember to deactivate the setting when planning starts');
      drupal_set_message($search_period_message, $type = 'warning');
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the booking list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['name'] = $this->t('Title');
    $header['booking_resource'] = $this->t('Resource');
    $header['booking_name'] = $this->t('Name');
    $header['booking_date'] = $this->t('Date');
    $header['booking_time'] = $this->t('Time');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * Adding content to the table.
   * Filtering/splitting up content based on booking status field.
   */
  public function buildRow(EntityInterface $entity) {
    // Show bookings depending on path.
    $edit_url = Url::fromRoute('entity.koba_booking_booking.edit_form', array('koba_booking_booking' => $entity->id()));

    // Only print bookings in pending states.
    if ($entity->booking_status->value != 'pending') {
      return;
    }

    /* @var $entity \Drupal\koba_booking\Entity\Booking */
    $row['name'] = \Drupal::l($entity->name->value, $edit_url);
    $row['booking_resource'] = $entity->booking_resource->value;
    $row['booking_name'] = $entity->booking_name->value;
    $row['booking_date'] = date('d/m/Y', $entity->booking_from_date->value);
    $row['booking_time'] = date('H:i', $entity->booking_from_date->value) . '-' . date('H:i', $entity->booking_to_date->value);

    return $row + parent::buildRow($entity);
  }
}
