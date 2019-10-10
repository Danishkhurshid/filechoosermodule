<?php

/**
 * @file
 * Contains Drupal\file_chooser_field\Plugins\OneDriveAPI.
 */

namespace Drupal\file_chooser_field\Plugins;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_chooser_field\Plugins;

/**
 * The OneDrive API integration class.
 */
class OneDriveAPI extends Plugins\FileChooserFieldPlugin {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('OneDrive');
  }

  /**
   * {@inheritdoc}
   */
  public function cssClass() {
    return 'one-drive-picker';
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
        'library' => [
          'file_chooser_field/file_chooser_field.onedrive',
        ],
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($config) {

    $form['warning'] = [
      '#markup' => '<div><p>' . $this->t('<strong>Warning</strong>: OneDrive button doesn\'t always trigger the popup. You have to keep pressing the button until the popup shows up. '
        . 'It behaves the same even on the MS\'s website. See <a href="https://dev.onedrive.com/sdk/javascript-picker-saver.htm" target="_blank">https://dev.onedrive.com/sdk/javascript-picker-saver.htm</a> '
        . 'It might start working well once they fixe the issue.'
        ) . '</p></div><hr/><br/>',
      '#weight' => -10,
    ];

    $form['onedrive_app_id'] = [
      '#title'         => $this->t('OneDrive App ID/Client ID'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('onedrive_app_id'),
      '#description'   => $this->t('Please <a href="https://account.live.com/developers/applications" target="_blank">Register your app</a> to get an app ID (client ID), if you haven\'t already done so. '
        . 'Ensure that the web page that is going to reference the SDK is a <em>Redirect URL</em> under <strong>Application Settings</strong>.'
      )
    ];

    $form['onedrive_instructions'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Configuration instructions'),
    ];

    $form['onedrive_instructions']['info'] = [
      '#markup' => '<p>Most people have problems with properly configuring the OneDrive app.'
        . ' First of all make sure you <a href="https://account.live.com/developers/applications" target="_blank">register your app</a>.'
        . ' Set <strong>Mobile or desktop client app</strong> to <strong>No</strong>. Leave Target domain empty.'
        . ' Set <strong>Restrict JWT issuing</strong> to <strong>Yes</strong>.'
        . ' No goes the tricky part - You have to add your node/add/edit page paths as <strong>Redirect URLs</strong>.'
        . ' For instance: http://example.com/node/add/article, http://example.com/node/add/page. <br/><strong>However</strong>'
        . ' the plugin won\'t work on node edit pages. I <a href="http://stackoverflow.com/a/32492185/258899" target="_blank"> told the lead developer of the plugin about this problem</a>, let\'s see what he says.</p>',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($config, $form_state) {
    $config->set('onedrive_app_id', $form_state->getValue('onedrive_app_id'))
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
