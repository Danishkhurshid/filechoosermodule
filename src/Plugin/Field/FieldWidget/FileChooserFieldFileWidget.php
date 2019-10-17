<?php

namespace Drupal\file_chooser_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file_chooser_field\Services\FileChooserFieldCore;

/**
 * Plugin implementation of the 'file_chooser_field' widget.
 *
 * @FieldWidget (
 *   id = "file_chooser_widget",
 *   label = @Translation("File chooser"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileChooserFieldFileWidget extends FileWidget implements ContainerFactoryPluginInterface {

  /**
   * Drupal\file_chooser_field\Services\FileChooserFieldCore.
   */
  protected $fileChooserFieldCore;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, $fileChooserFieldCore) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->fileChooserFieldCore = $fileChooserFieldCore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'), $container->get('file_chooser_field_core'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    return $element;
  }


  /**
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    return $elements;
  }

  /**
   *
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

  /**
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    $config = \Drupal::config('file_chooser_field.settings');
    $cardinality = $element['#cardinality'];
    $description = $element['#description'];
    $upload_validators = $element['#upload_validators'];
    $multiselect = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $info = [
      'cardinality'       => $cardinality,
      'description'       => $description,
      'upload_validators' => $upload_validators,
      'multiselect'       => $multiselect,
    ];

    $plugins = fileChooserFieldCore::loadPlugins();
    foreach ($plugins as $name => $plugin) {
      if (\Drupal::currentUser()->hasPermission('upload files from ' . $name) && $config->get($name . '_enabled') == 1) {
        // Button attributes.
        $attributes = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'attributes', [$info]);
        // Button label.
        $label = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'label');
        // Button CSS class.
        $cssClass = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'cssClass');
        // Load all requried assets.
        $library = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'assets', [$config]);
        $chooser = [
          '#theme' => 'file_chooser_field',
          '#label' => $label->render(),
          '#class' => $cssClass,
          '#attributes' => $attributes,
        ];
        $choose[] = array_merge($chooser, $library);
      }
    }

    $browse = [
      "#theme" => 'file_chooser_field',
      '#label' => t('Browse'),
      '#class' => 'browse',
      '#attributes' => [],
    ];

    $prefix_array = \Drupal::service('renderer')->render($choose);
    $browse = \Drupal::service('renderer')->render($browse);
    $element['file_chooser_field'] = [
      '#type' => 'hidden',
      '#value_callback' => [get_called_class(), 'file_chooser_field_value'],
      '#field_name' => $element['#field_name'],
      '#field_parents' => $element['#field_parents'],
      '#upload_location' => $element['#upload_location'],
      '#file_chooser_field_upload_validators' => $upload_validators,
      '#prefix' => '<div class="file-chooser-field-wrapper">' . $browse . $prefix_array,
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'file_chooser_field/file_chooser_field.core'
        ]
      ], 
    ];

    $element['upload_button']['#submit'][] = [get_called_class(), 'file_chooser_field_field_widget_submit'];
    $element['#pre_render'][] = [get_called_class(), 'file_chooser_field_field_widget_pre_render'];

    return $element;
  }

  /**
   * #pre_render callback for the field widget element.
   */
  public static function file_chooser_field_field_widget_pre_render($element) {
    if (!empty($element)) {
      // $element['file_chooser_field']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * #value_callback callback for the file_chooser_field element.
   */
  public function file_chooser_field_value($element, $input = FALSE, $form_state = []) {
    $fids = [];
    if ($input && !$form_state->isRebuilding()) {
      $file_urls = explode('|', $input);
      $file_url = $input;
      if (isset($element['#attributes']['data-max-files'])) {
        $file_urls = array_slice($file_urls, 0, max(0, $element['#attributes']['data-max-files']));
      }
      foreach ($file_urls as $file_url) {
        if ($file = self::file_chooser_field_save_upload($element, $file_url, $form_state)) {
          $fids[] = $file->id();
        }
      }
      $element['#default_value']['fids'] = $fids;
    }
    return implode(',', $fids);
  }

  /**
   * Save a completed upload.
   */
  public static function file_chooser_field_save_upload($element, $file_url, $form_state) {

    // Get the upload element name. 
    $element_parents = $element['#parents'];
    if (end($element_parents) == 'file_chooser_field') {
      unset($element_parents[key($element_parents)]);
    }

    $form_field_name = implode('_', $element_parents);

    if (empty($file_url)) {
      return FALSE;
    }

    // Ensure the destination is still valid.
    $destination = $element['#upload_location'];
    $destination_scheme = FileSystem::uriScheme($destination);
    if (!$destination_scheme) {
      return FALSE;
    }
    \Drupal::service('file_system')->prepareDirectory($element['#upload_location'], FILE_CREATE_DIRECTORY);

    // Download remote file.
    if (strstr($file_url, '::::')) {
      list($phpClassName, $remote_file) = explode("::::", $file_url);
      $local_file = fileChooserFieldCore::pluginMethod($phpClassName, 'downloadFile', [$destination, $remote_file]);
      // Invoke hook_file_chooser_field_download() when remote file gets downloaded.
      \Drupal::moduleHandler()->invokeAll('file_chooser_field_download', [$phpClassName, $remote_file, $local_file]);
    }
    else {
      $local_file = system_retrieve_file($file_url, $destination);
    }
    $wrapper = \Drupal::service('file_system')->realpath($local_file);
    $upload = self::file_chooser_field_file_info($wrapper);

    // Begin building the file entity.
    $file = File::create([]);
    $file->setOwnerId(\Drupal::service('current_user')->id());
    $file->setFilename(trim(FileSystem::basename($upload->filename), '.'));
    $file->setFileUri($local_file);
    $file->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($file->filename));
    $file->setSize($upload->filesize);


    // Run validators.
    $validators['file_validate_name_length'] = [];
    $errors = file_validate($file, $validators);
    if ($errors) {
      $message = t('The specified file %name could not be uploaded.', ['%name' => $file->filename]);
      $message .= ' ' . array_pop($errors);
      $form_state->setErrorByName($form_field_name, $message);
      return FALSE;
    }

    // Prepare the destination directory.
    if (!(\Drupal::service('file_system')->prepareDirectory($destination, FILE_CREATE_DIRECTORY))) {
      \Drupal::logger('file_chooser_field', 'The upload directory %directory for the file field !name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', ['%directory' => $destination, '!name' => $element['#field_name']]);
      $form_state->setErrorByName($form_field_name, t('The file could not be uploaded.'));
      return FALSE;
    }

    // Complete the destination.
    if (substr($destination, -1) != '/') {
      $destination .= '/';
    }
    $destination = \Drupal::service('file_system')->getDestinationFilename($destination . $file->getFilename(), FILE_EXISTS_RENAME);

    // Move the uploaded file.
    $file->setFileUri($destination);
    if (!rename($local_file, $file->getFileUri())) {
      $form_state->setErrorByName($form_field_name, t('File upload error. Could not move uploaded file.'));
      \Drupal::logger('file_chooser_field', 'Upload error. Could not move uploaded file %file to destination %destination.', ['%file' => $file->getFilename(), '%destination' => $file->getFileUri()]);
      return FALSE;
    }

    // Set the permissions on the new file.
    \Drupal::service('file_system')->chmod($file->getFileUri());

    $file->save();
    if (!$file) {
      return FALSE;
    }
    return $file;
  }

  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    return parent::value($element, $input, $form_state);
  }

  /**
   * Get file information and its contents to upload.
   */
  public static function file_chooser_field_file_info($path) {
    $file = pathinfo($path);

    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = @finfo_file($finfo, $path);
    $contents = file_get_contents($path);

    $info = [
      'filename'  => $file['basename'],
      'extension' => $file['extension'],
      'mimetype'  => $mimetype,
      'filesize'  => strlen($contents),
    ];
    return (object) $info;
  }

  /**
   *
   */
  public static function file_chooser_field_field_widget_submit($form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);
    $element = NestedArray::getValue($form, $parents);
    // Append our items.
    if ($button_key == 'upload_button') {
      if (!empty($element['file_chooser_field']['#value'])) {
        $fids = $element['file_chooser_field']['#value'];
        $form_state->setValueForElement($element['fids'], $fids);
        NestedArray::setValue($form_state->getUserInput(), $element['fids']['#parents'], $fids);
      }
    } 
    
    $form_state->setRebuild();
  }

}
