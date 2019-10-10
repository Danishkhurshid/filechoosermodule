<?php

/**
 * @file
 * Contains Drupal\file_chooser_field\Plugins\BoxPickerAPI.
 */

namespace Drupal\file_chooser_field\Plugins;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_chooser_field\Plugins;

/**
 * The Box Picker API integration class.
 */
class BoxPickerAPI extends Plugins\FileChooserFieldPlugin {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Box');
  }

  /**
   * {@inheritdoc}
   */
  public function cssClass() {
    return 'box-picker';
  }

  /**
   * {@inheritdoc}
   */
  public function attributes($info) {
    return [
      'plugin'       => get_class($this),
      'cardinality'  => $info['cardinality'],
      'description'  => strip_tags($info['description']),
      'max-filesize' => $info['upload_validators']['file_validate_size'][0],
      'multiselect'  => $info['multiselect'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function assets($config) {
    return [
      '#attached' => [
        'library' => 'file_chooser_field/file_chooser_field.box',
        'drupalSettings' => [
          'box_client_id' => $config->get('box_client_id')
        ]
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($config) {

    $form['box_client_id'] = [
      '#title'         => $this->t('Box Client ID'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('box_client_id'),
      '#description'   => $this->t('Please <a href="https://app.box.com/developers/services" target="_blank">create a Box Application</a> to get the Client ID.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($config, $form_state) {
    $config->set('box_client_id', $form_state->getValue('box_client_id'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile($element, $destination, $url) {
    $local_file = '';
    list($file_url, $orignal_name) = explode('@@@', $url);
    $local_file = system_retrieve_file($file_url, $destination . '/' . $orignal_name);
    return $local_file;
  }

}
