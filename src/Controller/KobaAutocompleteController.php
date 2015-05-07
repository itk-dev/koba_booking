<?php
/**
* @file
* Contains \Drupal\koba_booking\Controller\KobaBookingController.
* Blindly copied from the taxonomy module.
*/

namespace Drupal\koba_booking\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns autocomplete responses for koba booking.
 */
class KobaAutocompleteController implements ContainerInjectionInterface {

  /**
   * Booking entity query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $bookingEntityQuery;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new \Drupal\koba_booking\Controller\KobaAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $booking_entity_query
   *   The entity query service.
   * @param \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  public function __construct(QueryInterface $booking_entity_query, EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')->get('koba_booking_booking'),
      $container->get('entity.manager')
    );
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $entity_type
   *   The entity_type.
   * @param string $field_name
   *   The name of the booking field.
   * @param array $view_args
   *   Arguments from view.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   When valid field name is specified, a JSON response containing the
   *   autocomplete suggestions for a booking. Otherwise a normal response
   *   containing an error message.
   */
  public function autocomplete(Request $request, $entity_type, $field_name, $view_args = array()) {
    $input_value = $request->query->get('q');

    // Make sure the field exists and is a taxonomy field.
    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type);

    if (!isset($field_storage_definitions[$field_name])) {
      // Error string. The JavaScript handler will realize this is not JSON and
      // will display it as debugging information.
      return new Response(t('Taxonomy field @field_name not found.', array('@field_name' => $field_name)), 403);
    }

    $matches = array();
    if ($input_value != '') {
      $matches = $this->getMatchingValues($field_name, $input_value);
    }

    return new JsonResponse($matches);
  }

  /**
   * Gets bookings which matches some typed value.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $input_value
   *   The full typed tags string.
   *
   * @return array
   *   Returns an array of matching booking names.
   */
  protected function getMatchingValues($field_name, $input_value) {
    $matches = array();

    switch ($field_name) {
      case 'booking_name':
        // Select rows that match by booking_name.
        $booking_ids = \Drupal::entityQuery('koba_booking_booking')
          ->condition('booking_name', $input_value, 'CONTAINS')
          ->range(0, 10)
          ->execute();
        break;

      case 'name':
        // Select rows that match by title.
        $booking_ids = \Drupal::entityQuery('koba_booking_booking')
          ->condition('name', $input_value, 'CONTAINS')
          ->range(0, 10)
          ->execute();
        break;
    }

    if (!empty($booking_ids)) {
      foreach ($booking_ids as $id) {
        $booking = entity_load('koba_booking_booking', $id);
        $matches[] = $booking->$field_name->value;
      }
    }
    return $matches;
  }
}
