<?php

/**
 * @file
 * Contains Drupal\file_chooser_field\Plugins\FileChooserFieldPlugin.
 */

namespace Drupal\file_chooser_field\Plugins;

/**
 * File Chooser Field plugin abstract class.
 * Extend this class to integrate a new file upload plugin.
 */
abstract class FileChooserFieldPlugin {

  /**
   * Button label.
   *
   * @return string
   */
  abstract public function label();

  /**
   * Button CSS class name.
   *
   * @return string.
   */
  abstract public function cssClass();

  /**
   * Set button attributes such as data-[array-key]="[array-value]".
   *
   * @return array
   */
  abstract public function attributes($info);

  /**
   * Load all required assets, such as Javascript or CSS.
   * Use drupal_add_js() or drupal_add_css().
   */
  abstract public function assets($config);

  /**
   * Configuration form.
   * Drupal form API elements.
   *
   * @return array.
   */
  abstract public function configForm($config);

  /**
   * Submit configuration form.
   *
   * @return array.
   */
  public function submitForm($config, $form_state) {
    // Save configForm settings.
  }

  /**
   * Download remote file to Drupal.
   *
   * @return public://[remote-file-name]
   * @see file_chooser_field_save_upload().
   */
  abstract public function downloadFile($element, $destination, $url);

  /**
   * Redirect callback.
   * Some APIs require redirect URLs. This method handles that.
   * See Plugin configuration page for the URL.
   *
   * @return string. Contents of the callback page.
   */
  public function redirectCallback($config) {
    // This is where you would put all required code for the response.
  }
}
