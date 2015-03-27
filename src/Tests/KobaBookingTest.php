<?php

/**
 * @file
 * Test cases for Koba Booking Module.
 */

namespace Drupal\koba_booking\Tests;

use Drupal\koba_booking\Entity\Booking;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the basic functions of the Koba Booking module.
 *
 * @package Drupal\koba_booking\Tests
 *
 * @ingroup koba_booking
 *
 * @group koba_booking
 */
class ContentEntityExampleTest extends WebTestBase {

  public static $modules = array('koba_booking', 'block', 'field_ui');

  /**
   * Basic tests for Koba Booking.
   */
  public function testContentEntityExample() {
    $web_user = $this->drupalCreateUser(array(
      'add booking entity',
      'edit booking entity',
      'view booking entity',
      'delete booking entity',
      'administer booking entity',
      'administer koba_booking_booking display',
      'administer koba_booking_booking fields',
      'administer koba_booking_booking form display'));

    $this->drupalPlaceBlock('system_menu_block:tools', array());

    // Anonymous User should not see the link to the listing.
    $this->assertNoText(t('Koba Booking: Bookings Listing'));

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $this->assertLink(t('Koba Booking: Bookings Listing'));

    $this->clickLink(t('Koba Booking: Bookings Listing'));

    // WebUser can add entity content.
    $this->assertLink(t('Add Booking'));

    $this->clickLink(t('Add Booking'));

    $this->assertFieldByName('name[0][value]', '', 'Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'First Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'Gender Field, empty');

    $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
    $this->assertFieldByName('user_id[0][target_id]', $user_ref, 'User ID reference field points to web_user');

    // Post content, save an instance. Go back to list after saving.
    $edit = array(
      'name[0][value]' => 'test name',
      'first_name[0][value]' => 'test first name',
      'gender' => 'male',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Entity listed.
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    $this->clickLink('test name');

    // Entity shown.
    $this->assertText(t('test name'));
    $this->assertText(t('test first name'));
    $this->assertText(t('male'));
    $this->assertLink(t('Add Booking'));
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->assertLink(t('Cancel'));
    $this->drupalPostForm(NULL, array(), 'Delete');

    // Back to list, must be empty.
    $this->assertNoText('test name');

    // Settings page.
    $this->drupalGet('admin/structure/koba_booking_booking_settings');
    $this->assertText(t('Booking Settings'));

    // Make sure the field manipulation links are available.
    $this->assertLink(t('Settings'));
    $this->assertLink(t('Manage fields'));
    $this->assertLink(t('Manage form display'));
    $this->assertLink(t('Manage display'));
  }

  /**
   * Test all paths exposed by the module, by permission.
   */
  public function testPaths() {
    // Generate a booking so that we can test the paths against it.
    $booking = Booking::create(
      array(
        'name' => 'somename',
        'first_name' => 'Joe',
        'gender' => 'female',
      )
    );
    $booking->save();

    // Gather the test data.
    $data = $this->providerTestPaths($booking->id());

    // Run the tests.
    foreach ($data as $datum) {
      // drupalCreateUser() doesn't know what to do with an empty permission
      // array, so we help it out.
      if ($datum[2]) {
        $user = $this->drupalCreateUser(array($datum[2]));
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($datum[1]);
      $this->assertResponse($datum[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $booking_id
   *   The id of an existing Booking entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($booking_id) {
    return array(
      array(
        200,
        '/koba_booking_booking/' . $booking_id,
        'view booking entity',
      ),
      array(
        403,
        '/koba_booking_booking/' . $booking_id,
        '',
      ),
      array(
        200,
        '/koba_booking_booking/list',
        'view booking entity',
      ),
      array(
        403,
        '/koba_booking_booking/list',
        '',
      ),
      array(
        200,
        '/koba_booking_booking/add',
        'add booking entity',
      ),
      array(
        403,
        '/koba_booking_booking/add',
        '',
      ),
      array(
        200,
        '/koba_booking_booking/' . $booking_id . '/edit',
        'edit booking entity',
      ),
      array(
        403,
        '/koba_booking_booking/' . $booking_id . '/edit',
        '',
      ),
      array(
        200,
        '/booking/' . $booking_id . '/delete',
        'delete booking entity',
      ),
      array(
        403,
        '/booking/' . $booking_id . '/delete',
        '',
      ),
      array(
        200,
        'admin/structure/koba_booking_booking_settings',
        'administer booking entity',
      ),
      array(
        403,
        'admin/structure/koba_booking_booking_settings',
        '',
      ),
    );
  }

}
