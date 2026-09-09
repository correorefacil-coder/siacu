(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.gap = {
    attach: function (context, settings) {
      $('body').once().each(function() {
        $(function () {
          var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
          tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
          })
        })

        // $(document)
        // // On invisible make empty and unrequired.
        // .on('state:visible', (e) => {
        //   triggerFunction(e);
        // })
        // .on('state:required', (e) => {
        //   triggerFunction(e);
        // });
        // function triggerFunction(e) {
        //   if (e.trigger) {
        //     console.log('Global state:visible', e);

        //     const fields = $(e.target).find('textarea');
        //     fields.each(function processControls() {

        //       console.log('Global state:visible textarea', this);

        //       const $field = $(this);
        //       $field.removeAttr('required');
        //       $field.removeAttr('aria-required');


        //     });
        //   }
        // }

        $(document).ready(function() {
          // QUICKTABS ASPECTO
          $('#quicktabs-tablas_maestras').addClass('row');
          $('#quicktabs-tablas_maestras .item-list').addClass('col-md-4');
          $('#quicktabs-container-tablas_maestras').addClass('col-md-8');

          // VISTA PROGRAMAS ASPECTO
          $('.view-programas-academicos .views-exposed-form')
            .addClass('row');
          $('.view-programas-academicos .views-exposed-form .form-item-search')
            .addClass('col-md-12');
          $('.view-programas-academicos .views-exposed-form .form-item-nivel-programa')
            .addClass('col-md-4');
          $('.view-programas-academicos .views-exposed-form .form-item-modalidad')
            .addClass('col-md-4');
          $('.view-programas-academicos .views-exposed-form .form-item-field-escuela')
            .addClass('col-md-8');
          $('.view-programas-academicos .views-exposed-form .form-actions')
            .addClass('col-md-4');
          $('.view-programas-academicos .views-exposed-form .form-actions')
            .addClass('col-md-4')
          $('.view-programas-academicos .views-exposed-form .form-item-nivel-programa select')
            .css('min-height', '135px');
          $('.view-programas-academicos .views-exposed-form .form-item-modalidad select')
            .css('min-height', '135px');

          // SELECT NONE
          $('#content form.node-form .content select').each(function() {
            if ($( this ).hasClass('js-filter-list')) {
              return;
            }
            var options = $( this ).find( "option" );
            var none = false;
            options.each(function() {
              var option = $( this );
              if (option.text() == '- Ninguno -') {
                none = true;
              }
            });
            if (none == false) {
              $(this).prepend("<option value='_none'>- Ningunos -</option>");
            }
          });
        });

        $('.user-login-form').once().each(function() {
          var boton1 = $('.user-login-form a.btn-primary');
          //boton1.addClass("");
          boton1.after( '<a id="otro" style="float:right;opacity:0.5;margin-top: 40vh;display:block;" class="otro" href="#">Administrador</a>' );
          var ocultar = $('.js-form-item,#edit-submit');
          ocultar.toggle();
          $('#otro').bind('click',function() {
            boton1.hide();
            $('#otro').hide();
            ocultar.toggle("slow");
          })
        });
      });
    }
  };
})(jQuery, Drupal);
