<?php
/**
 * @file
 * Contains \Drupal\dokk_resource\Controller\DokkResourceController.
 */

namespace Drupal\koba_booking\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * DemoController.
 */
class KobaBookingController extends ControllerBase  {

  public function angular() {
    $build = array(
      '#type' => 'markup',
      '#theme' => 'angular_test',
      '#attached' => array(
        'library' =>  array(
          'koba_booking/angular'
        ),
      ),
    );

    return $build;
  }

  public function accepted() {
    return '';
  }

  public function denied() {
    return '';
  }
}