<?php

namespace Drupal\cron_alertas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use \Drupal\node\Entity\Node;
class CronAlertasController extends ControllerBase {
  private $nameOfDay = [
    "Sunday" => 'Domingo',
    "Monday" => 'Lunes',
    "Tuesday" => 'Martes',
    "Wednesday" => 'Miércoles',
    "Thursday" => 'Jueves',
    "Friday" => 'Viernes',
    "Saturday" => 'Sábado',
  ];

  public function ejecutarAlertasEstandar() {

    $this->alertasEstandarAcreditacion();
    $this->alertasEstandarRegistroCalificado();

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Alertas Estandar ejecutadas!'),
    ];
    return $build;

  }

  private function alertasEstandarRegistroCalificado() {
    // PROCESO REGISTROS CALIFICADOS
    // Obtener divisiones con alertas especiales para registro
    $divisiones_alerta_especial = [];
    $alertas_especiales = $this->getAlertasEspeciales('registro');
    foreach ($alertas_especiales as $alerta_especial) {
      $division = $alerta_especial->field_division_academica->getValue();
      if (!empty($division)) {
        $divisiones_alerta_especial[] = $division[0]['target_id'];
      }
    }
    // Obtener programas de esas divisiones
    $programas_excluir = [];
    if (!empty($divisiones_alerta_especial)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'programas_academicos')
        ->condition('field_escuela_dep', $divisiones_alerta_especial, 'IN');
      $programas_excluir = $query->execute();
    }
    // ALERTAS ESTANDAR REGISTRO CALIFICADO ASIGNADAS AL DIA DE LA SEMANA
    $alertas = $this->getAlertasEstandarCheckDia('registro');
    // LISTADO DE TODOS LOS PROGRAMAS CON ALARMA REGISTRO CALIFICADO ACTIVA
    $programasIDs = $this->getProgramasAlertas('registro');
    // Excluir programas con alertas especiales
    if (!empty($programas_excluir)) {
      $programasIDs = array_diff($programasIDs, $programas_excluir);
    }
    foreach ($programasIDs as $key => $programaID) {
      $programa = Node::load($programaID);
      $field_fecha_vencimiento_reg_cal = $programa->field_fecha_vencimiento_reg_cal;
      $fechaVencimiento = $field_fecha_vencimiento_reg_cal->getValue();

      \Drupal::messenger()->addMessage('Registro Programa: ' . $programa->getTitle());

      foreach ($alertas as $key => $alerta) {
        $mesesAlerta = $alerta->field_tiempo_notificacion->getValue();
        $mesesAlerta = $mesesAlerta[0]['value'];

        $continuar = $alerta->field_mayor_o_igual->getValue();
        $continuar = $continuar[0]['value'];

        if (!empty($fechaVencimiento)) {

          $message = 'fechaVencimiento: ' . date('d/m/Y', strtotime($fechaVencimiento[0]['value']));
          \Drupal::messenger()->addMessage($message);

          $message = 'Registro Programa: ' . $programa->getTitle() . ' Alerta: ' . $alerta->getName() . ' -> mesesAlerta: ' . $mesesAlerta . ' Continuar: ' . $continuar;
          \Drupal::messenger()->addMessage($message);

          $enviarCorreo = null;
          if (!$continuar) {
            $enviarCorreo = $this->comparaFechaAlarma($fechaVencimiento, $mesesAlerta);
          } else {
            $enviarCorreo = $this->comparaFechaAlarmaContinuar($fechaVencimiento, $mesesAlerta);
          }

          $message = "EnviarCorreo Registro Programa: ".$programa->getTitle()." ($mesesAlerta): " . json_encode($enviarCorreo);
          \Drupal::messenger()->addMessage($message);
          if ($enviarCorreo) {
            $label = "Alerta de Renovación de Registro Calificado";
            $mensaje = $alerta->field_mensaje->getValue();
            $options = ['absolute' => TRUE];
            $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $programa->id()], $options);
            $url = $url->toString();
            $fechaTope = new DrupalDateTime($fechaVencimiento[0]['value']);
            $fechaTope->modify('- 17 months');
            $fechaTope = $fechaTope->format('d/m/Y');

            $body = str_replace(
              array(
                '{ENLACE}',
                '{FECHA_VENCIMIENTO}',
                '{FECHA_TOPE}',
                "\n"
              ),
              array(
                "<a href='$url' target='_blank'>".$programa->getTitle()."</a>",
                date('d/m/Y', strtotime($fechaVencimiento[0]['value'])),
                $fechaTope,
                '<br/>'
              ),
              $mensaje[0]['value']
            );
            $to = $this->getResponsables('registro', $programa);
            // $to = ['markus993@hotmail.com'];
            if (!empty($to)) {
              $this->sendEmail(implode(', ',$to), $label, $body);
            }
          }
        }
      }
    }
  }

  private function alertasEstandarAcreditacion() {

    // Obtener divisiones con alertas especiales para acreditación
    $divisiones_alerta_especial = [];
    $alertas_especiales = $this->getAlertasEspeciales('acreditacion');
    foreach ($alertas_especiales as $alerta_especial) {
      $division = $alerta_especial->field_division_academica->getValue();
      if (!empty($division)) {
        $divisiones_alerta_especial[] = $division[0]['target_id'];
      }
    }

    // Obtener programas de esas divisiones
    $programas_excluir = [];
    if (!empty($divisiones_alerta_especial)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'programas_academicos')
        ->condition('field_escuela_dep', $divisiones_alerta_especial, 'IN');
      $programas_excluir = $query->execute();
    }
    // ALERTAS ESTANDAR ACREDITACION ASIGNADAS AL DIA DE LA SEMANA
    $alertas = $this->getAlertasEstandarCheckDia('acreditacion');
    // LISTADO DE TODOS LOS PROGRAMAS CON ALARMA ACREDITACION ACTIVA
    $programasIDs = $this->getProgramasAlertas('acreditacion');
    // Excluir programas con alertas especiales
    if (!empty($programas_excluir)) {
      $programasIDs = array_diff($programasIDs, $programas_excluir);
    }
    foreach ($programasIDs as $key => $programaID) {
      $programa = Node::load($programaID);
      // LISTADO ACREDITACIONES VIGENTES
      $acreditaciones_vigentes = $programa->field_acreditaciones_vig;
      foreach ($acreditaciones_vigentes as $key => $acreditacion) {
        $acreditacionID = $acreditacion->getValue()['target_id'];
        $acreditacionNode = Node::load($acreditacionID);
        $fechaVencimiento = $acreditacionNode->field_fecha_vencimiento_acred;

        if (!$fechaVencimiento->isEmpty()) {

          $fechaVencimiento = $fechaVencimiento->getValue();

          \Drupal::messenger()->addMessage('Programa: ' . $programa->getTitle());
          \Drupal::messenger()->addMessage('Acreditacion: ' . $acreditacionNode->getTitle());

          $message = 'fechaVencimiento: ' . date('d/m/Y', strtotime($fechaVencimiento[0]['value']));
          \Drupal::messenger()->addMessage($message);

          foreach ($alertas as $key => $alerta) {
            $mesesAlerta = $alerta->field_tiempo_notificacion->getValue();
            $mesesAlerta = $mesesAlerta[0]['value'];

            $mayor_o_igual = $alerta->field_mayor_o_igual->getValue();
            $continuar = $mayor_o_igual[0]['value'];

            $message = 'Acreditacion: '. $acreditacionNode->getTitle().' Alerta: ' . $alerta->getName().' -> mesesAlerta: '. $mesesAlerta.' Continuar: '.$continuar;
            \Drupal::messenger()->addMessage($message);

            $enviarCorreo = null;
            if (!$continuar) {
              $enviarCorreo = $this->comparaFechaAlarma($fechaVencimiento, $mesesAlerta);
            } else {
              $enviarCorreo = $this->comparaFechaAlarmaContinuar($fechaVencimiento, $mesesAlerta);
            }
            $message = "EnviarCorreo Acreditacion ". $acreditacionNode->getTitle()." Programa: " . $programa->getTitle() . " Meses($mesesAlerta): " . json_encode($enviarCorreo);

            \Drupal::messenger()->addMessage($message);

            if ($enviarCorreo) {
              $label = "Alerta de Renovación de Acreditación";
              $mensaje = $alerta->field_mensaje->getValue();
              $options = ['absolute' => TRUE];
              $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $programa->id()], $options);
              $url = $url->toString();
              $fechaTope = new DrupalDateTime($fechaVencimiento[0]['value']);
              $fechaTope->modify('- 17 months');
              $fechaTope = $fechaTope->format('d/m/Y');

              $body = str_replace(
                array(
                  '{ENLACE}',
                  '{FECHA_VENCIMIENTO}',
                  '{FECHA_TOPE}',
                  '{DIA}',
                  "\n"
                ),
                array(
                  "<a href='$url' target='_blank'>".$programa->getTitle()."</a>",
                  date('d/m/Y', strtotime($fechaVencimiento[0]['value'])),
                  $fechaTope,
                  $this->getMinimoDateAcreditacion($fechaVencimiento),
                  '<br/>'
                ),
                $mensaje[0]['value']
              );
              $to = $this->getResponsables('acreditacion', $programa);
              // $to = ['markus993@hotmail.com'];
              if (!empty($to)) {
                $this->sendEmail(implode(', ',$to), $label, $body);
              }
            }
          }
        }
      }
    }
  }

  private function getProgramasAlertas($proceso){
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    switch ($proceso) {
      case 'registro':
        $query->condition('field_alarmas_reg_calificado', TRUE);
        break;

      case 'acreditacion':
        $query->condition('field_alarmar_venc_acreditacion', TRUE);
        break;
    }
    return $query->execute();
  }

  private function getProgramasAlertasEspeciales($proceso, $division){
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'programas_academicos');
    switch ($proceso) {
      case 'registro':
        $query->condition('field_alarmas_reg_calificado', TRUE);
        break;

      case 'acreditacion':
        $query->condition('field_alarmar_venc_acreditacion', TRUE);
        break;
    }
    $query->condition('field_escuela_dep', $division[0]['target_id']);
    return $query->execute();
  }

  private function getAlertasEstandarCheckDia($alerta){
    // CHECK DIA DE LA SEMANA
    $nameOfDayEn = date('l');
    $nameOfDayEs = $this->nameOfDay[$nameOfDayEn];

    $properties = [
      'vid' => 'alertas_estandar',
      'field_enviar_dia_de_la_semana' => $nameOfDayEs,
      'field_tipo_alerta' => $alerta,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
  }

  public function ejecutarAlertasEspeciales() {

    $this->alertasEspecialesRegistroCalificado();
    $this->alertasEspecialesAcreditacion();

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Alertas Especiales ejecutadas!'),
    ];
    return $build;
  }

  private function alertasEspecialesRegistroCalificado() {

    // PROCESO REGISTROS CALIFICADOS
    $alerta = 'registro';
    // ALERTAS ESTANDAR REGISTRO CALIFICADO ASIGNADAS AL DIA DE LA SEMANA
    $alertas = $this->getAlertasEspecialesCheckDia('registro');


    foreach ($alertas as $key => $alerta) {

      $mesesAlerta = $alerta->field_tiempo_notificacion->getValue();
      $mesesAlerta = $mesesAlerta[0]['value'];

    // LISTADO DE TODOS LOS PROGRAMAS CON ALARMA REGISTRO CALIFICADO ACTIVA
      $division_academica = $alerta->field_division_academica->getValue();
      $programasIDs = $this->getProgramasAlertasEspeciales('registro', $division_academica);

      foreach ($programasIDs as $key => $programaID) {
        $programa = Node::load($programaID);
        $field_fecha_vencimiento_reg_cal = $programa->field_fecha_vencimiento_reg_cal;
        $fechaVencimiento = $field_fecha_vencimiento_reg_cal->getValue();

        if (!empty($fechaVencimiento)) {
          $enviarCorreo = $this->comparaFechaAlarma($fechaVencimiento, $mesesAlerta);

          if ($enviarCorreo) {
            $label = "Alerta Especial de Renovación de Registro Calificado";
            $mensaje = $alerta->field_mensaje->getValue();
            $options = ['absolute' => TRUE];
            $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $programa->id()], $options);
            $url = $url->toString();
            $fechaTope = new DrupalDateTime($fechaVencimiento[0]['value']);
            $fechaTope->modify('- 17 months');
            $fechaTope = $fechaTope->format('d/m/Y');

            $body = str_replace(
              array(
                '{ENLACE}',
                '{FECHA_VENCIMIENTO}',
                '{FECHA_TOPE}',
                "\n"
              ),
              array(
                "<a href='$url' target='_blank'>".$programa->getTitle()."</a>",
                date('d/m/Y', strtotime($fechaVencimiento[0]['value'])),
                $fechaTope,
                '<br/>'
              ),
              $mensaje[0]['value']
            );
            $to = $this->getResponsables('registro', $programa);
            // $to = ['markus993@hotmail.com'];
            if (!empty($to)) {
              $this->sendEmail(implode(', ', $to), $label, $body);
            }
          }
        }
      }
    }
  }

  private function alertasEspecialesAcreditacion() {

    // PROCESO ACREDITACION

    // ALERTAS ESTANDAR ACREDITACION ASIGNADAS AL DIA DE LA SEMANA
    $alertas = $this->getAlertasEspecialesCheckDia('acreditacion');

    foreach ($alertas as $key => $alerta) {

      $mesesAlerta = $alerta->field_tiempo_notificacion->getValue();
      $mesesAlerta = $mesesAlerta[0]['value'];

      // LISTADO DE TODOS LOS PROGRAMAS CON ALARMA ACREDITACION ACTIVA
      $division_academica = $alerta->field_division_academica->getValue();
      $programasIDs = $this->getProgramasAlertasEspeciales('acreditacion', $division_academica);

      foreach ($programasIDs as $key => $programaID) {
        $programa = Node::load($programaID);

        // LISTADO ACREDITACIONES VIGENTES
        $acreditaciones_vigentes = $programa->field_acreditaciones_vig;
        foreach ($acreditaciones_vigentes as $key => $acreditacion) {

          $acreditacionID = $acreditacion->getValue()['target_id'];
          $acreditacionNode = Node::load($acreditacionID);
          $fechaVencimiento = $acreditacionNode->field_fecha_vencimiento_acred;

          if (!empty($fechaVencimiento)) {
            $fechaVencimiento = $fechaVencimiento->getValue();

            $enviarCorreo = $this->comparaFechaAlarma($fechaVencimiento, $mesesAlerta);

            if ($enviarCorreo) {
              $label = "Alerta Especial de Renovación de Acreditación";
              $mensaje = $alerta->field_mensaje->getValue();
              $options = ['absolute' => TRUE];
              $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $programa->id()], $options);
              $url = $url->toString();
              $fechaTope = new DrupalDateTime($fechaVencimiento[0]['value']);
              $fechaTope->modify('- 17 months');
              $fechaTope = $fechaTope->format('d/m/Y');

              $body = str_replace(
                array(
                  '{ENLACE}',
                  '{FECHA_VENCIMIENTO}',
                  '{FECHA_TOPE}',
                  '{DIA}',
                  "\n"
                ),
                array(
                  "<a href='$url' target='_blank'>".$programa->getTitle()."</a>",
                  date('d/m/Y', strtotime($fechaVencimiento[0]['value'])),
                  $fechaTope,
                  $this->getMinimoDateAcreditacion($fechaVencimiento),
                  '<br/>'
                ),
                $mensaje[0]['value']
              );
              $to = $this->getResponsables('acreditacion', $programa);
              // $to = ['markus993@hotmail.com'];
              if (!empty($to)) {
                $this->sendEmail(implode(', ', $to), $label, $body);
              }
            }
          }
        }
      }
    }
  }

  private function getAlertasEspecialesCheckDia($alerta){
    // CHECK DIA DE LA SEMANA
    $nameOfDayEn = date('l');
    $nameOfDayEs = $this->nameOfDay[$nameOfDayEn];

    $properties = [
      'vid' => 'alertas_especiales',
      'field_enviar_dia_de_la_semana' => $nameOfDayEs,
      'field_tipo_alerta' => $alerta,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
  }

  private function getAlertasEspeciales($alerta){

    $properties = [
      'vid' => 'alertas_especiales',
      'field_tipo_alerta' => $alerta,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
  }

  private function comparaFechaAlarma($fechaVencimiento, $mesesAlerta){

    // Verifica semana de alerta
    $diaAntes = new DrupalDateTime();
    $diaAntes->modify('+'.$mesesAlerta.' months');
    $diaAntes->modify('-3 days');
    $diaAntesStamp = $diaAntes->getTimestamp();

    $diaDespues = new DrupalDateTime();
    $diaDespues->modify('+'.$mesesAlerta.' months');
    $diaDespues->modify('+4 days');
    $diaDespuesStamp = $diaDespues->getTimestamp();

    $fechaVencimiento = new DrupalDateTime($fechaVencimiento[0]['value']);
    $fechaVencimientoStamp = $fechaVencimiento->getTimestamp();

    // DEBUG
    // echo "<pre>";
    // var_dump('diaAntes: '.$diaAntes->format('d/m/Y'));
    // var_dump('fechaVencimiento '. $fechaVencimiento->format('d/m/Y'));
    // var_dump('diaDespues '. $diaDespues->format('d/m/Y'));
    // dd($diaDespues);
    // echo "</pre>";

    // CHECK LAPSO DE TIEMPO DE ALARMA 1 SEMANA
    if (
      ($fechaVencimientoStamp >= $diaAntesStamp) &&
      ($fechaVencimientoStamp < $diaDespuesStamp)
    ) {
      return TRUE;
    } else {
      return FALSE;
    }

  }

  private function comparaFechaAlarmaContinuar($fechaVencimiento, $mesesAlerta)
  {

    // Verifica semana de alerta
    $diaAlerta = new DrupalDateTime();
    $diaAlerta->modify('+' . $mesesAlerta . ' months');
    $diaAlertaStamp = $diaAlerta->getTimestamp();

    $fechaVencimiento = new DrupalDateTime($fechaVencimiento[0]['value']);
    $fechaVencimientoStamp = $fechaVencimiento->getTimestamp();

    // DEBUG
    // echo "<pre>";
    // var_dump('diaAlerta: '.$diaAlerta->format('d/m/Y'));
    // var_dump('fechaVencimiento '. $fechaVencimiento->format('d/m/Y'));
    // echo "</pre>";

    /// COMPARACION CON CONTINUAR TRUE
    if ($fechaVencimientoStamp <= $diaAlertaStamp) {
      return TRUE;
    } else {
      return FALSE;
    }

  }

  private function sendEmail($to, $label, $body){

    $mailManager = \Drupal::service('plugin.manager.mail');
    $params['message'] = $body;
    $params['subject'] = $label;

    $result = $mailManager->mail('cron_alertas', 'correo_alerta', $to, 'en', $params, NULL, TRUE);

    if ($result['result'] !== true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
      \Drupal::messenger()->addError($message);
      \Drupal::logger('custom_mail')->error($message);
      return;
    }

    $time = new DrupalDateTime();
    $timeStamp = $time->getTimestamp();
    $message = "MailManager - To: " . json_encode($to) . ' // Label: ' . $label.' Stamp'. $timeStamp;
    \Drupal::messenger()->addMessage($message);
    return;

  }

  private function getMinimoDateAcreditacion($fechaVencimiento){

    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'alertas_estandar')
      ->condition('field_tipo_alerta', 'acreditacion')
      ->sort('field_tiempo_notificacion', 'ASC')
      ->range(0, 1);
    $out = $query->execute();
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(reset($out));
    $meses = $term->field_tiempo_notificacion->getValue();

    $fechaMinima = new DrupalDateTime($fechaVencimiento[0]['value']);
    $fechaMinima->modify('-'.$meses[0]['value'].' months');
    return $fechaMinima = $fechaMinima->format('d/m/Y');

  }

  private function getResponsables($proceso, $programa){

    switch ($proceso) {
      case 'registro':
        $responsables = $programa->field_responsables_reg_calificad;
        break;

      case 'acreditacion':
        $responsables = $programa->field_responsables_acreditacion;
        break;
    }
    if (empty($responsables)) {
      return FALSE;
    }

    $destinatarios = [];
    foreach ($responsables->getValue() as $key => $responsable) {
      $user = \Drupal\user\Entity\User::load($responsable['target_id']);
      $destinatarios[] = $user->getEmail();
    }
    return $destinatarios;

  }

}
