(function ($, once) {

  'use strict';

  /**
   * Invalid event handler for input elements in Tab field group.
   */
  var onTabInvalid = function (e) {
    $(e.target).parents('details ').children('summary[aria-expanded=false]:not(.horizontal-tabs-pane > summary, .vertical-tabs__pane > summary)').parent().attr('open', 'open');
  };

  /**
   * Make sure tab field groups which contain invalid data are expanded when
   * they first load, and also when someone clicks the submit button.
   */
  Drupal.behaviors.fieldGroupTabValidation = {
    attach: function (context) {
      $(once('field-group-tab-validation', $('.field-group-tab :input', context))).on('invalid.field_group', onTabInvalid);
    }
  };

})(jQuery, once);
