(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.confirmacionRespuestaOffice = {
    attach: function (context, settings) {

      $('#respuestaDrive').once().each(function() {

        $(document).ready(function() {
          var respuesta = false;
          var nodeid = $('#respuestaDrive').data('nodeid');
          loopRespuestaDrive(nodeid);
        });
      });

      function loopRespuestaDrive(nodeid) {
        setTimeout(() => {
          consultaRespuestaDrive(nodeid)
        }, 7000);
      }

      function consultaRespuestaDrive(nodeid) {
        $('.message').html('consultando...');
        fetch('/node/'+nodeid+'/ajaxRespuestaDrive')
          .then(response => response.json())
          .then(repos => {
            console.log(repos);
            if (repos.cargado == false) {
              loopRespuestaDrive(nodeid);
              setTimeout(() => {
                $('.message').html('Sin confirmacion.<br>Proximo intento en 5 segundos.');
              }, 1000);
            }else{

              $('.loader').hide();
              if (repos.mensaje === "") {
                $('.message').html('Confirmado.<br> Redirigiendo...');
                setTimeout(() => {
                  window.location.href = '/node/'+nodeid;
                }, 1000);
              }else{
                $('.message').html(repos.mensaje);
              }
            }
          })
          .catch(err => console.log(err))
      }
    }
  };
})(jQuery, Drupal);
