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


class ActualizarProcesoController extends ControllerBase implements ProcesosInterface {

  use ProcesosTrait;
  /*
  field_proceso_plantilla
  34 // Acreditación de Programas de Postgrado a Nivel Internacional = "acreditacionPrograma"
  33 // Acreditación de Programas de Postgrado a Nivel Nacional = "acreditacionPrograma"
  32 // Acreditación de Programas de Pregrado a Nivel Internacional = "acreditacionPrograma"
  38 // Renovación de Acreditación de Programas de Postgrado a Nivel Internacional = "acreditacionPrograma"
  37 // Renovación de Acreditación de Programas de Postgrado a Nivel Nacional = "acreditacionPrograma"
  36 // Renovación de Acreditación de Programas de Pregrado a Nivel Internacional = "acreditacionPrograma"
  35 // Renovación de Acreditación de Programas de Pregrado a Nivel Nacional = "acreditacionPrograma"

  42 // Autoevaluación Permanente Programas de Postgrado = "autoevaluacionPrograma"
  41 // Autoevaluación permanente programas de pregrado = "autoevaluacionPrograma"

  39 // Modificaciones a Programas de Postgrado = "modificacionPrograma"
  27 // Modificaciones a Programas de Pregrado = "modificacionPrograma"

  31 // Renovación de Registro Calificado de Programas de Postgrado = "registroPrograma"
  25 // Renovación de Registro Calificado de Programas de Pregrado = "registroPrograma"

  29 // Creación de Asignaturas = "$this->cambioEstado"
  26 // Creación de Asignaturas de Programas de Formación Básica = "$this->cambioEstado"
  30 // Creación de Nuevos Programas de Postgrado = "$this->cambioEstado"
  28 // Creación de Nuevos Programas de Pregrado = "$this->cambioEstado"

  40 // Homologaciones: Para los procesos de doble programa, articulación PR-PG y doble titulación = "homologacion"
  */

