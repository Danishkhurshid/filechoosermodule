<?php

namespace Drupal\file_chooser_field\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for file_chooser_field routes.
 */
class FileChooserFieldPageController extends ControllerBase {

  /**
   * Redirect Callback.
   */
  public function redirectCallback($phpClassName) {

    $element['content'] = [
      '#markup' => 'test:' . $phpClassName,
    ];

    return $element;
  }

}
