<?php

namespace Drupal\reporte_historico_programas\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\node\Entity\Node;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Returns responses for Reporte Historico Programas routes.
 */
class ReporteHistoricoProgramasController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function reporte($node = null) {

    $sheet1Data = [
      ["Nombre del programa", "SNIES", "División", "Departamento", "Nivel de Programa", "Fecha del acta de creación", "Fecha del primer registro calificado", "Fecha de inicio del programa - Año", "Activo", "Funcionamiento", "Tipo de proceso", "Nombre", "Resolución", "Fecha", "Url"],
    ];
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'programas_academicos');
    $general = true;
    if (!empty($node)) {
      $query->condition('nid', $node);
      $general = false;
    }
    $programasIDs = $query->execute();
    foreach ($programasIDs as $key => $programaID) {
      $node = Node::load($programaID);

      $baseline = array( '', '', '', '', '', '', '', '', '', '');

      $baseline[0] = $node->getTitle();
      if (!$node->field_registro_snies->isEmpty()) {
        $sniesValue = $node->get('field_registro_snies')->getValue()[0];
        $baseline[1] = $sniesValue['value'];
      }
      if (!$node->field_escuela_dep->isEmpty()) {
        $escuelaID = $node->get('field_escuela_dep')->getValue()[0];
        $escuelaTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($escuelaID['target_id']);
        $baseline[2] = $escuelaTerm->getName();
      }
      if (!$node->field_facultad->isEmpty()) {
        $facultadID = $node->get('field_facultad')->getValue()[0];
        $facultadTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($facultadID['target_id']);
        $baseline[3] = $facultadTerm->getName();
      }
      if (!$node->field_nivel_programa->isEmpty()) {
        $nivelID = $node->get('field_nivel_programa')->getValue()[0];
        $nivelTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($nivelID['target_id']);
        $baseline[4] = $nivelTerm->getName();
      }
      if (!$node->field_fecha_norma_reg_cal->isEmpty()) {
        $fecha_norma_reg_cal = $node->get('field_fecha_norma_reg_cal')->getValue()[0];
        $fecha_norma_reg_cal = \DateTime::createFromFormat('Y-m-d',$fecha_norma_reg_cal['value']);
        $baseline[5] = (is_object($fecha_norma_reg_cal) && $fecha_norma_reg_cal instanceof \DateTime) ? $fecha_norma_reg_cal->format("d/m/Y") : '';
      }
      if (!$node->field_fecha_primer_reg_calificad->isEmpty()) {
        $fecha_primer_reg_calificad = $node->get('field_fecha_primer_reg_calificad')->getValue()[0];
        $fecha_primer_reg_calificad = \DateTime::createFromFormat('Y-m-d', $fecha_primer_reg_calificad['value']);
        $baseline[6] = (is_object($fecha_primer_reg_calificad) && $fecha_primer_reg_calificad instanceof \DateTime) ? $fecha_primer_reg_calificad->format("d/m/Y") : '';
      }
      if (!$node->field_anio_inicio_programa->isEmpty()) {
        $anio_inicio_programa = $node->get('field_anio_inicio_programa')->getValue()[0];
        $baseline[7] = $anio_inicio_programa['value'];
      }

      if (!$node->field_activo->isEmpty()) {
        $activo = $node->get('field_activo')->getValue()[0];
        $baseline[8] = $activo['value'] ? 'Activo' : 'No Activo';
      }
      if($node->field_en_funcionamiento->isEmpty()) {
        $en_funcionamiento = $node->get('field_en_funcionamiento')->getValue()[0];
        $baseline[9] = $en_funcionamiento['value'] ? 'En funcionamiento' : 'No Funcionando';
      }
      $cambios = $this->get_historico_cambios($node->field_historico_cambios->getValue());
      $registros = $this->get_historico('registros', $node->field_historico_registros_cal->getValue());
      $acreditaciones = $this->get_historico('acreditaciones', $node->field_historico_acreditaciones->getValue());

      foreach ($cambios as $cambio) {
        $line = array_merge($baseline, $cambio);
        $sheet1Data[] = $line;
      }
      foreach ($registros as $registro) {
        $line = array_merge($baseline, $registro);
        $sheet1Data[] = $line;
      }
      foreach ($acreditaciones as $acreditacion) {
        $line = array_merge($baseline, $acreditacion);
        $sheet1Data[] = $line;
      }
    }

    $mySpreadsheet = new Spreadsheet();

    // delete the default active sheet
    $mySpreadsheet->removeSheetByIndex(0);

    // Create "Sheet 1" tab as the first worksheet.
    // https://phpspreadsheet.readthedocs.io/en/latest/topics/worksheets/adding-a-new-worksheet
    $worksheet1 = new Worksheet($mySpreadsheet, "Historico Programas");
    $mySpreadsheet->addSheet($worksheet1, 0);
    $worksheet1->fromArray($sheet1Data);

    // Change the widths of the columns to be appropriately large for the content in them.
    // https://stackoverflow.com/questions/62203260/php-spreadsheet-cant-find-the-function-to-auto-size-column-width
    $worksheets = [$worksheet1];

    foreach ($worksheets as $worksheet)
    {
      foreach ($worksheet->getColumnIterator() as $column)
      {
        $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
      }
    }

    // Save to file.

    $date = date('d-m-y-' . substr((string) microtime(), 1, 8));
    $date = str_replace(".", "", $date);
    if ($general) {
      $filename = "Reporte-Historico-Programas-" . $date . ".xlsx";
    }else{
      $filename = "Reporte-Historico-Programa-".$baseline[0].'-'.$date.".xlsx";
    }

    try {
      $writer = new Xlsx($mySpreadsheet);
      $writer->save($filename);
      $content = file_get_contents($filename);
    } catch (\Exception $e) {
      exit($e->getMessage());
    }

    header("Content-Disposition: attachment; filename=" . $filename);
    unlink($filename);
    exit($content);
  }

  private function get_historico_cambios($nodeIDs)
  {
    $lines = [];
	  foreach ($nodeIDs as $nodeID) {
      $node = Node::load($nodeID['target_id']);
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      $line = [
        'Histórico de Cambio',
        $node->getTitle(),
        '',
        '',
        $url,
      ];
      if (!$node->field_resolucion_ministerio->isEmpty()) {
        $resolucion_ministerio = $node->get('field_resolucion_ministerio')->getValue()[0];
        $line[2] = $resolucion_ministerio['value'];
      }
      if (!$node->field_fecha_resolucion_ministeri->isEmpty()) {
        $fecha_resolucion_ministeri = $node->get('field_fecha_resolucion_ministeri')->getValue()[0];
        $fecha_resolucion_ministeri = \DateTime::createFromFormat('Y-m-d',$fecha_resolucion_ministeri['value']);
        $line[3] = (is_object($fecha_resolucion_ministeri) && $fecha_resolucion_ministeri instanceof \DateTime) ? $fecha_resolucion_ministeri->format("d/m/Y") : '';
      }
      $lines[] = $line;
    }
    return $lines;
  }

  private function get_historico($tipo, $nodeIDs)
  {
    $lines = [];
    switch ($tipo) {
      case 'registros':
        $nombre = 'Histórico de Registros';
        break;
      case 'acreditaciones':
        $nombre = 'Histórico de Acreditación';
        break;
    }
    foreach ($nodeIDs as $nodeID) {
      $node = Node::load($nodeID['target_id']);
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      $line = [
        $nombre,
        $node->getTitle(),
        '',
        '',
        $url,
      ];
      if (!$node->field_resolucion->isEmpty()) {
        $resolucion = $node->get('field_resolucion')->getValue()[0];
        $line[2] = $resolucion['value'];
      }
      if (!$node->field_fecha->isEmpty()) {
        $fecha = $node->get('field_fecha')->getValue()[0];
        $fecha = \DateTime::createFromFormat('Y-m-d', $fecha['value']);
        $line[3] = (is_object($fecha) && $fecha instanceof \DateTime) ? $fecha->format("d/m/Y") : '';
      }
      $lines[] = $line;
    }
    return $lines;
  }

}
