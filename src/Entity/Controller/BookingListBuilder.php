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
    $header['booking_short_description'] = $this->t('Description');
    $header['booking_name'] = $this->t('Name');
    $header['booking_email'] = $this->t('Email');
    $header['booking_resource'] = $this->t('Resource');
    $header['booking_status'] = $this->t('Booking Status');
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
    $current_uri = \Drupal::request()->getRequestUri();
    $edit_url = Url::fromRoute('entity.koba_booking_booking.edit_form', array('koba_booking_booking' => $entity->id()));

    switch ($current_uri) {
      case '/admin/booking/requests':
        if ($entity->booking_status->value != 'request') {
          return;
        }
        break;
      case '/admin/booking/accepted':
        if ($entity->booking_status->value != 'accepted') {
          return;
        }
        break;
      case '/admin/booking/denied':
        if ($entity->booking_status->value != 'denied') {
          return;
        }
        break;
      case '/admin/booking/history':
        if ($entity->booking_status->value != 'surpassed') {
          return;
        }
        break;
    }

    /* @var $entity \Drupal\dokk_resource\Entity\Booking */
    $row['booking_short_description'] = \Drupal::l($entity->booking_short_description->value, $edit_url);
    $row['booking_name'] = $entity->booking_name->value;
    $row['booking_email'] = $entity->booking_email->value;
    $row['booking_resource'] = $entity->booking_resource->value;
    $row['booking_status'] = $entity->booking_status->value;
    return $row + parent::buildRow($entity);
  }
}
