(function ($, once) {

  'use strict';

  /**
   * Invalid event handler for input elements in Details field group.
   */
  var onDetailsInvalid = function(e) {
    // Open any hidden parents first.
    $(e.target).parents('details:not([open])').each(function () {
      $(this).attr('open', '');
    });
  }

  /**
   * Behaviors for details validation.
   */
  Drupal.behaviors.fieldGroupDetailsValidation = {
    attach: function (context) {
      $(once('field-group-details-validation', $('.field-group-details :input', context))).on('invalid.field_group', onDetailsInvalid);
    }
  };

})(jQuery, once);
