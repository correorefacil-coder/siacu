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
      $messageIn = '<div class="wrapper-mail">
        <p>
          Señor(a) <strong>@usuario :</strong>
        </p>
        <p>Reciba un cordial saludo</p>
        <p>
          El plan de trabajo <strong>@link</strong> se encuentra disponible para
          su revisión y actualización de las actividades que este proceso conlleva.
        </p>
        <p>
          Una vez finalizado el plan de trabajo por favor enviarlo a la Dirección
          de Calidad y Proyectos Académicos (DCPA) por medio de la opción
          "Enviar plan de trabajo a DCPA" del sistema para su aprobación final.
        </p>
        <p>
          Si tiene dudas comuníquese con el responsable de la oficina gestora:
          @responsable
        </p>
      </div>';


      // USUARIO
      $responsableEntity = $proceso->field_proceso_responsable_academ->referencedEntities();
      $usuario = $this->getNombreUsuario( $responsableEntity[0]);

      // Destinatarios
      $mailSend = '';
      // RESPONSABLE PROCESO ACADEMIA
      //var_dump($proceso->field_proceso_responsable->isEmpty());
      if (!$proceso->field_proceso_responsable->isEmpty()) {
        $responsableGestorEntity = $proceso->field_proceso_responsable->referencedEntities();
        $mailResponsableProc = $responsableGestorEntity[0]->getEmail();
        //dd($mailResponsableProc);
        if (filter_var($mailResponsableProc, FILTER_VALIDATE_EMAIL)) {
          $mailSend = $mailResponsableProc;
        } else {
          $message = t('Error al enviar correo de notificacion, el usuario @username no tiene correo electronico configurado.', array('@username' => $responsableGestorEntity->getAccountName()));
          \Drupal::messenger()->addError($message);
          \Drupal::logger('activar_proceso')->error($message);
        }
      }


      // RESPONSABLE OFICINA GESTORA
      $responsableAcademiaEntity = $proceso->field_proceso_responsable_academ->referencedEntities();
      $mailResponsableAct = $responsableAcademiaEntity[0]->getEmail();

      if (filter_var($mailResponsableAct, FILTER_VALIDATE_EMAIL)) {
        $mailSend = $mailSend . ',' . $mailResponsableAct;
      } else {
        $message = t('Error al enviar correo de notificacion, el usuario @username no tiene correo electronico configurado.', array('@username' => $responsableAcademiaEntity->getAccountName()));
        \Drupal::messenger()->addError($message);
        \Drupal::logger('activar_proceso')->error($message);
      }

      // LINK A PROCESO
      $link = $proceso->toLink(NULL, 'canonical', ['absolute' => true, 'https' => true]);
      $link = $link->toString();

      // ENVIO CORREO
      $values = array(
        '@usuario' => $usuario,
        '@link' => $link,
        '@responsable' => $responsableAcademiaEntity[0]->getEmail(),
      );
      $messageOut = t($messageIn, $values);
      $this->sendEmail($mailSend, "Proceso Activado", $messageOut, 'correo_activar_proceso');
      $this->setProcesoActivado($proceso);
    }
  }
}
