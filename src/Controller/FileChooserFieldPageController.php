<?php

/**
 * @file
 * Contains \Drupal\file_chooser_field\Controller\FileChooserFieldPageController.
 */

namespace Drupal\file_chooser_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Drupal\file_chooser_field\FileChooserFieldCore;

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