  public function actualizarProceso(NodeInterface $node){
    $proceso = $node;

    if ($this->getEstadoProceso($proceso) == Self::FINALIZADO) {

      $plantillaEntity = $proceso->field_proceso_plantilla->referencedEntities();
      $plantillaEntity = reset($plantillaEntity);
      $plantillaID = $plantillaEntity->id();

      switch ($plantillaID) {
        case '32':
        case '33':
        case '34':
        case '35':
        case '36':
        case '37':
        case '38':
          $this->acreditacionPrograma($proceso);
          break;

        case '41':
        case '32':
          $this->autoevaluacionPrograma($proceso);
          break;

        case '27':
        case '39':
          $this->modificacionPrograma($proceso);
          break;

        case '25':
        case '31':
          $this->registroPrograma($proceso);
          break;

        case '26':
        case '28':
        case '29':
        case '30':
          $this->$this->cambioEstado($proceso);
          break;

        // TODO HOMOLOGACIONES
        // case '40':
        //   $this->homologacion($proceso);
        //   break;

      }
    }else{
      \Drupal::messenger()->addError(Markup::create('No se puede actualizar el proceso.'));
      \Drupal::messenger()->addError(Markup::create('Proceso no esta en estado finalizado'));
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $proceso->id()]);
    return new RedirectResponse($url->toString());

  }

  private function acreditacionPrograma($proceso){

    $programa = $proceso->field_procesos_pa->referencedEntities();
    $programa = reset($programa);
    $valido = TRUE;
    $message = '';

    // Acreditaciones
    $acreditacion = $proceso->field_procesos_acreditacion->referencedEntities();
    $acreditacion = reset($acreditacion);
    if ($acreditacion == FALSE) {
      $messages[] = 'Falta diligenciar formulario de Acreditacion';
      $valido = FALSE;
    }
    // Acreditaciones Vigentes
    $acreditacion_vig = $proceso->field_procesos_acreditacion_vige->referencedEntities();
    $acreditacion_vig = reset($acreditacion_vig);
    if ($acreditacion_vig == FALSE) {
      $messages[] = 'Falta diligenciar formulario de Acreditacion Vigente';
      $valido = FALSE;
    }

    // Faltan campos por diligenciar.
    if ($valido == FALSE) {
      foreach ($messages as $key => $message) {
        \Drupal::messenger()->addError(Markup::create('No se puede actualizar el proceso.'));
        \Drupal::messenger()->addError(Markup::create($message));
      }
      return;
    }

    // Actualiza Programa Acreditaciones
    $field_historico_acreditaciones = $programa->field_historico_acreditaciones;
    $value_acreditaciones = $field_historico_acreditaciones->getValue();
    if (!$this->in_array_r($acreditacion->id(), $value_acreditaciones)) {
      $value_acreditaciones[] = ["target_id" => $acreditacion->id()];
      $programa->field_historico_acreditaciones->setValue($value_acreditaciones);
    }

    // Actualiza Programa Acreditaciones Vigentes
    $field_acreditaciones_vig = $programa->field_acreditaciones_vig;
    $value_acreditaciones_vig = $field_acreditaciones_vig->getValue();
    if (!$this->in_array_r($acreditacion_vig->id(), $value_acreditaciones_vig)) {
      $value_acreditaciones_vig[] = [ "target_id" => $acreditacion_vig->id()];
      $programa->field_acreditaciones_vig->setValue($value_acreditaciones_vig);
    }

    $programa->save();
    $this->cambioEstado($proceso);
  }

  private function autoevaluacionPrograma($proceso){

    $programa = $proceso->field_procesos_pa->referencedEntities();
    $programa = reset($programa);

    // AUTOEVALUACION
    $valido = TRUE;
    $autoevaluacion = $proceso->field_procesos_autoevaluacion->referencedEntities();
    $autoevaluacion = reset($autoevaluacion);
    if ($autoevaluacion == FALSE) {
      $messages[] = 'Falta diligenciar formulario de Autoevaluacion';
      $valido = FALSE;
    }

    // Faltan campos por diligenciar.
    if ($valido == FALSE) {
      foreach ($messages as $key => $message) {
        \Drupal::messenger()->addError(Markup::create('No se puede actualizar el proceso.'));
        \Drupal::messenger()->addError(Markup::create($message));
      }
      return;
    }

    // Actualiza Programa Historial Autoevaluaciones
    $field_his_autoevaluaciones = $programa->field_historico_autoevaluaciones;
    $value_his_autoevaluaciones = $field_his_autoevaluaciones->referencedEntities();
    if (!$this->in_array_r($autoevaluacion->id(), $value_his_autoevaluaciones)) {
      $value_his_autoevaluaciones[] = [ "target_id" => $autoevaluacion->id()];
      $programa->field_historico_autoevaluaciones->setValue($value_his_autoevaluaciones);
    }

    $programa->save();
    $this->cambioEstado($proceso);
  }

  private function modificacionPrograma($proceso){

    $programa = $proceso->field_procesos_pa_existente->referencedEntities();
    $programa = reset($programa);

    $tipoModificaciones = $proceso->field_tipo_modificaciones->getValue();
    foreach ($tipoModificaciones as $key => $tipoModificacion) {

      switch ($tipoModificacion['value']) {

        case 'denominacion':
          // PROCESO
          $nombrePrograma = $proceso->field_new_nombre_programa->getValue();
          $nombrePrograma = reset($nombrePrograma);
          $tituloOtorgar = $proceso->field_new_titulo->getValue();
          $tituloOtorgar = reset($tituloOtorgar);
          // PROGRAMA
          $programa->set('title', $nombrePrograma['value']);
          $programa->field_titulo_otorga->setValue($tituloOtorgar['value']);
          break;

        case 'lugar':
          // PROCESO
          $municipio = $proceso->field_municipio_pa->referencedEntities();
          $municipio = reset($municipio);
          $metodologia = $proceso->field_metodologia->getValue();
          $metodologia = reset($metodologia);
          // PROGRAMA
          $programa->field_municipio_pa->setValue(["target_id" => $municipio->id()]);
          $programa->field_metodologia->setValue($metodologia['value']);
          break;

        case 'duracion':
          // PROCESO
          $duracion = $proceso->field_pa_duracion_tiempo->getValue();
          $duracion = reset($duracion);
          $periodicidad = $proceso->field_periodicidad->getValue();
          $periodicidad = reset($periodicidad);
          $field_pa_campo_amplio = $proceso->get('field_pa_campo_amplio')->referencedEntities();
          $field_pa_campo_amplio = reset($field_pa_campo_amplio);
          $field_pa_campo_especifico = $proceso->get('field_pa_campo_especifico')->referencedEntities();
          $field_pa_campo_especifico = reset($field_pa_campo_especifico);
          $field_pa_campo_detllado = $proceso->get('field_pa_campo_detllado')->referencedEntities();
          $field_pa_campo_detllado = reset($field_pa_campo_detllado);
          // PROGRAMA
          $programa->field_pa_duracion_tiempo->setValue($duracion['value']);
          $programa->field_periodicidad->setValue($periodicidad['value']);
          if (!empty($field_pa_campo_amplio)) {
            $programa->field_pa_campo_amplio->setValue(["target_id" => $field_pa_campo_amplio->id()]);
          }
          if (!empty($field_pa_campo_especifico)) {
            $programa->field_pa_campo_especifico->setValue(["target_id" => $field_pa_campo_especifico->id()]);
          }
          if (!empty($field_pa_campo_detllado)) {
            $programa->field_pa_campo_detllado->setValue(["target_id" => $field_pa_campo_detllado->id()]);
          }
          break;

        case 'cupos':
          // PROCESO
          $cupos = $proceso->field_cupo_maximo->getValue();
          $cupos = reset($cupos);
          // PROGRAMA
          $programa->field_cupo_maximo->setValue($cupos['value']);
          break;

        // TODO
        // case 'plan':
        //   break;

        case 'convenios':
          // PROCESO
          $convenios = $proceso->field_convenio_ref->referencedEntities();
          $convenios = reset($convenios);
          // PROGRAMA
          $programa->field_convenio_ref->setValue(["target_id" => $convenios->id()]);
          break;
      }
      # code...
    }

    $programa->save();
    $this->cambioEstado($proceso);
  }

  private function registroPrograma($proceso){

    $programa = $proceso->field_procesos_pa->referencedEntities();
    $programa = reset($programa);
    $valido = TRUE;
    $message = '';

    // Registro Calificado
    $registro = $proceso->field_procesos_registro->referencedEntities();
    $registro = reset($registro);
    if ($registro == FALSE) {
      $messages[] = 'Falta diligenciar formulario de Registro Calificado';
      $valido = FALSE;
    }

    // Faltan campos por diligenciar.
    if ($valido == FALSE) {
      foreach ($messages as $key => $message) {
        \Drupal::messenger()->addError(Markup::create('No se puede actualizar el proceso.'));
        \Drupal::messenger()->addError(Markup::create($message));
      }
      return;
    }

    // Actualiza Programa Registros Calificado
    $field_historico_registros_cal = $programa->field_historico_registros_cal;
    $value_registros = $field_historico_registros_cal->getValue();
    if (!$this->in_array_r($registro->id(), $value_registros)) {
      $value_registros[] = [ "target_id" => $registro->id()];
      $programa->field_historico_registros_cal->setValue($value_registros);
    }

    $programa->save();
    $this->cambioEstado($proceso);
  }

  private function cambioEstado($proceso){

    $this->setProcesoActualizado($proceso);

    \Drupal::messenger()->addStatus('El Proceso ha sido actualizado exitosamente.');
    $messageIn = '<div class="wrapper-mail">
      <p>
        Señor(a) <strong>@usuario :</strong>
      </p>
      <p>Reciba un cordial saludo</p>
      <p>
        El proceso <strong>@link</strong>, ha finalizado,
        ya que se han cumplido todas las tareas del mismo.
      </p>
    </div>';

    // RESPONSABLE GESTOR
    $gestorEntity = $proceso->field_proceso_responsable_academ->referencedEntities();
    $gestorEntity = reset($gestorEntity);
    $usuario = $this->getNombreUsuario($gestorEntity);

    // LINK A PROCESO
    $link =  $proceso->toLink();

    // ENVIO CORREO
    $values = array(
      '@usuario' => $usuario,
      '@link' => $link->toString(),
    );
    //var_dump($values);
    $messageOut = t($messageIn, $values);
    $mailSend = $gestorEntity->getEmail();
    $this->sendEmail($mailSend, "Proceso Actualizado", $messageOut, 'correo_activar_proceso');
  }

  // TODO HOMOLOGACIONES
  // private function homologacion($proceso){}

}

