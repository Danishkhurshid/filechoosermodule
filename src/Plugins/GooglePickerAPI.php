<?php

namespace Drupal\file_chooser_field\Plugins;

use Drupal\file_chooser_field\Plugins\FileChooserFieldPlugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_chooser_field\Services\FileChooserFieldCore;

/**
 * Google Picker API integration class.
 */
class GooglePickerAPI extends FileChooserFieldPlugin {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Google Drive');
  }

  /**
   * {@inheritdoc}
   */
  public function cssClass() {
    return 'google-picker';
  }

  /**
   * {@inheritdoc}
   */
  public function attributes($info) {

    $extensions = [];
    if (isset($info['upload_validators']['file_validate_extensions'][0])) {
      foreach (array_filter(explode(' ', $info['upload_validators']['file_validate_extensions'][0])) as $ext) {
        $mime = FileChooserFieldCore::file_chooser_field_mime_by_extension($ext);
        if (is_array($mime)) {
          foreach ($mime as $mime_item) {
            $extensions[] = $mime_item;
          }
        }
        else {
          $extensions[] = $mime;
        }
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
        'library' => 'file_chooser_field/file_chooser_field.google',
        'drupalSettings' => [
          'google_client_id' => $config->get('google_client_id'),
          'google_app_id'    => $config->get('google_app_id'),
          'google_scope'     => explode("\n", $config->get('google_scope')),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($config) {

    $form['google_client_id'] = [
      '#title'         => $this->t('Client ID'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('google_client_id'),
      '#description'   => $this->t('The Client ID obtained from the Google Developers Console. e.g. <em>886162316824-pfrtpjns2mqnek6e35gv321tggtmp8vq.apps.googleusercontent.com</em>'),
    ];

    $form['google_app_id'] = [
      '#title'         => $this->t('Application ID'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('google_app_id'),
      '#description'   => $this->t('Its the first number in your Client ID. e.g. <em>886162316824</em>'),
    ];

    $scope_var = $config->get('google_scope');

    $form['google_scope'] = [
      '#title'         => $this->t('Scope'),
      '#type'          => 'textarea',
      '#default_value' => !empty($scope_var) ? $scope_var : 'https://www.googleapis.com/auth/photos',
      '#description'   => $this->t('Scope to use to access user\'s Drive items. Please put each scope in it is own line. <a href="https://developers.google.com/picker/docs/#otherviews" target="_blank">See available scopes</a>.'),
    ];

    $form['google_instructions'] = [
      '#type'  => 'fieldset',
      '#title' => t('Configuration instructions'),
    ];

    $form['google_instructions']['info'] = [
      '#markup' => '<p>To get started using Google Picker API, you need to first '
      . '<a href="https://console.developers.google.com/flows/enableapi?apiid=picker" target="_blank">'
      . 'create or select a project in the Google Developers Console and enable the API</a>.</p>'
      . '<ul><li>Enable <strong>Google Picker API</strong> <em>(required)</em></li>'
      . '<li>Enable <strong>Drive API</strong> <em>(required)</em></li></ul>'
      . '<p>Read more about <em>Scopes</em> and more details about how to get credentials on the '
      . '<a href="https://developers.google.com/picker/docs/" target="_blank">documentaion page</a>.',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($config, $form_state) {
    $config->set('google_developer_key', $form_state->getValue('google_developer_key'))
      ->set('google_client_id', $form_state->getValue('google_client_id'))
      ->set('google_app_id', $form_state->getValue('google_app_id'))
      ->set('google_scope', $form_state->getValue('google_scope'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile($destination, $url) {
    $local_file = '';
    list($id, $orignal_name, $google_token) = explode('@@@', $url);
    $remote_url = 'https://www.googleapis.com/drive/v2/files/' . $id . '?alt=media';
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $google_token,
      ],
    ];
    $request = drupal_http_request($remote_url, $options);
    if ($request->code == 200) {
      $local_file = file_unmanaged_save_data($request->data, $destination . '/' . $orignal_name);
    }
    return $local_file;
  }

  /**
   * {@inheritdoc}
   */
  public function redirectCallback($config) {
    return $this->assets($config);
  }

}
