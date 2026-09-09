<?php

namespace Drupal\visualizador_programas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateTimezone;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Datetime;

/**
 * Returns responses for Visualizador Programas routes.
 */
class VisualizadorProgramasController extends ControllerBase
{

  /**
   * Builds the response.
   */
  public function build()
  {
    $html = "<h5 class='visual_h5'>Indicadores de Programas</h5>
              <div id='visualizador_chart'><div id='visualizador_programas'></div></div>
            <h5 class='visual_h5'>Listado de Procesos Activos</h5>
              <div id='visualizador_table'><table id='tabla_upb'></table></div>";
    $build['content'] = [

      '#type' => 'container',
      'content' => [
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $html,
          '#attributes' => [
            'class' => 'row',
            'id' => 'my-id2',
          ]
        ],
      ],
      //'#markup' => $html,
      '#attributes' => [
        'class' => 'container',
        'id' => 'my-id',
      ]
    ];
    $build['#attached']['library'][] = 'visualizador_programas/visualizador_programas';
    return $build;
  }

  public function getData()
  {

    $response['base']['labels'][] = 'Activos (' . $this->get_count_programas_activos() . ')';
    $response['base']['data'][] = $this->get_count_programas_activos();
    $response['base']['url'][] = '/programas-academicos-activos';

    // $response['base']['labels'][] = 'Acreditables (' . $this->get_count_programas_acreditables() . ')';
    // $response['base']['data'][] = $this->get_count_programas_acreditables();

    $response['base']['labels'][] = 'Acreditados (' . $this->get_count_programas_acreditados() . ')';
    $response['base']['data'][] = $this->get_count_programas_acreditados();
    $response['base']['url'][] = '/programas-academicos-acreditados';

    $response['rc']['labels'][] = 'RC 12 - 16 Meses (' . $this->get_count_programas_rc_12_16_meses() . ')';
    $response['rc']['data'][] = $this->get_count_programas_rc_12_16_meses();
    $response['rc']['url'][] = '/programas-academicos-registro-calificado-12-16';

    $response['rc']['labels'][] = 'RC 10 - 12 Meses (' . $this->get_count_programas_rc_10_12_meses() . ')';
    $response['rc']['data'][] = $this->get_count_programas_rc_10_12_meses();
    $response['rc']['url'][] = '/programas-academicos-registro-calificado-10-12';

    $response['rc']['labels'][] = 'RC Menos de 10 Meses (' . $this->get_count_programas_rc_10_meses() . ')';
    $response['rc']['data'][] = $this->get_count_programas_rc_10_meses();
    $response['rc']['url'][] = '/programas-academicos-registro-calificado-menor-10';

    $response['rc']['labels'][] = 'RC Vencidos (' . $this->get_count_programas_rc_vencidos() . ')';
    $response['rc']['data'][] = $this->get_count_programas_rc_vencidos();
    $response['rc']['url'][] = '/programas-academicos-registro-calificado-vencidos';

    $response['ac']['labels'][] = 'AC 12 - 16 Meses (' . $this->get_count_programas_ac_12_16_meses() . ')';
    $response['ac']['data'][] = $this->get_count_programas_ac_12_16_meses();
    $response['ac']['url'][] = '/programas-academicos-acreditados-12-16';

    $response['ac']['labels'][] = 'AC 10 - 12 Meses (' . $this->get_count_programas_ac_10_12_meses() . ')';
    $response['ac']['data'][] = $this->get_count_programas_ac_10_12_meses();
    $response['ac']['url'][] = '/programas-academicos-acreditados-10-12';

    $response['ac']['labels'][] = 'AC Menos de 10 Meses (' . $this->get_count_programas_ac_10_meses() . ')';
    $response['ac']['data'][] = $this->get_count_programas_ac_10_meses();
    $response['ac']['url'][] = '/programas-academicos-acreditados-menor-10';

    $response['ac']['labels'][] = 'AC Vencidos (' . $this->get_count_programas_ac_vencidos() . ')';
    $response['ac']['data'][] = $this->get_count_programas_ac_vencidos();
    $response['ac']['url'][] = '/programas-academicos-acreditados-vencidos';

    $response['men']['labels'][] = 'Espera MEN (' . $this->get_count_programas_espera_MEN() . ')';
    $response['men']['data'][] = $this->get_count_programas_espera_MEN();
    $response['men']['url'][] = '/programas-academicos-men';

    $response['procesos']['labels'][] = 'Procesos';
    $response['procesos']['data'] = $this->get_procesos_en_proceso();

    return new JsonResponse($response);
  }

  // PROGRAMAS
  private function get_count_programas_activos()
  {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_activo', '1');
    return $query->count()->execute();
  }

  private function get_count_programas_acreditables()
  {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    //$query->condition('field_programa_acreditable', '1');
    return $query->count()->execute();
  }
  private function get_count_programas_acreditados()
  {
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $formatted = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $today = date("d/m/Y");
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_acreditaciones_vig.entity:node.field_fecha_vencimiento_acred', $formatted, '>');

    return $query->count()->execute();
  }

  private function get_count_programas_rc_12_16_meses()
  {

    $date = new DrupalDateTime(" +12 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +16 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_fecha_vencimiento_reg_cal', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_rc_10_12_meses()
  {
    $date = new DrupalDateTime(" +10 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +12 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_fecha_vencimiento_reg_cal', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_rc_10_meses()
  {
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +10 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_fecha_vencimiento_reg_cal', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_rc_vencidos()
  {
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $today = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_activo', '1'); // <-- ESTA ES LA LÍNEA NUEVA
    $query->condition('field_fecha_vencimiento_reg_cal', $today, '<');
    return $query->count()->execute();
  }

  private function get_count_programas_ac_12_16_meses()
  {
    $date = new DrupalDateTime(" +12 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +16 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_acreditaciones_vig.entity:node.field_fecha_vencimiento_acred', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_ac_10_12_meses()
  {
    $date = new DrupalDateTime(" +10 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +12 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_acreditaciones_vig.entity:node.field_fecha_vencimiento_acred', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_ac_10_meses()
  {
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $date = new DrupalDateTime(" +10 months");
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endDate = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_acreditaciones_vig.entity:node.field_fecha_vencimiento_acred', array($startDate, $endDate), 'BETWEEN');
    return $query->count()->execute();
  }

  private function get_count_programas_ac_vencidos()
  {
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $today = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_acreditaciones_vig.entity:node.field_fecha_vencimiento_acred', $today, '<');
    return $query->count()->execute();
  }

  private function get_count_programas_espera_MEN()
  {
    $term_id = 7611; // 'A la espera de informe de pares';
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'programas_academicos');
    $query->condition('status', 1);
    $query->condition('field_historico_acreditaciones.entity:node.field_estado_acreditacion', $term_id);
    return $query->count()->execute();
  }
  private function get_procesos_en_proceso()
  {
    //field_proceso_actividades
    $data = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'procesos');
    $query->condition('status', 1);
    $query->condition('field_proceso_estado', 'En Proceso');
    $nodeIds = $query->execute();
    foreach ($nodeIds as $key => $nodeId) {
      $nodeProceso = Node::load($nodeId);
      $actividades = $nodeProceso->field_proceso_actividades->getValue();
      $totalActividades = count($actividades);
      $actividadesFinalizadas = 0;
      foreach ($actividades as $key => $actividad) {
        $nodeActividad = Node::load($actividad["target_id"]);
        if ($nodeActividad && $nodeActividad->hasField('field_act_procesos_estado')) {
          $estado = $nodeActividad->field_act_procesos_estado->getValue();
          //var_dump($estado);
          if (!empty($estado) && $estado[0]['value'] == 'finalizada') {
            $actividadesFinalizadas++;
          }
        }
      }
      $datanodeId = [];
      $datanodeId['nombre'] = $nodeProceso->toLink($nodeProceso->getTitle())->toString();
      $datanodeId['totalActividades'] = $totalActividades;
      $datanodeId['actividadesFinalizadas'] = $actividadesFinalizadas;
      //$datanodeId['link'] = $nodeProceso->toUrl('canonical', ['absolute' => TRUE])->toString();
      $data[] = $datanodeId;
    }
    return $data;
  }

}
