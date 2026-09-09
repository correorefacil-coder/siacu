(function ($, Drupal) {

  // Ajax Procesos Tipo de modificacion checkbox
  $.fn.myAjaxCallback = function(args) {

    var array = Object.entries(args);
    for (let i = 0; i < array.length; i++) {
      console.log(array[i][0]);

      switch (array[i][0]) {
        /*
        denominacion|Denominación – Título
        lugar|Lugar de desarrollo
        convenios|Convenios
        cupos|Cupos
        metodologia|Modalidad
        duracion|Duración
        admision|Periodo de admisión
        conocimiento|Campo de Conocimiento
        plan|Plan de estudios
        */
        case 'denominacion':
          iter = array[i][1]['lugar']
          title1 = "<h5>Nombre Del Programa Actual:</h5>";
          text1 = '<div>' + array[i][1]['title'] + '</div>';
          title2 = "<h5>Titulo A Otorgar Actual:</h5>";
          text2 = '<div>' + array[i][1]['otorga'] + '</div>';
          title3 = "<h5>Municipio De Oferta Del Programa Actual:</h5>";
          text3 = '';
          iter.forEach(element => {
            text3 = text3 + '<div>' + element + '</div>';
          });
          $('#html-denominacion').html(
            title1 + text1 +
            title2 + text2 +
            title3 + text3
          );
          break;

        case 'lugar':
          iter = array[i][1]['lugar'];
          text1 = '';
          iter.forEach(element => {
            text1 = text1 + '<div>' + element + '</div>';
          });
          title1 = "<h5>Municipio De Oferta Del Programa Actual:</h5>";
          $('#html-lugar').html(
            title1 + text1
          );
          break;

        case 'metodologia':
          iter = array[i][1]['metodologia'];
          text1 = '';
          iter.forEach(element => {
            text1 = text1 + '<div>' + element + '</div>';
          });
          title1 = "<h5>Modalidad del Programa Actual:</h5>";
          $('#html-metodologia').html(
            title1 + text1
          );
          break;

        case 'convenios':
          title = "<h5>Convenios Para El Ofrecimiento Del Programa:</h5>";
          text = '';
          iter = array[i][1];
          iter.forEach(element => {
            text = text + '<div>' + element + '</div>';
          });
          $('#html-convenios').html(title + text);
          break;

        case 'cupos':
          title = "<h5>Cupo Máximo Actual:</h5>";
          text = '<div>' + array[i][1] + '</div>';
          $('#html-cupos').html(title + text);
          break;

        case 'modalidad':
          title2 = "<h5>Metodología Actual:</h5>";
          text2 = '<div>' + array[i][1]['metodo'] + '</div>';
          $('#html-modalidad').html(
            title2 + text2
          );
          break;

        case 'duracion':
          title1 = "<h5>Duración Estimada Del Programa Actual:</h5>";
          text1 = '<div>' + array[i][1]['tiempo'] + '</div>';
          $('#html-duracion').html(
            title1 + text1
          );
          break;

        case 'conocimiento':
          title3 = "<h5>Campos De Conocimiento Actual:</h5>";
          text3 = '<div><b>Amplio:</b> ' + array[i][1]['amplio']  + '</div>';
          text4 = '<div><b>Especifico:</b> ' + array[i][1]['especifico']  + '</div>';
          text5 = '<div><b>Detallado:</b> ' + array[i][1]['detallado']  + '</div>';
          $('#html-conocimiento').html(
            title3 + text3 + text4 + text5
          );
          break;

        case 'admision':
          title2 = "<h5>Periodicidad De Admisión Actual:</h5>";
          text2 = '<div>' + array[i][1]['periodicidad'] + '</div>';
          $('#html-periodicidad').html(
            title2 + text2
          );
          break;

        case 'plan':
          title = "<h5>Planes De Estudios Vigentes:</h5>";
          text = '';
          iter = array[i][1];
          iter.forEach(element => {
            text = text + '<div>' + element + '</div>';
          });
          $('#html-plan').html(title + text);
          break;

      }

    }

  };

  'use strict';
  Drupal.behaviors.udn_clonar_actividades = {
    attach: function (context, settings) {
      $('body').once().each(function() {
        $(document).ready(function() {

          // En Procesos, quita checks de Modificaciones al cambiar el programa existente
          $('#edit-field-procesos-pa-existente-0-target-id').keyup(function(){
            $('#edit-field-tipo-modificaciones input').prop( "checked", false );
          });

          // Rango Fechas Programadas en Actividades
          $('#edit-field-act-procesos-fecha-inicio-0-value-date').change(function(value){
            $('#edit-field-act-procesos-fecha-final-0-value-date').prop( "min", value.currentTarget.value );
          });
          $('#edit-field-act-procesos-fecha-final-0-value-date').change(function(value){
            $('#edit-field-act-procesos-fecha-inicio-0-value-date').prop( "max", value.currentTarget.value );
          });

          // Rango Fechas Reales en Actividades
          $('#edit-field-act-procesos-fecha-inicior-0-value-date').change(function(value){
            $('#edit-field-act-procesos-fecha-finalr-0-value-date').prop( "min", value.currentTarget.value );
          });
          $('#edit-field-act-procesos-fecha-finalr-0-value-date').change(function(value){
            $('#edit-field-act-procesos-fecha-inicior-0-value-date').prop( "max", value.currentTarget.value );
          });

        });
      });
    }
  };
})(jQuery, Drupal);
