(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.acreditacion_vigente = {
    attach: function (context, settings) {

      $('.node-acreditaciones-vigentes-form').once().each(function() {

        // NAME edit-field-procesos-acreditacion-vige-form-0-field-fecha-vencimiento-acred-0-value-date

        var inicioInput = '#edit-field-fecha-resol-acreditacion-0-value-date';
        var venceInput = '#edit-field-fecha-vencimiento-acred-0-value-date';
        var aniosInput = '#edit-field-tiempo-vigencia-acreditaci-0-value';

        $(document).ready(function() {
          console.log('acreditacion_vigente');

          // Calcula fecha vencimiento acreditacion vigente
          $(venceInput).prop('disabled',true);
          $().on('change', function() {
            calculaFechaVencimientoRegistro();
          });

          // habilita al guardar, los habilita de nuevo para guardar los valores.
          $('#edit-submit').click(function(){
            $(venceInput).prop('disabled',false);
          });

        });
      });

      // Funcion Calcula fecha vencimiento acreditacion vigente
      function calculaFechaVencimientoRegistro() {
        let anios = Number($(aniosInput).val());
        let fechaInicio = jQuery(inicioInput).val();
        if ( anios == '' || fechaInicio == '' ) {
          $(venceInput).val('');
          return;
        }
        let fechaVence = new Date(fechaInicio);
        fechaVence.setFullYear(fechaVence.getFullYear() + anios);
        let getYear = fechaVence.toLocaleString("default", { year: "numeric" });
        let getMonth = fechaVence.toLocaleString("default", { month: "2-digit" });
        let getDay = fechaVence.toLocaleString("default", { day: "2-digit" });
        let dateFormat = getYear + "-" + getMonth + "-" + getDay;
          console.log(dateFormat);
        $(venceInput).val(dateFormat);
      }
    }
  };
})(jQuery, Drupal);
