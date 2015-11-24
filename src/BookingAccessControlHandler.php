<?php

/**
 * @file
 * Contains \Drupal\koba_booking\BookingAccessControlHandler
 */

namespace Drupal\koba_booking;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class BookingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'add':
        return AccessResult::allowedIfHasPermission($account, 'add booking entity');

      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view booking entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit booking entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete booking entity');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add booking entity');
  }

}
