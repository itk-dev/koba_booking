<?php

/**
 * @file
 * Angular page callbacks.
 */


/**
 * Prepares variables for angular test page.
 *
 * Example taken from search module
 *
 * @param array $variables
 *   An array with the following elements:
 *    - title
 */
function template_preprocess_angular_test(&$variables) {
  $variables['title'] = 'My title';
}