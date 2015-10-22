<?php

/**
 * @file
 * Contains \Drupal\koba_booking\Plugin\views\argument\Uid.
 */

namespace Drupal\koba_booking\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("booking_id")
 */
class Id extends NumericArgument {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The user storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')->getStorage('koba_booking'));
  }

  /**
   * Override the behavior of title(). Get the name of the booking.
   *
   * @return array
   *    A list of booking names.
   */
  public function titleQuery() {
    return array_map(function($booking) {
      return SafeMarkup::checkPlain($booking->label());
    }, $this->storage->loadMultiple($this->value));
  }
}
