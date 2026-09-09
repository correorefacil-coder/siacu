<?php
namespace Drupal\activar_proceso;

use Drupal\node\NodeInterface;

trait ProcesosTrait {

  private function getNombreUsuario($user){
    $usuario = $user->getDisplayName();
    $fields = $user->getFields();
    $firstName = $fields['field_primer_nombre']->getValue();
    $lastName = $fields['field_primer_apellido']->getValue();
    if (!empty($firstName) && !empty($lastName)) {
      $firstName = reset($firstName);
      $lastName = reset($lastName);
      $usuario = $firstName['value'].' '.$lastName['value'];
    }
    return $usuario;
  }

  // Set Proceso en estado en "En Proceso"
  public function setProcesoActivado(NodeInterface $proceso){
    $this->setEstadoProceso($proceso, Self::PROCESO);
  }

  // Set Proceso en estado en "Finalizado"
  public function setProcesoFinalizado(NodeInterface $proceso){
    $this->setEstadoProceso($proceso, Self::FINALIZADO);
  }

  // Set Proceso en estado en "Finalizado - Actualizado"
  public function setProcesoActualizado(NodeInterface $proceso){
    $this->setEstadoProceso($proceso, Self::ACTUALIZADO);
  }

  // Set Proceso en estado en "Suspendido"
  public function setProcesoSuspendido(NodeInterface $proceso){
    $this->setEstadoProceso($proceso, Self::SUSPENDIDO);
  }

  // Set Proceso en estado en "Cancelado"
  public function setProcesoCancelado(NodeInterface $proceso){
    $this->setEstadoProceso($proceso, Self::CANCELADO);
  }

  public function getEstadoProceso(NodeInterface $proceso){
    $fields = $proceso->getFields();
    $field_estado = $proceso->field_proceso_estado->getValue();
    return $estado = $field_estado[0]['value'];
  }

  private function setEstadoProceso(NodeInterface $proceso, String $estado){
    $fields = $proceso->getFields();
    $field_estado = $proceso->field_proceso_estado->setValue($estado);
    // Estado Finalizado, Archiva el contenido
    /*
    if ($estado == Self::FINALIZADO) {
      $proceso->set('moderation_state', "archived");
      $proceso->save();
    }*/
    return $proceso->save();
  }

  private function sendEmail($to, $label, $body, $template){

    $mailManager = \Drupal::service('plugin.manager.mail');
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $params['message'] = $body;
    $params['subject'] = $label;

    $result = $mailManager->mail('activar_proceso', $template, $to, 'en', $params, NULL, TRUE);
    if ($result['result'] !== true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
      \Drupal::messenger()->addError($message);
      \Drupal::logger('custom_mail')->error($message);
      return;
    }
  }

  private function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
      if (
        ($strict ? $item === $needle : $item == $needle) ||
        (is_array($item) && $this->in_array_r($needle, $item, $strict))
      ) {
        return true;
      }
    }
    return false;
  }

}
