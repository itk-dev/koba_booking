<?php
/**
 * @file
 * Contains \Drupal\koba_booking\Controller\KobaBookingController
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\koba_booking\BookingInterface;
use Drupal\koba_booking\Exception\ProxyException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * KobaBookingController.
 */

class KobaBookingController extends ControllerBase  {

  /**
   * Setup template for frontend for calendar page.
   *
   * @return array
   *   Render array for calendar page.
   */
  public function calendarPage() {
    // Get session values from previous step.
    $defaults = \Drupal::service('session')->get('koba_booking_request');

    // Check that the session data is not expired.
    if ($defaults['expire'] < REQUEST_TIME) {
      $defaults = array();
      \Drupal::service('session')->remove('koba_booking_request');
    }

    // Load configuration.
    $config = $this->config('koba_booking.settings');
    $content = \Drupal::getContainer()->get('koba_booking.booking_content');

    // Get a half year before last_booking_date
    $last_booking_date = $content->get('koba_booking.last_booking_date');
    $last_booking_date_minus_half_year = null;

    if (date('n', $last_booking_date) > 6) {
      $last_booking_date_minus_half_year = mktime(0, 0, 0, 7, 1, date('Y', $last_booking_date));
    }
    else {
      $last_booking_date_minus_half_year = mktime(0, 0, 0, 1, 1, date('Y', $last_booking_date));
    }

    $build = array(
      '#type' => 'markup',
      '#theme' => 'booking_calendar_page',
      '#attached' => array(
        'library' => array(
          'koba_booking/angular',
        ),
        'drupalSettings' => array(
          'koba_booking' => array(
            'login_path' => \Drupal::urlGenerator()->generateFromRoute('koba_booking.api.login'),
            'module_path' => \Drupal::moduleHandler()->getModule('koba_booking')->getPath(),
            'theme_path' => \Drupal::theme()->getActiveTheme()->getPath(),
            'app_dir' => drupal_get_path('module', 'koba_booking') . '/js/app',
            'resource' => isset($defaults['resource']) ? $defaults['resource'] : NULL,
            'from' => isset($defaults['from']) ? $defaults['from'] : NULL,
            'to' => isset($defaults['to']) ? $defaults['to'] : NULL,
            'opening_hours' => $content->get('koba_booking.opening_hours'),
            'last_booking_date' => $content->get('koba_booking.last_booking_date'),
            'last_booking_date_minus_half_year' => $last_booking_date_minus_half_year,
            'search_phase' => $content->get('koba_booking.search_phase'),
            'search_phase_text' => strip_tags(check_markup($content->get('koba_booking.search_phase_text'), 'editor_format')),
          ),
        ),
      ),
    );

    return $build;
  }

  /**
   * Booking receipt page.
   *
   * @param string $hash
   *   Hash value that identifies a given booking.
   *
   * @return array
   *   Render array for calendar page.
   */
  public function receipt($hash) {
    // Load entity base on hash value.
    $query = \Drupal::entityQuery('koba_booking_booking')
      ->condition('booking_hash', $hash);
    $nids = $query->execute();

    if (!empty($nids)) {
      // Load booking.
      $booking = \Drupal::entityManager()->getStorage('koba_booking_booking')->load(array_pop($nids));

      // Setup template for frontend.
      $build = array(
        '#theme' => 'booking_receipt',
        '#booking' => $booking,
      );

      // If this booking is in the next search phase, attach search phase message.
      $config = \Drupal::config('koba_booking.settings');
      $content = \Drupal::getContainer()->get('koba_booking.booking_content');
      $last_booking_date = $content->get('koba_booking.last_booking_date');
      $last_booking_date_minus_half_year = null;
      if (date('n', $last_booking_date) > 6) {
        $last_booking_date_minus_half_year = mktime(0, 0, 0, 7, 1, date('Y', $last_booking_date));
      }
      else {
        $last_booking_date_minus_half_year = mktime(0, 0, 0, 1, 1, date('Y', $last_booking_date));
      }
      $booking_from_date = $booking->booking_from_date->value;
      if ($booking_from_date >= $last_booking_date_minus_half_year &&
        $booking_from_date <= $last_booking_date
      ) {
        $build['#search_phase_text'] = strip_tags(check_markup($content->get('koba_booking.search_phase_text'), 'editor_format'));
      }

      return $build;
    }

    // Build sub-request with page not found.
    $subrequest = Request::create('/system/404', 'GET');
    $response = \Drupal::service('httpKernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);

    return $response;
  }

  /**
   * Pending action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionPending(Request $request, BookingInterface $koba_booking_booking) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'pending');
    $koba_booking_booking->save();

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }

  /**
   * Accept action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionAccept(Request $request, BookingInterface $koba_booking_booking) {
    // Get proxy service.
    $proxy = \Drupal::service('koba_booking.api.proxy');

    try {
      if ($proxy->sendBooking($koba_booking_booking)) {
        // For efficiency manually save the original booking before applying any
        // changes.
        $koba_booking_booking->original = clone $koba_booking_booking;
        $koba_booking_booking->set('booking_status', 'pending');
        $koba_booking_booking->save();
      }
    }
    catch (ProxyException $exception) {
      drupal_set_message(t($exception->getMessage()), 'error');
    }

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }

  /**
   * Refuse action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionRefuse(Request $request, BookingInterface $koba_booking_booking) {
    // Change booking state.
    $koba_booking_booking->set('booking_status', 'refused');
    $koba_booking_booking->save();

    // Send mail.
    \Drupal::service('koba_booking.mailer')->send('rejected', $koba_booking_booking);

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }


  /**
   * Confirm action for admin pending list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionConfirm(Request $request, BookingInterface $koba_booking_booking) {
    // Get proxy service.
    $proxy = \Drupal::service('koba_booking.api.proxy');

    try {
      if ($proxy->confirmBooking($koba_booking_booking)) {
        // For efficiency manually save the original booking before applying any
        // changes.
        $koba_booking_booking->original = clone $koba_booking_booking;
        $koba_booking_booking->set('booking_status', 'pending');
        $koba_booking_booking->save();
      }
    }
    catch (ProxyException $exception) {
      drupal_set_message(t($exception->getMessage()), 'error');
    }

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
    exit;
  }

  /**
   * Cancel action for admin list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for this action.
   * @param \Drupal\koba_booking\BookingInterface $koba_booking_booking
   *   The booking to perform the action on.
   */
  public function actionCancel(Request $request, BookingInterface $koba_booking_booking = NULL) {
    // Get proxy service.
    $proxy = \Drupal::service('koba_booking.api.proxy');

    try {
      if ($proxy->deleteBooking($koba_booking_booking)) {
        // For efficiency manually save the original booking before applying any
        // changes.
        $koba_booking_booking->original = clone $koba_booking_booking;
        $koba_booking_booking->set('booking_status', 'cancelled');
        $koba_booking_booking->save();
      }
    }
    catch (ProxyException $exception) {
      drupal_set_message(t($exception->getMessage()), 'error');
    }

    // Set redirect. (Original path.)
    $referer = $request->server->get('HTTP_REFERER');
    $response = new RedirectResponse($referer);
    $response->send();
  }
}
