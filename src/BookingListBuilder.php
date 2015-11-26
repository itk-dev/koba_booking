<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Entity\Controller\ContentEntityExampleController.
 */

namespace Drupal\koba_booking;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use \Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a list controller for koba_booking entity.
 *
 * @ingroup koba_booking
 */
class BookingListBuilder extends EntityListBuilder {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new UserListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, QueryFactory $query_factory, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('koba_booking_booking');
    $entity_query->condition('booking_status', 'request');
    $entity_query->pager($this->limit);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $uids = $entity_query->execute();
    return $this->storage->loadMultiple($uids);
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      );
    }
    if ($entity->access('edit status accepted')) {
      $operations['accepted'] = array(
        'title' => $this->t('Accepted'),
        'weight' => 80,
        'url' => Url::fromRoute('koba_booking.action_accept', array('koba_booking_booking' => $entity->id->value)),
      );
    }

    if ($entity->access('edit status refused')) {
      $operations['refused'] = array(
        'title' => $this->t('Refuse'),
        'weight' => 90,
        'url' => Url::fromRoute('koba_booking.action_refuse', array('koba_booking_booking' => $entity->id->value)),
      );
    }

    if ($entity->access('edit status cancelled')) {
      $operations['cancelled'] = array(
        'title' => $this->t('Cancel'),
        'weight' => 100,
        'url' => Url::fromRoute('koba_booking.action_cancel', array('koba_booking_booking' => $entity->id->value)),
      );
    }

    if ($entity->access('update')) {
      $operations['confirmed'] = array(
        'title' => $this->t('Confirm'),
        'weight' => 100,
        'url' => Url::fromRoute('koba_booking.action_confirm', array('koba_booking_booking' => $entity->id->value)),
      );
    }

    return $operations;
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

    /* @var $entity \Drupal\koba_booking\Entity\Booking */
    $row['name'] = \Drupal::l($entity->name->value, $edit_url);
    $row['booking_resource'] = $entity->booking_resource->value;
    $row['booking_name'] = $entity->booking_name->value;
    $row['booking_date'] = date('d/m/Y', $entity->booking_from_date->value);
    $row['booking_time'] = date('H:i', $entity->booking_from_date->value) . '-' . date('H:i', $entity->booking_to_date->value);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    // If edit operation ensure that we are returned to the current page when
    // saved.
    $operations = parent::getOperations($entity);
    if (isset($operations['edit'])) {
      $destination = \Drupal::service('redirect.destination')->getAsArray();
      $operations['edit']['query'] = $destination;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['table'] = parent::render();

    $content = \Drupal::getContainer()->get('koba_booking.booking_content');
    if ($content->get('koba_booking.search_phase') > 0) {
      $search_period_message = t('Notice! Search period is active, remember to deactivate the setting when planning starts');
      drupal_set_message($search_period_message, $type = 'warning');
    }

    return $build;
  }
}
