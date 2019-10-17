<?php

namespace Drupal\file_chooser_field\Plugins;

use Drupal\file_chooser_field\Plugins\FileChooserFieldPlugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dropbox Chooser API integration class.
 */
class DropboxChooserAPI extends FileChooserFieldPlugin {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Dropbox');
  }

  /**
   * {@inheritdoc}
   */
  public function cssClass() {
    return 'dropbox-chooser';
  }

  /**
   * {@inheritdoc}
   */
  public function attributes($info) {
    // Add the extension list as a data attribute.
    $extensions = [];
    if (isset($info['upload_validators']['file_validate_extensions'][0])) {
      foreach (array_filter(explode(' ', $info['upload_validators']['file_validate_extensions'][0])) as $ext) {
        $extensions[] = '.' . $ext;
      }
    }
    return [
      'plugin'          => get_class($this),
      'cardinality'     => $info['cardinality'],
      'description'     => strip_tags($info['description']),
      'max-filesize'    => $info['upload_validators']['file_validate_size'][0],
      'multiselect'     => $info['multiselect'],
      'file-extentions' => join(",", $extensions),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function assets($config) {
    return [
      '#attached' => [
        'library' => [
          'file_chooser_field/file_chooser_field.dropbox',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($config) {

    $form['dropbox_app_key'] = [
      '#title'         => $this->t('Dropbox App Key'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('dropbox_app_key'),
      '#description'   => $this->t('Please <a href="https://www.dropbox.com/developers/apps" target="_blank">create a Drop-in app</a> to get the App Key.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($config, $form_state) {
    $config->set('dropbox_app_key', $form_state->getValue('dropbox_app_key'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile($destination, $url) {
    return system_retrieve_file($url, $destination);
  }

}
