<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Entity\ContentEntityExample.
 */

namespace Drupal\koba_booking\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\koba_booking\BookingInterface;
use Drupal\user\UserInterface;

/**
 * Defines the ContentEntityExample entity.
 *
 * @ingroup koba_booking
 *
 * @ContentEntityType(
 *   id = "koba_booking_booking",
 *   label = @Translation("Booking"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\koba_booking\BookingListBuilder",
 *     "access" = "Drupal\koba_booking\BookingAccessControlHandler",
 *     "views_data" = "Drupal\koba_booking\BookingViewsData",
 *     "form" = {
 *       "add" = "Drupal\koba_booking\Form\BookingForm",
 *       "edit" = "Drupal\koba_booking\Form\BookingForm",
 *       "delete" = "Drupal\koba_booking\Form\BookingDeleteForm",
 *     },
 *
 *   },
 *   translatable = FALSE,
 *   admin_permission = "administer koba_booking entity",
 *   base_table = "booking",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *  links = {
 *     "canonical" = "/booking/{koba_booking_booking}",
 *     "edit-form" = "/booking/{koba_booking_booking}/edit",
 *     "delete-form" = "/booking/{koba_booking_booking}/delete",
 *     "collection" = "/booking/list"
 *   },
 *   field_ui_base_route = "koba_booking.booking_settings",
 *   common_reference_target = TRUE
 * )
 */
class Booking extends ContentEntityBase implements BookingInterface {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || $this->id() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isPending() {
    return $this->get('booking_status')->value == 'pending';
  }

  /**
   * {@inheritdoc}
   */
  public function isRefused() {
    return $this->get('booking_status')->value == 'refused';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccepted() {
    return $this->get('booking_status')->value == 'accepted';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequested() {
    return $this->get('booking_status')->value == 'requested';
  }

  /**
   * {@inheritdoc}
   */
  public function isCancelled() {
    return $this->get('booking_status')->value == 'cancelled';
  }

  /**
   * {@inheritdoc}
   */
  public function isPublic() {
    return $this->get('booking_public')->value;
  }

  /**
   * @inheritdoc
   */
  public function getRoomEntity() {
    $values = $this->get('booking_resource')->getValue();
    if (!empty($values) && isset($values[0]['target_id'])) {
      return entity_load('node', array_pop($values)['target_id']);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Booking entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Booking entity.'))
      ->setReadOnly(TRUE);

    // Booking status field.
    // ListTextType with a drop down menu widget.
    // The values shown in the menu represents the possible states of the booking, which is used for filtering in administration.
    // In the view the field content is shown as string.
    // In the form the choices are presented as options list.
    // Possible values are: Request, accepted, refused, pending, cancelled.
    $fields['booking_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the Booking entity.'))
      ->setSettings(array(
        'allowed_values' => array(
          'request' => 'Request',
          'accepted' => 'Accepted',
          'refused' => 'Refused',
          'pending' => 'Pending',
          'cancelled' => 'Cancelled',
        ),
      ))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Booking type field.
    $fields['booking_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Booking type'))
      ->setDescription(t('The type of booking.'))
      ->setSettings(array(
        'allowed_values' => array(
          'private' => 'Private',
          'association' => 'Association',
        ),
      ))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Name field.
    $fields['booking_association'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Association'))
      ->setDescription(t('The name of the association'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Booking public status field.
    // ListTextType with a drop down menu widget.
    $fields['booking_public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public status'))
      ->setDescription(t('Whether the booking is public avaiable.'))
      ->setDefaultValue(TRUE)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Group id field. Defines which resources are available to the user.
    // This field cannot be modified by users and is set depending on the user login type (Employee, Union or private person).
    $fields['booking_group_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group id'))
      ->setDescription(t('Group id.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Hash value that is calculated to display the booking in links in mails
    // etc.
    $fields['booking_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Hash'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Exchange id field. Used to identify the resource in exchange.
    // Used for deleting a resource in exchange.
    $fields['booking_exchange_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Exchange id'))
      ->setDescription(t('Exchange id.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Exchange change key field. Used to identify the resource and state in exchange.
    // Used for altering a resource in exchange.
    $fields['booking_change_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Change key'))
      ->setDescription(t('Change key.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Name field.
    $fields['booking_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('First name and last name.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Email field.
    $fields['booking_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('Booking email.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Phone field.
    $fields['booking_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone number'))
      ->setDescription(t('Booking phone number.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Usage field.
    $fields['booking_usage'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Usage'))
      ->setDescription(t('The usage of the booked resource.'))
      ->setSettings(array(
        'allowed_values' => koba_booking_room_usage(),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Resource/room field.
    $fields['booking_resource'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resource'))
      ->setDescription(t('The resource to book.'))
      ->setSettings(array(
        'target_type' => 'node',
        'target_bundle' => 'room',
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array('room'),
          'sort' => array(
            'field' => 'title',
            'direction' => 'ASC',
          ),
        ),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // From date.
    $fields['booking_from_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setDescription(t('Indtastes som åååå-mm-dd / tt:mm:ss.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_timestamp',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => -3,
      ))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // To date.
    $fields['booking_to_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setDescription(t('Indtastes som åååå-mm-dd / tt:mm:ss.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_timestamp',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => -3,
      ))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Message.
    $fields['booking_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => array(
          'rows' => 5,
        ),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // <--------------------------------------->

    // Name field for the booking.
    // This is a required field for entities.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('A title for the booking.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -6,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of ContentEntityExample entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}
