(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.validacionActividades = {
    attach: function (context, settings) {
      // Solo ejecutar en formularios de actividades_procesos
      $('form[data-drupal-selector*="node-actividades-procesos-edit-form"]', context).once('validacion-actividades').each(function () {
        var $form = $(this);

        const field_act_procesos_mail_user = 'field_act_procesos_mail_user[]';
        const field_act_procesos_mail_titulo = 'field_act_procesos_mail_titulo[0][value]';
        const field_act_procesos_contenidomail = 'field_act_procesos_contenidomail[0][value]';
        const field_act_procesos_mail_sino = 'field_act_procesos_mail_sino';
        const id_container_proceso_mail_user = 'improvedselect-edit-field-act-procesos-mail-user';
        const id_container_proceso_mail_contenido = 'edit-field-act-procesos-contenidomail-wrapper';

        // Interceptar el envío del formulario
        $form.on('submit', function (e) {
          if (!validarCamposCorreoPersonalizado()) {
            e.preventDefault();
            return false;
          }
        });

        // Función para validar campos obligatorios
        function validarCamposCorreoPersonalizado() {

          limpiarErrores();
          var errores = [];

          var $mailSino = $('select[name*="'+field_act_procesos_mail_sino+'"]');
          var mailSino = $mailSino.val();

          if (mailSino == 'Sí, un correo personalizado') {
            var $mailUsers = $('select[name*="'+field_act_procesos_mail_user+'"]');
            var mailUsers = $mailUsers.val();
            if (!mailUsers || mailUsers[0] == '_none') {
              errores.push('Falta diligenciar el campo Correo personalizado');
              var $containerProcesoMailUser = $('#' + id_container_proceso_mail_user);
              $containerProcesoMailUser.addClass('error-field');
              $containerProcesoMailUser.focus();
            }

            var $mailTitulo = $('input[name*="'+field_act_procesos_mail_titulo+'"]');
            var mailTitulo = $mailTitulo.val();
            if (!mailTitulo || mailTitulo.trim() === '') {
              errores.push('Falta diligenciar el campo Asunto del correo personalizado');
              $mailTitulo.addClass('error-field');
              $mailTitulo.focus();
            }

            var $mailContenido = $('textarea[name*="'+field_act_procesos_contenidomail+'"]');
            const number = $mailContenido.data('ckeditor5-id').toString();
            var editorInstance = Drupal.CKEditor5Instances.get(number);
            const data = editorInstance.getData();
            if (!data || data.trim() === '') {
              errores.push('Falta diligenciar el campo Contenido del correo personalizado');
              var $containerProcesoMailContenido = $('#' + id_container_proceso_mail_contenido);
              $containerProcesoMailContenido.addClass('error-field');
              $mailContenido.focus();
            }

            if (errores.length > 0) {
              agregarClaseError(errores);
              return false;
            }
          }

          return true;
        }

        // Añadir la clase error-field a los campos que no pasen la validación y hacer focus en el campo.
        function agregarClaseError(errores) {
          errores.forEach(function(error) {
            var $campo = $('input[name*="'+field_act_procesos_mail_user+'"]');
            $campo.addClass('error-field');
            $campo.focus();
          });
        }

        // Limpiar los campos de error
        function limpiarErrores() {
          $('input[name*="'+field_act_procesos_mail_user+'"]').removeClass('error-field');
          $('input[name*="'+field_act_procesos_mail_titulo+'"]').removeClass('error-field');
          $('#'+id_container_proceso_mail_user).removeClass('error-field');
        }


      });
    }
  };

})(jQuery, Drupal);
