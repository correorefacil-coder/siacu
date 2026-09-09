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
use Drupal\Core\Link;


class FinalizarProcesoController extends ControllerBase implements ProcesosInterface {

  use ProcesosTrait;

  public function finalizarProceso(NodeInterface $proceso){

    $estado = $this->getEstadoProceso($proceso);

    if ($estado == Self::PROCESO) {

      $this->setProcesoFinalizado($proceso);

      \Drupal::messenger()->addStatus('El Proceso ha finalizado exitosamente.');

      // Notificacion de correo

      $messageIn = '
        <div class="wrapper-mail">
          <p>
            Señor <strong>@usuario:</strong>
          </p>
          <p>
            El proceso <strong>@link</strong>, ha finalizado ya que se han cumplido todas las tareas del mismo.
          </p>
        </div>';

      // USUARIO
      $responsableEntity = $proceso->field_proceso_responsable_academ->referencedEntities();
      $usuario = $this->getNombreUsuario( $responsableEntity[0]);

      // RESPONSABLE GESTOR
      $gestorEntity = $proceso->field_proceso_responsable_academ->referencedEntities();

      // LINK A PROCESO
      $link =  $proceso->toLink();

      // ENVIO CORREO
      $values = array(
        '@usuario' => $usuario,
        '@link' => $link->toString(),
        '@responsable' => $gestorEntity[0]->getEmail(),
      );
      $messageOut = t($messageIn, $values);
      $mailSend = $gestorEntity[0]->getEmail().','.$responsableEntity[0]->getEmail();
      $this->sendEmail($mailSend, "Proceso Finalizado", $messageOut,'correo_activar_proceso');

    }

  }

}

