<?php

/**
 * @file
 * Contains \Drupal\file_chooser_field\Plugin\Field\FieldWidget\FileChooserFieldFileWidget.
 */

namespace Drupal\file_chooser_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\file\Entity\File;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file_chooser_field\Services\FileChooserFieldCore;




/**
 * Plugin implementation of the 'gardengnome_player' widget.
 *
 * @FieldWidget (
 *   id = "file_chooser_widget",
 *   label = @Translation("File chooser"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileChooserFieldFileWidget extends FileWidget implements ContainerFactoryPluginInterface{
  
  /**
   * Drupal\file_chooser_field\Services\FileChooserFieldCore
   *
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
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'),  $container->get('file_chooser_field_core'));
  }

  /**
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    return $elements;

  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }
  /**
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $config = \Drupal::config('file_chooser_field.settings');
    $cardinality = $element['#cardinality'];
    $description = $element['#description'];
    $upload_validators = $element['#upload_validators'];
    $multiselect = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $info = [
      'cardinality'       => $cardinality,
      'description'       =>  $description,
      'upload_validators' => $upload_validators,
      'multiselect'       => $multiselect,
    ];  

    $plugins =  fileChooserFieldCore::loadPlugins();
    foreach ($plugins as $name => $plugin) {
      // $status = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'getStatus');
      // kint($status);
      if (\Drupal::currentUser()->hasPermission('upload files from ' . $name) && $config->get($name . '_enabled') == 1) {
        // Button attributes.
        $attributes = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'attributes', array($info));
        // Button label.
        $label = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'label');
        // Button CSS class.
        $cssClass = fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'cssClass');
        // Load all requried assets.
        $library =  fileChooserFieldCore::pluginMethod($plugin['phpClassName'], 'assets', [$config]);
        $chooser = [
            '#theme' => 'file_chooser_field',
            '#label' => $label->render(),
            '#class' => $cssClass,
            '#attributes' => $attributes,
        ];
        $choose[] = array_merge($chooser, $library);
      }  
    }

    $prefix_array =  \Drupal::service('renderer')->render($choose);
  
    $element['file_chooser_field'] = [
      '#type' => 'hidden',
      '#field_name' => $element['#field_name'],
      '#field_parents' => $element['#field_parents'],
      '#upload_location' => $element['#upload_location'],
      '#file_chooser_field_upload_validators' => $upload_validators,
      '#prefix' => '<div class="file-chooser-field-wrapper">' . $prefix_array,
      '#suffix' => '</div>',
    ];
    $element['file_chooser_field']['#attached']['library'][] = 'file_chooser_field/file_chooser_field.core';
    return parent::process($element, $form_state, $form);
  }

  /**
  * #value_callback callback for the file_chooser_field element.
  */
  function file_chooser_field_value($element, $input = FALSE, $form_state = array()) {
    kint("Works");
    kint($element);
    $fids = array();
    if ($input) {
      $file_urls = explode('|', $input);
      array_shift($file_urls);
      if (isset($element['#attributes']['data-max-files'])) {
        $file_urls = array_slice($file_urls, 0, max(0, $element['#attributes']['data-max-files'] - 1));
      }
      foreach ($file_urls as $file_url) {
        if ($file = file_chooser_field_save_upload($element, $file_url)) {
          $fids[] = $file->fid;
        }
      }
    }
    return implode(',', $fids);
  }


  //  /**
  //  * Form API callback. Retrieves the value for the file_generic field element.
  //  *
  //  * This method is assigned as a #value_callback in formElement() method.
  //  */
  // public static function value($element, $input = FALSE, FormStateInterface $form_state) {
  //   kint($element);
  //   kint("outside");
  //   if ($input) {
  //     kint("inside");
  //     kint($input);
  //     // Checkboxes lose their value when empty.
  //     // If the display field is present make sure its unchecked value is saved.
  //     if (empty($input['display'])) {
  //       $input['display'] = $element['#display_field'] ? 0 : 1;
  //     }
  //   }

  //   // We depend on the managed file element to handle uploads.
  //   $return = ManagedFile::valueCallback($element, $input, $form_state);

  //   // Ensure that all the required properties are returned even if empty.
  //   $return += array(
  //     'fids' => array(),
  //     'display' => 1,
  //     'description' => '',
  //   );

  //   return $return;
  // }



  //  /**
  //  * Form submission handler for upload/remove button of formElement().
  //  *
  //  * This runs in addition to and after file_managed_file_submit().
  //  *
  //  * @see file_managed_file_submit()
  //  */
  // public static function submit($form, FormStateInterface $form_state) {
  //   // During the form rebuild, formElement() will create field item widget
  //   // elements using re-indexed deltas, so clear out FormState::$input to
  //   // avoid a mismatch between old and new deltas. The rebuilt elements will
  //   // have #default_value set appropriately for the current state of the field,
  //   // so nothing is lost in doing this.
  //   $button = $form_state->getTriggeringElement();
  //   $parents = array_slice($button['#parents'], 0, -2);
  //   NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

  //   // Go one level up in the form, to the widgets container.
  //   $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
  //   kint($element);
  //   $field_name = $element['#field_name'];
  //   $parents = $element['#field_parents'];
  //   // If there are more files uploaded via the same widget, we have to separate
  //   // them, as we display each file in it's own widget.
  //   $new_values = array();
  //   foreach ($submitted_values as $delta => $submitted_value) {
  //     if (is_array($submitted_value['fids'])) {
  //       foreach ($submitted_value['fids'] as $fid) {
  //         $new_value = $submitted_value;
  //         $new_value['fids'] = array($fid);
  //         $new_values[] = $new_value;
  //       }
  //     }
  //     else {
  //       $new_value = $submitted_value;
  //     }
  //   }
  //   // Re-index deltas after removing empty items.
  //   $submitted_values = array_values($new_values);


  // }

}
