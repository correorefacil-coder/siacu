<?php

namespace Drupal\plan_estudios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Plan estudios routes.
 */
class PlanEstudiosController extends ControllerBase {

  /**
   * Builds the response.
   */

  public function importando()
  {

    $batch = array(
      'title' => t('Exporting'),
      'operations' => array(
        array('disc_migrate', array('courses', array('foo' => 'bar'))),
      ),
      'finished' => 'disc_migrate_finished_callback',
      'file' => drupal_get_path('module', 'plan_estudios') . '/plan_estudios.importacionbatch.inc',
    );

    batch_set($batch);
    return batch_process('user');
  }
  /**
   * Generar reporte del plan de estudios
   * @param int $node
   */
  public function plan_estudios_generar_reporte($node)
  {
    $plan_estudio = Node::load($node);
    $ht = $hl = $cre = array();
    $lista_semestres_asig = $lista_asignaturas = array();

    $bloques_academicos = $plan_estudio->field_plan_estudios_asignaturas->getValue();

    foreach ($bloques_academicos as $k => $bloque) {
      if (!empty($bloque['target_id'])) {
        $bloque = Paragraph::load($bloque['target_id']);
        // Nombre Semestre
        $field_semestre = $bloque->field_semestre->getValue();
        $semestre_tax = Term::load($field_semestre[0]['target_id']);
        $titulo_semestre = $semestre_tax->getName();
        $lista_semestres_asig[] = $titulo_semestre;
        // ASIGNATURAS DEL SEMESTRE
        $asignaturas = $bloque->field_plan_estudios_asig_seme->getValue();

        $tot_ht = $tot_hl = $tot_cre = $tot_pl = $tot_l = $tot_o = array();

        foreach ($asignaturas as $key => $asignaturaId) {
          $asignatura = Node::load($asignaturaId['target_id']);
          $lista_asignaturas[$titulo_semestre] = array(
            'titulo' => $asignatura->field_titulo_asignatura->value,
            'materia' => $asignatura->field_materia->value . ' ' .
              $asignatura->field_curso->value,
            'ht' => $asignatura->field_horas_teo->value,
            'hl' => $asignatura->field_horas_lab->value,
            'hti' => ($asignatura->field_horas_trabajo_ind->value == null ?
              0 :
              $asignatura->field_horas_trabajo_ind->value
            ),
            'cre_acad' => $asignatura->field_credito_acad->value,
            'cre_fact' => $asignatura->field_credito_fact->value,
            'area' => $bloque->field_plan_estudios_area->value,
            'flex' => $bloque->field_plan_estudios_flexibilidad->value,
            'regla' => $this->reglaAsignatura($plan_estudio->field_regla, $asignatura->id())
          );
          // CALCULOS TOTALES SEMESTRE
          $tot_hti[] = ($asignatura->field_horas_trabajo_ind->value == null ?
            0 :
            $asignatura->field_horas_trabajo_ind->value
          );
          $tot_ht[] = $asignatura->field_horas_teo->value;
          $tot_hl[] = $asignatura->field_horas_lab->value;
          $tot_cre[] = $asignatura->field_credito_acad->value;
          switch ($bloque->field_plan_estudios_flexibilidad->value) {
            case 'L':
              $tot_l[] = $asignatura->field_credito_acad->value;
              break;
            case 'PL':
              $tot_pl[] = $asignatura->field_credito_acad->value;
              break;
            case 'O':
              $tot_o[] = $asignatura->field_credito_acad->value;
              break;
          }
        }
        $lista_asignaturas[$titulo_semestre]['hti'] = array_sum($tot_hti);
        $lista_asignaturas[$titulo_semestre]['hl'] = array_sum($tot_hl);
        $lista_asignaturas[$titulo_semestre]['cre'] = array_sum($tot_cre);
        $lista_asignaturas[$titulo_semestre]['l'] = array_sum($tot_l);
        $lista_asignaturas[$titulo_semestre]['pl'] = array_sum($tot_pl);
        $lista_asignaturas[$titulo_semestre]['o'] = array_sum($tot_o);

        $ht[] = array_sum($tot_ht);
        $hl[] = array_sum($tot_hl);
        $cre[] = array_sum($tot_cre);
        $l[] = array_sum($tot_l);
        $pl[] = array_sum($tot_pl);
        $o[] = array_sum($tot_o);
      }
    }
    $totales['ht'] = array_sum($ht);
    $totales['hl'] = array_sum($hl);
    $totales['cre'] = array_sum($cre);
    $totales['l'] = array_sum($l);
    $totales['pl'] = array_sum($pl);
    $totales['o'] = array_sum($o);

    $variables['title'] = $plan_estudio->getTitle();
    $variables['lista_asignaturas'] = $lista_asignaturas;
    $variables['totales'] = $totales;
    return [
      '#theme' => 'my_template',
      '#titulos' => $lista_semestres_asig,
      '#variables' => $variables,
    ];
  }

  private function reglaAsignatura($reglas, $asignatura)
  {
    $rules = array();

    $i = 0;
    foreach ($reglas as $regla) {
      $reglaID = $regla->getValue();
      $rule = Node::load($reglaID['target_id']);
      $field_prog_materia = $rule->field_prog_materia->getValue();
      if ($asignatura == $field_prog_materia[0]['target_id']) {
        $rules[$i]['tipo'] = $rule->field_reglas_electiva_enfasis->value;
        $rules[$i]['nombre'] = $rule->getTitle();
        $i++;
      }
      $field_reglas_asignaturas_relacio = $rule->field_reglas_asignaturas_relacio->getValue();
      foreach ($field_reglas_asignaturas_relacio as $value) {
        if ($asignatura == $value['nid']) {
          $rules[$i]['tipo'] = $rule->field_reglas_electiva_enfasis->value[0]['value'];
          $rules[$i]['nombre'] = $rule->title;
          $i++;
        }
      }
    }

    return $rules;
  }
}
