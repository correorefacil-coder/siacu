<?php

namespace Drupal\activar_proceso\Controller;

use Drupal\finalizar_actividades\Controller\FinalizarActividadController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\activar_proceso\ProcesosInterface;
use Drupal\activar_proceso\ProcesosTrait;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;


class ActivarProcesoController extends ControllerBase implements ProcesosInterface {

  use ProcesosTrait;

  public function activarProceso($node) {

    // Listado de actividades
    $actividades = $node->field_proceso_actividades->referencedEntities();
    $controller = new FinalizarActividadController;

    // Checkeo de campos en las actividades
    $faltaGlobal = FALSE;
    foreach ($actividades as $key => $actividad) {
      $falta = $controller->faltaFechasProgramadas($actividad);
      if ($falta != FALSE ) {
          $faltaGlobal = TRUE;
        // Faltan campos por diligenciar.
        foreach ($falta as $key => $message) {
          \Drupal::messenger()->addError(Markup::create('No se puede activar el proceso.'));
          \Drupal::messenger()->addError(Markup::create($message));
        }
      }
    }
    // Activa proceso si no faltan campos de actividades
    if (!$faltaGlobal) {
      // Actualiza campo Estado

      // TOMA PRIMERA ACTIVIDAD
      $actividad = reset($actividades);
      // Activa la primera actividad
      if (!empty($actividad)) {
        $this->activaProceso($node);
        $controller->iniciarActividadProceso($actividad);
      }else {
        \Drupal::messenger()->addError(Markup::create('No se puede activar el proceso.'));
        \Drupal::messenger()->addError(Markup::create('El proceso no tiene actividades programadas'));
      }
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
    return new RedirectResponse($url->toString());
  }

  private function activaProceso(NodeInterface $proceso){

      $estado = $this->getEstadoProceso($proceso);

    if ($estado == Self::INICIADO) {

      \Drupal::messenger()->addStatus('El Proceso ha sido activado exitosamente.');
      
      // Variables comunes para reemplazos
      $nombre_proceso = $proceso->getTitle();
      $nombre_programa = '';
      
      if ($proceso->hasField('field_procesos_pa_existente') && !$proceso->get('field_procesos_pa_existente')->isEmpty()) {
        $programa_entities = $proceso->get('field_procesos_pa_existente')->referencedEntities();
        if (!empty($programa_entities)) {
          $nombre_programa = reset($programa_entities)->label();
        }
      } elseif ($proceso->hasField('field_procesos_pa') && !$proceso->get('field_procesos_pa')->isEmpty()) {
        $programa_entities = $proceso->get('field_procesos_pa')->referencedEntities();
        if (!empty($programa_entities)) {
          $nombre_programa = reset($programa_entities)->label();
        }
      }

      // LINK A PROCESO
      $link = $proceso->toLink(NULL, 'canonical', ['absolute' => true, 'https' => true]);
      $link = $link->toString();

      // OBTENER CORREOS DE DESTINATARIOS
      
      // 1. RESPONSABLES DEL PROCESO
      $mailResponsableProc = '';
      $usuarioGestor = '';
      if (!$proceso->field_proceso_responsable->isEmpty()) {
        $responsableGestorEntity = $proceso->field_proceso_responsable->referencedEntities();
        $mailResponsableProc = $responsableGestorEntity[0]->getEmail();
        $usuarioGestor = $this->getNombreUsuario($responsableGestorEntity[0]);
      }

      // 2. RESPONSABLE DE LA ACTIVIDAD
      $mailResponsableAct = '';
      $usuarioAcademia = '';
      if (!$proceso->field_proceso_responsable_academ->isEmpty()) {
        $responsableAcademiaEntity = $proceso->field_proceso_responsable_academ->referencedEntities();
        $mailResponsableAct = $responsableAcademiaEntity[0]->getEmail();
        $usuarioAcademia = $this->getNombreUsuario($responsableAcademiaEntity[0]);
      }

      // ENVÍO AL RESPONSABLE DEL PROCESO
      if (filter_var($mailResponsableProc, FILTER_VALIDATE_EMAIL)) {
        $tipo_notificacion_proc = 'automatico';
        if ($proceso->hasField('field_tipo_notif_proceso') && !$proceso->get('field_tipo_notif_proceso')->isEmpty()) {
          $tipo_notificacion_proc = $proceso->get('field_tipo_notif_proceso')->value;
        }

        if ($tipo_notificacion_proc === 'personalizado' && $proceso->hasField('field_correo_proceso') && !$proceso->get('field_correo_proceso')->isEmpty()) {
          $messageIn = $proceso->get('field_correo_proceso')->value;
          $replacements = [
            '{PROCESO}' => $nombre_proceso,
            '{PROGRAMA}' => $nombre_programa,
            '@usuario' => $usuarioGestor,
            '@link' => $link,
            '@responsable' => $mailResponsableAct,
          ];
          $messageOut = Markup::create(str_replace(array_keys($replacements), array_values($replacements), $messageIn));
        } else {
          $messageIn = '<div class="wrapper-mail">
            <p>Señor(a) <strong>@usuario :</strong></p>
            <p>Reciba un cordial saludo</p>
            <p>El plan de trabajo <strong>@link</strong> se encuentra disponible para su revisión y actualización de las actividades que este proceso conlleva.</p>
            <p>Una vez finalizado el plan de trabajo por favor enviarlo a la Dirección de Calidad y Proyectos Académicos (DCPA) por medio de la opción "Enviar plan de trabajo a DCPA" del sistema para su aprobación final.</p>
            <p>Si tiene dudas comuníquese con el responsable de la oficina gestora: @responsable</p>
          </div>';
          $values = ['@usuario' => $usuarioGestor, '@link' => $link, '@responsable' => $mailResponsableAct];
          $messageOut = t($messageIn, $values);
        }
        $this->sendEmail($mailResponsableProc, "Proceso Activado - Responsables del Proceso", $messageOut, 'correo_activar_proceso');
      }

      // ENVÍO AL RESPONSABLE DE LA ACTIVIDAD
      if (filter_var($mailResponsableAct, FILTER_VALIDATE_EMAIL)) {
        $tipo_notificacion_act = 'automatico';
        if ($proceso->hasField('field_tipo_notif_actividad') && !$proceso->get('field_tipo_notif_actividad')->isEmpty()) {
          $tipo_notificacion_act = $proceso->get('field_tipo_notif_actividad')->value;
        }

        if ($tipo_notificacion_act === 'personalizado' && $proceso->hasField('field_correo_actividad') && !$proceso->get('field_correo_actividad')->isEmpty()) {
          $messageIn = $proceso->get('field_correo_actividad')->value;
          $replacements = [
            '{PROCESO}' => $nombre_proceso,
            '{PROGRAMA}' => $nombre_programa,
            '@usuario' => $usuarioAcademia,
            '@link' => $link,
            '@responsable' => $mailResponsableProc,
          ];
          $messageOut = Markup::create(str_replace(array_keys($replacements), array_values($replacements), $messageIn));
        } else {
          $messageIn = '<div class="wrapper-mail">
            <p>Señor(a) <strong>@usuario :</strong></p>
            <p>Reciba un cordial saludo</p>
            <p>El plan de trabajo <strong>@link</strong> se encuentra disponible. Tiene una actividad asignada a su cargo.</p>
            <p>Si tiene dudas comuníquese con los responsables del proceso: @responsable</p>
          </div>';
          $values = ['@usuario' => $usuarioAcademia, '@link' => $link, '@responsable' => $mailResponsableProc];
          $messageOut = t($messageIn, $values);
        }
        $this->sendEmail($mailResponsableAct, "Proceso Activado - Responsable Actividad", $messageOut, 'correo_activar_proceso');
      }

      $this->setProcesoActivado($proceso);
    }
  }
}
