(function ($) {

  "use strict";

  /**
   * File Chooser Field Config Form helper.
   */
  Drupal.behaviors.FileChooserFieldConfigForm = {
    attach: function (context) {
      var $context = $(context);

      $context.find('form.file-chooser-field-settings .vertical-tabs__pane').each(function() {
        var _plugin_name = $(this).data('drupal-selector');
        $context.find('#' + _plugin_name).drupalSetSummary(function (context) {
          if ($(context).find('input[type=checkbox]:first:checked').length) {
            return Drupal.t('Enabled');
          }
          else {
            return Drupal.t('Disabled');
          }
        });

      });

    }
  };

}(jQuery));
