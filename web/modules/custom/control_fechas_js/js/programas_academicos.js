(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.programas_academicos = {
    attach: function (context, settings) {

      // SE EJECUTA AL EDITAR PROGRAMA ACADEMICO
      $('.node-programas-academicos-edit-form').once().each(function() {
        $(document).ready(function() {

          $('#edit-field-anio-inicio-programa-0-value').attr('maxlength','4');

          // Desactiva el campo vencimiento
          $('#edit-field-fecha-vencimiento-reg-cal-0-value-date').prop('disabled',true);
          // Calcula fecha vencimiento registro calificado
          $('#edit-field-vigencia-0-value,#edit-field-fecha-ini-proacre-vig-0-value-date,#edit-field-fecha-resol-reg-calif-0-value-date').on('change', function() {
            calculaFechaVencimientoRegistro();
          });

          // Formato numero en campo Costo Matricula
          $('#edit-field-costo-matricula-nuevos-0-value').on('blur', function() {
            const value = this.value.replace(/,/g, '');
            const numberFormat2 = new Intl.NumberFormat('en-US');
            this.value = numberFormat2.format(value)
          });

          // Suma Creditos
          $('#edit-field-total-creditos-0-value').prop('disabled',true);
          $('#edit-field-num-cred-oblig-0-value,#edit-field-num-cred-elec-0-value').keyup(function(){
            sumaCreditos();
          });

          // Suma Creditos Ciclon Formacion
          $('#edit-field-cred-formacion-general-0-value').prop('disabled',true);
          $('#edit-field-num-cred-ciclo-basico-0-value,#edit-field-cred-ciclo-profesional-0-value').keyup(function(){
            sumaCreditosCicloFormacion();
          });

          // habilita al guardar, los habilita de nuevo para guardar los valores.
          $('#edit-submit').click(function(){
            $('#edit-field-total-creditos-0-value').prop('disabled',false);
            $('#edit-field-cred-formacion-general-0-value').prop('disabled',false);
          $('#edit-field-fecha-vencimiento-reg-cal-0-value-date').prop('disabled',false);
          });

        });
      });

      $('.ief-form > [id*="edit-field-acreditaciones-vig-form-inline-entity-form-entities-').once().each(function() {
        var env = this;
        $(document).ready(function() {
          var id = $(env).attr('id').split('--')[0];
          var index = id.match(/\d+/g)[0];
          // DESACTIVA EL CAMPO VENCIMIENTO
          $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).prop('disabled',true);

          // ASIGNACION DE CAMPO NUMERO A TIEMPO VIGENCIA
          $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_tiempo_vigencia_acreditaci][0][value]"]`).get(0).type = 'number';

          $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_resol_acreditacion][0][value][date]"],
             [name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_tiempo_vigencia_acreditaci][0][value]"]`).on('change', function(ev) {
            calculaFechaVencimientoAcreditacion(index);
          });

          // habilita al guardar, los habilita de nuevo para guardar los valores.
          var submit1 = $(`[name="ief-edit-submit-field_acreditaciones_vig-form-${index}"]`);
          submit1.on( 'mousedown', function(e) {
            $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).prop('disabled',false);
          });
          submit1.on( 'click', function(e) {
            $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).prop('disabled',false);
          });
          submit1.on( 'keypress', function(e) {
            $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).prop('disabled',false);
          });

          var eve = $._data( $(`[name="ief-edit-submit-field_acreditaciones_vig-form-${index}"]`)[0], "events" );
          eve.click.reverse();
          eve.mousedown.reverse();
          eve.keypress.reverse();

          var submit2 = $('#edit-submit');
          submit2.click(function(e){
            console.log('Submit2');
            $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).prop('disabled',false);
            return true;
          });
        });
      });
      // Funcion Suma Creditos
      function sumaCreditos() {
        let cred_oblig = Number($('#edit-field-num-cred-oblig-0-value').val());
        let cred_elec = Number($('#edit-field-num-cred-elec-0-value').val());
        $('#edit-field-total-creditos-0-value').val(cred_oblig + cred_elec);
      }

      // Funcion Suma Creditos Ciclon Formacion
      function sumaCreditosCicloFormacion() {
        let cred_oblig = Number($('#edit-field-num-cred-ciclo-basico-0-value').val());
        let cred_elec = Number($('#edit-field-cred-ciclo-profesional-0-value').val());
        $('#edit-field-cred-formacion-general-0-value').val(cred_oblig + cred_elec);
      }

      // Funcion Calcula fecha vencimiento registro calificado
      function calculaFechaVencimientoRegistro() {
        var fechaInicio = '';
        let anios = Number($('#edit-field-vigencia-0-value').val());
        let fechaInicio1 = jQuery('#edit-field-fecha-ini-proacre-vig-0-value-date').val();
        let fechaInicio2 = jQuery('#edit-field-fecha-resol-reg-calif-0-value-date').val();
        if ( anios == '' || (fechaInicio1 == '' && fechaInicio2 == '') ) {
          $('#edit-field-fecha-vencimiento-reg-cal-0-value-date').val('');
          return;
        }
        if (fechaInicio1 == '') {
          fechaInicio = fechaInicio2;
        }else{
          fechaInicio = fechaInicio1;
        }
        let fechaVence = addYears(fechaInicio, anios);
        let getYear = fechaVence.toLocaleString("default", { year: "numeric" });
        let getMonth = fechaVence.toLocaleString("default", { month: "2-digit" });
        let getDay = fechaVence.toLocaleString("default", { day: "2-digit" });
        let dateFormat = getYear + "-" + getMonth + "-" + getDay;
        $('#edit-field-fecha-vencimiento-reg-cal-0-value-date').val(dateFormat);
      }

      // Funcion Calcula fecha vencimiento Acreditacion en formulario In Line Form
      function calculaFechaVencimientoAcreditacion(index) {
        console.log(`calculaFechaVencimientoAcreditacion:${index}`);
        let anios = Number($(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_tiempo_vigencia_acreditaci][0][value]"]`).val());
        let fechaInicio = jQuery(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_resol_acreditacion][0][value][date]"]`).val();
         if ( anios == '' || fechaInicio == '' ) {
          $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).val('');
          return;
        }
        let fechaVence = addYears(fechaInicio, anios);
        let getYear = fechaVence.toLocaleString("default", { year: "numeric" });
        let getMonth = fechaVence.toLocaleString("default", { month: "2-digit" });
        let getDay = fechaVence.toLocaleString("default", { day: "2-digit" });
        let dateFormat = getYear + "-" + getMonth + "-" + getDay;
        $(`[name="field_acreditaciones_vig[form][inline_entity_form][entities][${index}][form][field_fecha_vencimiento_acred][0][value][date]"]`).val(dateFormat);
      }

      function addYears(date,years) {
        date = new Date(`${date} 00:00:00 GMT-0500`);
        let day = date.getDate(),
        newDate = new Date(date.getFullYear()+years,date.getMonth(),date.getDate());
        newDate.getDate() != day && newDate.setDate(day);
        return newDate;
      }

    }
  };
})(jQuery, Drupal);
