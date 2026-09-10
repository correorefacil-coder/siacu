<?php

namespace Drupal\finalizar_actividades\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\activar_proceso\Controller\FinalizarProcesoController;
use Drupal\Component\Render\FormattableMarkup;

class FinalizarActividadController extends ControllerBase {

  public const SIN_INICIAR = "sin_iniciar";
  public const PROCESO = "en_proceso";
  public const FINALIZADO = "finalizada";
  public const SIN_APROBACION = "finalizada_sin_aprob";
  public const RECHAZADA = "rechazada";

  public function finalizarActividad($node) {

    // Verificacion de campos
    $falta = $this->faltaFechasRealyResponsable($node);

    if ($falta != FALSE ) {
      // Faltan campos por diligenciar.
      foreach ($falta as $key => $message) {
        \Drupal::messenger()->addError('No se puede finalizar la actividad.');
        \Drupal::messenger()->addError($message);
      }
    }else{
      // Finaliza actividad e inicia la siguiente en cola.
      $this->siguienteActividad($node);
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
    return new RedirectResponse($url->toString());
  }

  public function iniciarActividadProceso($actividad) {

    // Apertura y notificacion de actividad siguiente
    $this->setEstadoProceso($actividad, Self::PROCESO);

    //Envio notificacion
    $this->enviarNotificacion(null, $actividad);

  }

  public function iniciarActividad($actividad, $actividadSiguiente) {

    // Apertura y notificacion de actividad siguiente
    $this->setEstadoProceso($actividadSiguiente, Self::PROCESO);

    //Envio notificacion
    $this->enviarNotificacion($actividad, $actividadSiguiente);

  }

  private function enviarNotificacion($actividad, $actividadSiguiente){

    // Flag primera actividad de proceso
    $primera = FALSE;
    if (empty($actividad)) {
      $primera = TRUE;
      $actividad = $actividadSiguiente;
    }

    // Tipo de notificacion
    $enviarNotificacion = $actividad->field_act_procesos_mail_sino->getValue();
    $enviarNotificacion = reset($enviarNotificacion);

    // LINK A Actividad
    $link = $actividadSiguiente->toLink(NULL, 'canonical', ['absolute' => true, 'https' => true]);

    // VariableS de reemplazo en template
    $variables = array(
      '@link' => $link->toString(),
      '@observaciones' => 'Ninguna',
      '@entradas' => 'Ninguna',
      '@salidas' => 'Ninguna',
    );

    // Flag para correo Primera actividad
    if ($primera) {
      $enviarNotificacion['value'] = 'Sí, un correo automático';
    }

    switch ($enviarNotificacion['value']) {

      case 'Sí, un correo automático':
        // Titulo y contenido mail
        $tituloMail = 'Puede iniciar la actividad - '. $actividadSiguiente->getTitle();
        $contenidoMail = '<div class="wrapper-mail">
          <p>
            Señores <strong>responsables de actividades y responsable academico,</strong> se les informa que :
          </p>
          <p>
            El plan de trabajo <strong>@linkProc</strong> se encuentra publicado y con actividades asignadas.
          </p>
        </div>
        <div class="wrapper-mail">
          <p>
            Señor(a) <strong>@nombreResponsableAct :</strong>
          </p>
          <p>
            Dentro del proceso <strong>@tituloProc</strong>, puede dar inicio a la actividad correspondiente a <strong>@link</strong>, la cual debe ser ejecutada entre <strong>@fechaInicio</strong> y <strong>@fechaFinal</strong>.
          </p>
          <p>
            Para iniciar esta actividad debe contar con <strong>@entradas</strong> y al finalizar debe entregar el siguiente resultado <strong>@salidas</strong>
          </p>
          <p>
            Para desarrollar esta actividad debe tener en cuenta las siguientes observaciones: <strong>@observaciones</strong>
          </p>
        </div>
        ';
        // Datos Responsable Proceso
        $proceso = $this->findProcesoByActividad($actividad);
        $mailResponsableProc = '';
        $responsableProcObj = NULL;

        if ($proceso && $proceso->hasField('field_proceso_responsable_academ') && !$proceso->field_proceso_responsable_academ->isEmpty()) {
          $responsableProc = $proceso->field_proceso_responsable_academ->referencedEntities();
          if (!empty($responsableProc)) {
            $responsableProcObj = reset($responsableProc);
            $mailResponsableProc = $responsableProcObj->getEmail();
            $variables['@mailResponsableProc'] = $mailResponsableProc;
            $variables['@nombreResponsableProc'] = $this->getNombreUsuario($responsableProcObj);
          }
        }
        $variables['@tituloProc'] = $proceso ? $proceso->getTitle() : '';
        if ($proceso) {
          $linkProc = $proceso->toLink(NULL, 'canonical', ['absolute' => true, 'https' => true]);
          $variables['@linkProc'] = $linkProc->toString();
        } else {
          $variables['@linkProc'] = '';
        }

        // Datos Actividad
        $mailResponsableAct = '';
        $responsableAcadObj = NULL;
        if ($actividad->hasField('field_act_procesos_responsable') && !$actividad->field_act_procesos_responsable->isEmpty()) {
          $responsableAcad = $actividad->field_act_procesos_responsable->referencedEntities();
          if (!empty($responsableAcad)) {
            $responsableAcadObj = reset($responsableAcad);
            $mailResponsableAct = $responsableAcadObj->getEmail();
            $variables['@mailResponsableAct'] = $mailResponsableAct;
            $variables['@nombreResponsableAct'] = $this->getNombreUsuario($responsableAcadObj);
          }
        }
        if (!isset($variables['@mailResponsableAct'])) {
          $variables['@mailResponsableAct'] = '';
          $variables['@nombreResponsableAct'] = '';
        }

        $observaciones = $actividad->field_act_procesos_observaciones->getValue();
        if (!empty($observaciones)) {
          $observaciones = reset($observaciones);
          $variables['@observaciones'] = new formattablemarkup($observaciones['value'], []);
        }

        $variables['@fechaInicio'] = '';
        $fechaInicio = $actividad->field_act_procesos_fecha_inicio->getValue();
        if (!empty($fechaInicio)) {
          $fechaInicio = reset($fechaInicio);
          $variables['@fechaInicio'] = isset($fechaInicio['value']) ? $fechaInicio['value'] : '';
        }

        $variables['@fechaFinal'] = '';
        $fechaFinal = $actividad->field_act_procesos_fecha_final->getValue();
        if (!empty($fechaFinal)) {
          $fechaFinal = reset($fechaFinal);
          $variables['@fechaFinal'] = isset($fechaFinal['value']) ? $fechaFinal['value'] : '';
        }

        $entradas = $actividad->field_act_procesos_entradas->getValue();
        if (!empty($entradas)) {
          $entradas = reset($entradas);
          $variables['@entradas'] = new formattablemarkup($entradas['value'], []);
        }

        $salidas = $actividad->field_act_procesos_salidad->getValue();
        if (!empty($salidas)) {
          $salidas = reset($salidas);
          $variables['@salidas'] = new formattablemarkup($salidas['value'], []);
        }

        // Destinatarios
        $mailSend = '';
        if (filter_var($mailResponsableProc, FILTER_VALIDATE_EMAIL)) {
          $mailSend = $mailResponsableProc;
        } elseif ($responsableProcObj) {
          $message = t('Error al enviar correo de notificacion, el usuario @username no tiene correo electronico configurado.', array('@username' => $responsableProcObj->getAccountName()));
          \Drupal::messenger()->addError($message);
          \Drupal::logger('finalizar_Actividad')->error($message);
        }

        if (filter_var($mailResponsableAct, FILTER_VALIDATE_EMAIL)) {
          $mailSend = !empty($mailSend) ? $mailSend . ',' . $mailResponsableAct : $mailResponsableAct;
        } elseif ($responsableAcadObj) {
          $message = t('Error al enviar correo de notificacion, el usuario @username no tiene correo electronico configurado.', array('@username' => $responsableAcadObj->getAccountName()));
          \Drupal::messenger()->addError($message);
          \Drupal::logger('finalizar_Actividad')->error($message);
        }
        break;

      case 'Sí, un correo personalizado':
        // Titulo y contenido mail
        $tituloMail = $actividad->field_act_procesos_mail_titulo->getValue();
        $tituloMail = reset($tituloMail);
        $tituloMail = $tituloMail['value'];
        $contenidoMail = $actividad->field_act_procesos_contenidomail->getValue();
        $contenidoMail = reset($contenidoMail);
        $contenidoMail = $contenidoMail['value'];
        // Destinatarios
        $destinatarios = $actividad->field_act_procesos_mail_user->referencedEntities();
        $mailSend = '';
        foreach ($destinatarios as $key => $destinatario) {
          if (filter_var($destinatario->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $mailSend = $mailSend . ',' . $destinatario->getEmail();
          }else{
            $message = t('Error al enviar correo de notificacion, el usuario @username no tiene correo electronico configurado.', array('@username' => $destinatario->getAccountName()));
            \Drupal::messenger()->addError($message);
            \Drupal::logger('finalizar_Actividad')->error($message);
          }
        }
        break;

      // No envia correo
      default:
        return;
    }
    if (!empty($mailSend)) {
      $body = t($contenidoMail, $variables);
      $this->sendEmail($mailSend, $tituloMail, $body, 'correo_finalizar_actividades');
    }else {
      $message = 'Error al enviar correo de notificacion ('.$tituloMail.'), no hay correos destinatarios.';
      \Drupal::messenger()->addError($message);
      \Drupal::logger('finalizar_Actividad')->error($message);
    }
  }

  private function faltaFechasRealyResponsable($actividad){

    $inicior = $actividad->field_act_procesos_fecha_inicior->getValue();
    $finalr = $actividad->field_act_procesos_fecha_finalr->getValue();
    $responsable = $actividad->field_act_procesos_responsable->referencedEntities();

    $falta = FALSE;
    $message = [];

    if (empty($inicior)) {
      $falta = TRUE;
      $message[] = '- Falta diligenciar el campo Fecha de inicio Real';
    }
    if (empty($finalr)) {
      $falta = TRUE;
      $message[] = '- Falta diligenciar el campo Fecha de final Real';
    }
    if (empty($responsable)) {
      $falta = TRUE;
      $message[] = '- Falta diligenciar el campo Responsable';
    }

    // Validar campos de correo personalizado si está habilitado
    $mail_sino = $actividad->field_act_procesos_mail_sino->getValue();
    if (!empty($mail_sino) && $mail_sino[0]['value'] == 'Sí, un correo personalizado') {
      $mail_users = $actividad->field_act_procesos_mail_user->referencedEntities();
      if (empty($mail_users)) {
        $falta = TRUE;
        $message[] = '- Falta diligenciar el campo Correo personalizado';
      }

      $mail_titulo = $actividad->field_act_procesos_mail_titulo->getValue();
      if (empty($mail_titulo) || empty($mail_titulo[0]['value']) || trim($mail_titulo[0]['value']) === '') {
        $falta = TRUE;
        $message[] = '- Falta diligenciar el campo Asunto del correo personalizado';
      }

      $mail_contenido = $actividad->field_act_procesos_contenidomail->getValue();
      if (empty($mail_contenido) || empty($mail_contenido[0]['value']) || trim($mail_contenido[0]['value']) === '') {
        $falta = TRUE;
        $message[] = '- Falta diligenciar el campo Contenido del correo personalizado';
      }
    }

    if ($falta) {
      return $message;
    }
    return $falta;

  }

  public function faltaFechasProgramadas($actividad){

    $inicio = $actividad->field_act_procesos_fecha_inicio->getValue();
    $final = $actividad->field_act_procesos_fecha_final->getValue();
    $responsable = $actividad->field_act_procesos_responsable->referencedEntities();

    // LINK A Actividad

    $link = $actividad->toLink(NULL, 'canonical', ['absolute' => true, 'https' => true]);

    $falta = FALSE;
    $message = [];

    if (empty($inicio)) {
      $falta = TRUE;
      $message[] = 'Falta diligenciar el campo Fecha de inicio programada en la actividad '.$link->toString();
    }
    if (empty($final)) {
      $falta = TRUE;
      $message[] = 'Falta diligenciar el campo Fecha de final programada en la actividad '.$link->toString();
    }
    if (empty($responsable)) {
      $falta = TRUE;
      $message[] = 'Falta diligenciar el campo Responsable en la actividad '.$link->toString();
    }

    if ($falta) {
      return $message;
    }
    return $falta;

  }

  private function findProcesoByActividad($actividad){
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $ids = $nodeStorage->getQuery()
      ->condition('status', 1)
      ->condition('type', 'procesos')
      ->condition('field_proceso_actividades', $actividad->id())
      ->pager(1) // limit 1 item
      ->execute();
    $proceso = $nodeStorage->loadMultiple($ids);
    return $proceso = reset($proceso);
  }

  private function siguienteActividad(NodeInterface $actividad){

    $estado = $this->getEstadoProceso($actividad);

    if ($estado == Self::PROCESO) {

      // Finalizacion de Actitidad
      $this->setEstadoProceso($actividad, Self::FINALIZADO);
      \Drupal::messenger()->addStatus('La actividad ha sido finalizada exitosamente.');

      // Busca Proceso Padre
      $proceso = $this->findProcesoByActividad($actividad);

      // Identificacion de actividad siguiente en el Proceso Padre
      $actividades = $proceso->field_proceso_actividades->referencedEntities();
      $next = false;
      $actividadSiguiente = null;
      foreach ($actividades as $key => $actividadProc) {
        if ($next) {
          $actividadSiguiente = $actividadProc;
          break;
        } else {
          if ($actividad->id() == $actividadProc->id()) {
            $next = TRUE;
          }
        }
      }

      // SI ES ULTIMA ACTIVIDAD, FINALIZAR PROCESO, SIN ACTIVAR ACTIVIDAD SIGUIENTE
      if (empty($actividadSiguiente)) {
        $finalizarProceso = new FinalizarProcesoController;
        $finalizarProceso->finalizarProceso($proceso);
        return;
      }

      // Apertura y notificacion de actividad siguiente
      $this->iniciarActividad($actividad, $actividadSiguiente);

    }

  }

  private function getNombreUsuario($user){
    $usuario = $user->getDisplayName();
    $fields = $user->getFields();
    $firstName = $fields['field_primer_nombre']->getValue();
    $lastName = $fields['field_primer_apellido']->getValue();

    if (!empty($firstName) && !empty($lastName)) {
      $usuario = $firstName[0]['value'].' '.$lastName[0]['value'];
    }
    return $usuario;
  }

  public function getEstadoProceso(NodeInterface $actividad){
    $fields = $actividad->getFields();
    $field_estado = $actividad->field_act_procesos_estado->getValue();
    $field_estado = reset($field_estado);
    if (empty($field_estado['value'])) {
      return FALSE;
    }
    return $estado = $field_estado['value'];
  }

  private function setEstadoProceso(NodeInterface $actividad, String $estado){
    $fields = $actividad->getFields();
    $field_estado = $actividad->field_act_procesos_estado->setValue($estado);
    return $actividad->save();
  }

  private function sendEmail($to, $label, $body, $template)
  {

    $mailManager = \Drupal::service('plugin.manager.mail');
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $params['message'] = $body;
    $params['subject'] = $label;

    $result = $mailManager->mail('finalizar_Actividades', $template, $to, 'en', $params, NULL, TRUE);
    if ($result['result'] !== true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
      \Drupal::messenger()->addError($message);
      \Drupal::logger('custom_mail')->error($message);
      return;
    }
  }

  /**
   * Valida los campos de correo personalizado.
   *
   * @param \Drupal\node\NodeInterface $actividad
   *   La actividad a validar.
   *
   * @return array
   *   Array con errores encontrados, vacío si no hay errores.
   */
  public function validarCamposCorreoPersonalizado($actividad) {
    $errores = [];

    // Verificar si está habilitado el correo personalizado
    $mail_sino = $actividad->field_act_procesos_mail_sino->getValue();
    if (empty($mail_sino) || $mail_sino[0]['value'] != 'Sí, un correo personalizado') {
      return $errores; // No hay correo personalizado, no hay errores
    }

    // Validar campo de usuarios destinatarios
    $mail_users = $actividad->field_act_procesos_mail_user->referencedEntities();
    if (empty($mail_users)) {
      $errores[] = 'Falta diligenciar el campo Correo personalizado';
    }

    // Validar campo de título del correo
    $mail_titulo = $actividad->field_act_procesos_mail_titulo->getValue();
    if (empty($mail_titulo) || empty($mail_titulo[0]['value']) || trim($mail_titulo[0]['value']) === '') {
      $errores[] = 'Falta diligenciar el campo Asunto del correo personalizado';
    }

    // Validar campo de contenido del correo
    $mail_contenido = $actividad->field_act_procesos_contenidomail->getValue();
    if (empty($mail_contenido) || empty($mail_contenido[0]['value']) || trim($mail_contenido[0]['value']) === '') {
      $errores[] = 'Falta diligenciar el campo Contenido del correo personalizado';
    }

    return $errores;
  }

    /**
   * Route title callback.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The title.
   */
  public function getDynamicTabTitle(NodeInterface $actividad) {

    if ($actividad->bundle() == 'actividades_procesos') {
      $fields = $actividad->getFields();
      $field_estado = $actividad->field_act_procesos_estado->getValue();
      if (empty($field_estado)) {
        return false;
      }
      $estado = $field_estado[0]['value'];
      switch ($estado) {
        case self::SIN_INICIAR :
          return false;
          break;
        case self::PROCESO :
          return 'Finalizar';
          break;
        case self::FINALIZADO :
          return false;
          break;
        case self::SIN_APROBACION :
          return false;
          break;
        case self::RECHAZADA :
          return false;
          break;

        default:
          return false;
          break;
      }
    }

  }

}

