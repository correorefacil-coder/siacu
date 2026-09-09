(function ($, once) {

  'use strict';

  /**
   * Opens Tab field group with invalid input elements.
   */
  var fieldGroupTabsOpen = function ($field_group) {
    if ($field_group.data('verticalTab')) {
      $field_group.data('verticalTab').tabShow();
    }
    else if ($field_group.data('horizontalTab')) {
      $field_group.data('horizontalTab').tabShow();
    }
    else {
      $field_group.attr('open', '');
    }
  };

  /**
   * Behaviors for tab validation.
   */
  Drupal.behaviors.fieldGroupTabsValidation = {
    attach: function (context) {
      /**
       * Invalid event handler for input elements in Tabs field group.
       */
      var onTabsInvalid = function (e) {
        $inputs.off('invalid.field_group', onTabsInvalid);
        $(e.target).parents('details:not(:visible), details.horizontal-tab-hidden, details.vertical-tab-hidden').each(function () {
          fieldGroupTabsOpen($(this));
        });
        requestAnimationFrame(function () {
          $inputs.on('invalid.field_group', onTabsInvalid);
        });
      };

      var $inputs = $('.field-group-tabs-wrapper :input', context);
      $(once('field-group-tabs-validation', $inputs)).on('invalid.field_group', onTabsInvalid);
    }
  };

})(jQuery, once);
