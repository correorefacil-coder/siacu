<?php

namespace Drupal\asignaturas_sincro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Returns responses for Asignaturas Sincro routes.
 */
class AsignaturasSincroController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

  public function sincronizarAsignaturasBanner()
  {
    set_time_limit(0);
    print("Inicio SincronizarAsignaturasBanner\n");
    $listadoAsignaturas = $this->listadoAsignaturasBanner();
    $out = "Sincronizacion de asignaturas Banner\n";
    foreach ($listadoAsignaturas as $key => $asignatura) {
      print("Procesando: ".$asignatura['PERIODO_EFECTIVO'].'-'.$asignatura['MATERIA'].$asignatura['CURSO'].'-'.$asignatura['TITULO_ASIGNATURA']."\n");
      $line = $this->actualizarAsignaturaEnGAP($asignatura);
      $out = $line."\n";
    }
    $build['content'] = ['#markup' => $out];
    return $build;
  }

  public function confirmaEnviarAsignaturaBanner($node)
  {
    // VALIDA DATOS COMPLETOS
    $faltan = $this->validarDatosAsignatura($node);

    if (!empty($faltan)) {
      $titleWindow = 'Datos de Asignatura incompleta';
      $faltantes = '';
      foreach ($faltan as $key => $faltante) {
        $faltantes = '<div>- '.$faltante.'</div>'.$faltantes;
      }
      $htmlWindow = '<div>La información de la Asignatura <b>' .
        $node->getTitle().'</b> se encuentra incompleta o con errores para el envio a Banner. </div><br>' .
        '<div>Errores en los siguientes campos:</div><br>'.$faltantes;
    } else {
      $titleWindow = 'Confirmación de acción';
      $htmlWindow = "¿Desea enviar a Syllabus General en Banner la Asignatura: <br>" .
        "<b>".$node->getTitle()."</b>?<br><br>" .
        '<a href="/node/'.$node->id().'/enviarAsignaturaBanner" class="btn btn-primary">Confirmar</a>';
    }

    $response = new AjaxResponse();
    $dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogText['#markup'] = $htmlWindow;
    $response->addCommand(new OpenModalDialogCommand($titleWindow, $dialogText, ['width' => '600']));
    return $response;

  }

  private function validarDatosAsignatura($node)
  {

    $faltan = [];

    if ($node->field_materia->isEmpty()) {
      $faltan[] = 'FALTA CÓDIGO MATERIA';
    }

    if ($node->field_periodo_efectivo->isEmpty()) {
      $faltan[] = 'FALTA PERIODO EFECTIVO';
    }else{
      $field_periodo_efectivo = $node->field_periodo_efectivo[0]->getValue();
      if (!is_int(filter_var($field_periodo_efectivo['value'], FILTER_VALIDATE_INT))) {
        $faltan[] = 'FALTA PERIODO EFECTIVO NO ES UN NÚMERO';
      }
    }

    if ($node->field_asignaturas_par_per_efecti->isEmpty()) {
      $faltan[] = 'FALTA PERIODO EFECTIVO DEL SYLLABUS';
    } else {
      $field_asignaturas_par_per_efecti = $node->field_asignaturas_par_per_efecti[0]->getValue();
      if (!is_int(filter_var($field_asignaturas_par_per_efecti['value'], FILTER_VALIDATE_INT))) {
        $faltan[] = 'PERIODO EFECTIVO DEL SYLLABUS NO ES UN NÚMERO';
      }
    }

    if ($node->field_asignaturas_banner_per_efe->isEmpty()) {
      $faltan[] = 'FALTA PERIODO EFECTIVO DEL SYLLABUS DE BANNER';
    } else {
      $field_asignaturas_banner_per_efe = $node->field_asignaturas_banner_per_efe[0]->getValue();
      if (!is_int(filter_var($field_asignaturas_banner_per_efe['value'], FILTER_VALIDATE_INT))) {
        $faltan[] = 'PERIODO EFECTIVO DEL SYLLABUS DE BANNER NO ES UN NÚMERO';
      }
    }

    if ($field_asignaturas_banner_per_efe['value'] > $field_asignaturas_par_per_efecti['value']) {
      $faltan[] = 'EL PERIODO EFECTIVO DEL SYLLABUS NO ES MAYOR O IGUAL QUE EL PERIODO DE BANNER';
    }
    if ($field_periodo_efectivo['value'] > $field_asignaturas_par_per_efecti['value']) {
      $faltan[] = 'EL PERIODO EFECTIVO DEL SYLLABUS NO ES MAYOR O IGUAL QUE EL PERIODO DE LA ASIGNATURA';
    }

    if ($node->field_curso->isEmpty()) {
      $faltan[] = 'FALTA CÓDIGO CURSO';
    }

    if ($node->field_descripcion_asignatura->isEmpty()) {
      $faltan[] = 'FALTA DESCRIPCIÓN DE LA ASIGNATURA';
    }

    if ($node->field_justificacion_parcelacion->isEmpty()) {
      $faltan[] = 'FALTA JUSTIFICACIÓN DEL SYLLABUS';
    }

    if ($node->field_asignatura_par_objetivos->isEmpty()) {
      $faltan[] = 'FALTA OBJETIVO(S)  DEL CURSO';
    }

    return $faltan;
  }

  public function enviarAsignaturaBanner($node)
  {
    $txt_resultado_aprendizaje = '';
    $description = '';
    $tipo = null;
    try {
      $txt_resultado_aprendizaje_html = '<tr><td><strong>RESULTADO(S) DE APRENDIZAJE ESPERADO(S) A NIVEL PROGRAMA</strong></td><td><strong>RESULTADO(S) DE APRENDIZAJE DE LA ASIGNATURA</strong></td></tr>';

      $perfiles = $node->field_perfil_egresado_asignatura->getValue();
      foreach ($perfiles as $perfilID) {
        $field_resultado_aprendizaje = '';
        $field_resultado_perfil_egresado = '';
        $perfil = Paragraph::load($perfilID['target_id']);
        if (!empty($perfil->field_resultado_aprendizaje->getValue())) {
          $field_resultado_aprendizaje = $perfil->field_resultado_aprendizaje->getValue()[0]['value'];
          $txt_resultado_aprendizaje .= "RESULTADO(S) DE APRENDIZAJE ESPERADO(S) A NIVEL PROGRAMA:".chr(10).$perfil->field_resultado_aprendizaje->getValue()[0]['value'].chr(10);
        }
        if (!empty($perfil->field_resultado_perfil_egresado->getValue())) {
          $field_resultado_perfil_egresado = $perfil->field_resultado_perfil_egresado->getValue()[0]['value'];
          $txt_resultado_aprendizaje .= "RESULTADO(S) DE APRENDIZAJE DE LA ASIGNATURA:".chr(10).$perfil->field_resultado_perfil_egresado->getValue()[0]['value'].chr(10);
        }
        $txt_resultado_aprendizaje_html .= '<tr><td>'.$field_resultado_aprendizaje.'</td><td>'.$field_resultado_perfil_egresado.'</td></tr>';
      }

      $contenidos_asignatura = $node->field_contenidos_asignatura->referencedEntities();
      $txt_contenidos_asignatura = '';
      $txt_contenidos_asignatura_html =
        '<tr>
          <td>
            <strong>UNIDAD TEMÁTICA</strong>
          </td>
          <td>
            <strong>SUBTEMAS</strong>
          </td>
        </tr>';

      foreach ($contenidos_asignatura as $contenido_asignatura) {
        $unidad_tematica = '';
        if(!empty($contenido_asignatura->field_unidad_tematica)){
          $unidad_tematica = $contenido_asignatura->field_unidad_tematica->getValue()[0]['value'];
          $txt_contenidos_asignatura .= "UNIDAD TEMÁTICA:".chr(10).$unidad_tematica.chr(10);
        }
        $unidad_subtema = '';
        if (!empty($contenido_asignatura->field_unidad_subtema)) {
          $unidad_subtema = $contenido_asignatura->field_unidad_subtema->getValue()[0]['value'];
          $txt_contenidos_asignatura .= "SUBTEMAS:".chr(10).$unidad_subtema.chr(10);
        }
        $txt_contenidos_asignatura_html .= '<tr><td>'.nl2br($unidad_tematica).'</td><td>'.nl2br($unidad_subtema).'</td></tr>';
      }

      $txt_bibliografia_basica = '';
      $txt_bibliografia_basica_html = '<table><tr>';
      $txt_bibliografia_basica_html .= '<td><strong>NOMBRE DE LA BIBLIOGRAFÍA</strong></td>';
      $txt_bibliografia_basica_html .= '<td><strong>TIPO DE BIBLIOGRAFÍA</strong></td>';
      $txt_bibliografia_basica_html .= '<td><strong>REFERENCIA</strong></td>';
      $txt_bibliografia_basica_html .= '<td><strong>CLASE DE FUENTE BIBLIOGRÁFICA</strong></td>';
      $txt_bibliografia_basica_html .= '<td><strong>IDIOMA DE LA FUENTE</strong></td>';
      $txt_bibliografia_basica_html .= '<td><strong>EXISTE EN BIBLIOTECA</strong></td></tr>';

      $bibliografias = $node->field_bibliografia_asignatura->referencedEntities();
      foreach ($bibliografias as $bibliografia) {
        // IDIOMA
        if (!$bibliografia->field_idioma_bibliografia->isEmpty()) {
          $idioma_bibliografiaID = $bibliografia->field_idioma_bibliografia->getValue();
          $idioma_bibliografia_term = Term::load($idioma_bibliografiaID[0]['target_id']);
          $idioma_bibliografia = $idioma_bibliografia_term->getName();
        }
        // TIPO
        if (!$bibliografia->field_asignaturas_tipo_biblio->isEmpty()) {
          $tipo_bibliografiaID = $bibliografia->field_asignaturas_tipo_biblio->getValue();
          $tipo_bibliografia_term = Term::load($tipo_bibliografiaID[0]['target_id']);
          $tipo_bibliografia = $tipo_bibliografia_term->getName();
        }
        // FUENTE (CLASE)
        if (!$bibliografia->field_asignatura_clase_biblio->isEmpty()) {
          $fuente_bibliografiaID = $bibliografia->field_asignatura_clase_biblio->getValue();
          $fuente_bibliografia_term = Term::load($fuente_bibliografiaID[0]['target_id']);
          $fuente_bibliografia = $fuente_bibliografia_term->getName();
        }
        // VERSION TEXTO
        $txt_bibliografia_basica .= "NOMBRE DE LA BIBLIOGRAFÍA".chr(10).$bibliografia->field_nombre_bibliografia->getValue()[0]['value'].chr(10);
        $txt_bibliografia_basica .= "TIPO DE BIBLIOGRAFÍA".chr(10).$tipo_bibliografia.chr(10);
        $txt_bibliografia_basica .= "REFERENCIA".chr(10).MailFormatHelper::htmlToText($bibliografia->field_referencia_bibliografia->getValue()[0]['value']).chr(10);
        $txt_bibliografia_basica .= "CLASE DE FUENTE BIBLIOGRÁFICA".chr(10).$fuente_bibliografia.chr(10);
        $txt_bibliografia_basica .= "IDIOMA DE LA FUENTE".chr(10).$idioma_bibliografia.chr(10); //referencia a nodo
        $txt_bibliografia_basica .= "EXISTE EN BIBLIOTECA".chr(10).$bibliografia->field_existe_biblioteca->getValue()[0]['value'].chr(10);
        // VERSION HTML
        $txt_bibliografia_basica_html .= '<tr>';
        $txt_bibliografia_basica_html .= '<td>'.$bibliografia->field_nombre_bibliografia->getValue()[0]['value'].'</td>';
        $txt_bibliografia_basica_html .= '<td>'.$tipo_bibliografia.'</td>';
        $txt_bibliografia_basica_html .= '<td>'.$bibliografia->field_referencia_bibliografia->getValue()[0]['value'].'</td>';
        $txt_bibliografia_basica_html .= '<td>'.$fuente_bibliografia.'</td>';
        $txt_bibliografia_basica_html .= '<td>'.$idioma_bibliografia.'</td>';
        $txt_bibliografia_basica_html .= '<td>'.$bibliografia->field_existe_biblioteca->getValue()[0]['value'].'</td>';
        $txt_bibliografia_basica_html .= '</tr>';
      }

      $txt_bibliografia_basica_html .= '</table>';

      $parcelacion = "PERIODO EFECTIVO DE LA PARCELACIÓN:".chr(10).$node->field_asignaturas_par_per_efecti->getValue()[0]['value'].chr(10).chr(10) .
        "DESCRIPCIÓN DE LA ASIGNATURA:".chr(10).$node->field_descripcion_asignatura->getValue()[0]['value'].chr(10).chr(10) .
        "JUSTIFICACIÓN DE LA ASIGNATURA".chr(10).$node->field_justificacion_parcelacion->getValue()[0]['value'].chr(10).chr(10) .
        "RESULTADOS DE APRENDIZAJE".chr(10).$txt_resultado_aprendizaje.chr(10) .
        "CONTENIDOS DE LA ASIGNATURA".chr(10).$txt_contenidos_asignatura.chr(10) .
        "BIBLIOGRAFÍA BÁSICA DE LA ASIGNATURA".chr(10).$txt_bibliografia_basica.chr(10);

      $parcelacion_html =
      '<table>
        <tr>
          <td style="background: #ddd;" colspan="2" nowrap>
            <strong>PARCELACIÓN</strong>
          </td>
        </tr>
        <tr>
          <td style="background: #eee;" nowrap>
            <strong>PERIODO EFECTIVO DE LA PARCELACIÓN</strong>
          </td>
          <td>'.
            $node->field_asignaturas_par_per_efecti->getValue()[0]['value'].
          '</td>
        </tr>
        <tr>
          <td style="background: #eee;" nowrap>
            <strong>DESCRIPCIÓN DE LA ASIGNATURA</strong>
          </td>
          <td>'.
            $node->field_descripcion_asignatura->getValue()[0]['value'].
          '</td>
        </tr>
        <tr>
          <td style="background: #eee;" nowrap>
          <strong>JUSTIFICACIÓN DE LA ASIGNATURA</strong>
          </td>
          <td>'
            .$node->field_justificacion_parcelacion->getValue()[0]['value'].
          '</td>
        </tr>
        <tr>
          <td style="background: #eee;" colspan="2" nowrap>
            <strong>RESULTADOS DE APRENDIZAJE</strong>
          </td>
        </tr>'.
        $txt_resultado_aprendizaje_html.
        '<tr>
          <td style="background: #eee;" colspan="2" nowrap>
            <strong>CONTENIDOS DE LA ASIGNATURA</strong>
          </td>
        </tr>'.
        $txt_contenidos_asignatura_html.
        '<tr>
          <td style="background: #eee;" colspan="2" nowrap>
            <strong>BIBLIOGRAFÍA BÁSICA DE LA ASIGNATURA  </strong>
          </td>
        </tr>
        <tr>
          <td colspan="2">'.
            $txt_bibliografia_basica_html.
          '</td>
        </tr>
      </table>';

      $scrsylo_activity_date = date("d-M-Y");
      $conn = $this->conexion();

      $query = "SELECT
                  SCRSYLO_SUBJ_CODE,
                  SCRSYLO_CRSE_NUMB,
                  SCRSYLO_TERM_CODE_EFF
                FROM
                  SCRSYLO
                WHERE
                  SCRSYLO_SUBJ_CODE = '".$node->field_materia->getValue()[0]['value']."' AND
                  SCRSYLO_CRSE_NUMB = '".$node->field_curso->getValue()[0]['value']."' AND
                  SCRSYLO_TERM_CODE_EFF = '".$node->field_asignaturas_par_per_efecti->getValue()[0]['value']."'";
      $stid = oci_parse($conn, $query);
      if (!$stid) {
        $e = oci_error($conn);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
      }
      $current_user = \Drupal::currentUser();
      if (oci_execute($stid)) {

        $count = 0;
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
          $count++;
          $query2 = "UPDATE
                        SCRSYLO
                      SET
                        SCRSYLO_ACTIVITY_DATE = '$scrsylo_activity_date',
                        SCRSYLO_USER_ID = 'GAP: ".$current_user->getEmail()."',
                        SCRSYLO_LEARNING_OBJECTIVES = EMPTY_CLOB()
                      WHERE
                        SCRSYLO_SUBJ_CODE = '".$node->field_materia->getValue()[0]['value']."' AND
                        SCRSYLO_CRSE_NUMB = '".$node->field_curso->getValue()[0]['value']."' AND
                        SCRSYLO_TERM_CODE_EFF = '".$node->field_asignaturas_par_per_efecti->getValue()[0]['value']."'
                      RETURNING
                        SCRSYLO_LEARNING_OBJECTIVES INTO :lob";

          $clob_var = oci_new_descriptor($conn, OCI_D_LOB);
          $stmt_clob = oci_parse($conn, $query2);
          oci_bind_by_name($stmt_clob, ':lob', $clob_var, -1, OCI_B_CLOB);
          oci_execute($stmt_clob, OCI_DEFAULT);
          if ($clob_var->save($parcelacion)) {
            oci_commit($conn);
            $description = 'Se ha guardado correctamente en BANNER actualizando un registro existente y almacenando una copia en GAP con la siguiente información: <br>'.$parcelacion_html;
            \Drupal::messenger()->addStatus('La asignatura ha sido actualizada exitosamente en Banner.');
            $tipo = "Actualización";
          } else {
            $description = 'Ocurrió un error técnico al momento de conectarse con una base de datos externa a este sistema, no se ha podido actualizar la información en BANNER. Por favor intente más tarde. Si el error persiste contacte al administrador técnico del sistema reportando este caso al Centro de Soluciones Uninorte (CSU Ext:505)';
          }
          $clob_var->free();
          oci_free_statement($stmt_clob);
        }

        if ($count == 0) {

          $query2 = "INSERT INTO SCRSYLO (
                        SCRSYLO_SUBJ_CODE,
                        SCRSYLO_CRSE_NUMB,
                        SCRSYLO_TERM_CODE_EFF,
                        SCRSYLO_ACTIVITY_DATE,
                        SCRSYLO_USER_ID,
                        SCRSYLO_LEARNING_OBJECTIVES
                      ) VALUES (
                        '".$node->field_materia->getValue()[0]['value']."',
                        '".$node->field_curso->getValue()[0]['value']."',
                        '".$node->field_asignaturas_par_per_efecti->getValue()[0]['value']."',
                        '".$scrsylo_activity_date."',
                        'GAP:".$current_user->getDisplayName()."',
                        EMPTY_CLOB()
                      )
                      RETURNING
                        SCRSYLO_LEARNING_OBJECTIVES INTO :lob";

          $stmt_clob = oci_parse($conn, $query2);

          $clob = oci_new_descriptor($conn, OCI_D_LOB);

          oci_bind_by_name($stmt_clob, ':lob', $clob, -1, OCI_B_CLOB);

          oci_execute($stmt_clob, OCI_DEFAULT);

          if ($clob->save($parcelacion)) {
            oci_commit($conn);
            $description = 'Se ha guardado correctamente en BANNER creando un nuevo registro y almacenando una copia en GAP con la siguiente información: <br>'.$parcelacion_html;
            \Drupal::messenger()->addStatus('La asignatura ha sido actualizada exitosamente en Banner.');
            $tipo = "Nuevo Registro";
          } else {
            $description = 'Ocurrió un error técnico al momento de conectarse con una base de datos externa a este sistema, no se ha podido actualizar la información en BANNER. Por favor intente más tarde. Si el error persiste contacte al administrador técnico del sistema reportando este caso al Centro de Soluciones Uninorte (CSU Ext:505)';

          }
          $clob->free();
          oci_free_statement($stmt_clob);
        }
      }
      oci_free_statement($stid);

      if (!empty($tipo)) {
        $title = $node->field_materia->getValue()[0]['value'].
          $node->field_curso->getValue()[0]['value'].'-' .
          $node->field_asignaturas_par_per_efecti->getValue()[0]['value'].'-' .
          $current_user->getDisplayName();
        $node_pg = Node::create([
          'type' => 'parcelacion_general',
          'title' => $title
        ]);
        $node_pg->type = 'parcelacion_general';
        $node_pg->uid = $current_user->id();
        $node_pg->field_pargen_materia->setValue($node->field_materia->getValue()[0]['value']);
        $node_pg->field_pargen_curso->setValue($node->field_curso->getValue()[0]['value']);
        $node_pg->field_pargen_periodo->setValue($node->field_asignaturas_par_per_efecti->getValue()[0]['value']);
        $node_pg->field_pargen_texto->setValue(['value' => $parcelacion_html, 'format' => 'full_html']);
        $node_pg->field_pargen_tipo->setValue($tipo);
        $node_pg->field_pargen_asignatura->setValue(['target_id' => $node->id()]);
        $node->field_asignaturas_banner_per_efe->setValue($node->field_asignaturas_par_per_efecti->getValue()[0]['value']);
        $node_pg->save();
        $node->save();
      }

      oci_close($conn);

    } catch (PDOException $e) {
      dpm($e->getMessage());
    }

    $markup = "<div>$description</div>".
              "<div><a href='". $node->toUrl()->toString()."' >Volver</a></div>";
    $build['content'] = [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

  private function actualizarAsignaturaEnGAP($asignatura){
    // VERIFICAR SI EXISTE LA ASIGNATURA
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'szvcata');
    $query->condition('field_materia', $asignatura['MATERIA']);
    $query->condition('field_curso', $asignatura['CURSO']);
    $query->condition('field_periodo_efectivo', $asignatura['PERIODO_EFECTIVO']);
    $query->condition('field_fuente_origen', 'banner');
    $res = $query->execute();

    $title = $asignatura['PERIODO_EFECTIVO'].'-'.$asignatura['MATERIA'].$asignatura['CURSO'].'-'.$asignatura['TITULO_ASIGNATURA'];

    if (empty($res)) {
      // CREACION DE NODO
      $node = Node::create([
        'type' => 'szvcata',
        'title' => $title,
        'field_fuente_origen' => 'banner'
      ]);
    } else {
      // CARGA DE NODO EXISTENTE
      $nid = reset($res);
      $node = Node::load($nid);
      //dd(get_class_methods($node));
      // REVISION DE NODO
      $node->setNewRevision(TRUE);
      $node->isDefaultRevision(TRUE);
      $node->setRevisionCreationTime(REQUEST_TIME);
      $node->revision_log = 'Revision creada por sincronizacion CRON con Banner';
    }

    // NODO;
    $node->field_materia->setValue($asignatura['MATERIA']);
    $node->field_curso->setValue($asignatura['CURSO']);
    $node->field_periodo_efectivo->setValue($asignatura['PERIODO_EFECTIVO']);
    $node->field_periodo_final->setValue($asignatura['PERIODO_FINAL']);

    if (isset($asignatura['CODIGO_DIVISION']) && isset($asignatura['DIVISION'])) {
      $node->field_division->setValue($asignatura['CODIGO_DIVISION'].' - '.$asignatura['DIVISION']);
    }
    if (isset($asignatura['CODIGO_DEPARTAMENTO']) && isset($asignatura['DEPARTAMENTO'])) {
      $node->field_departamento->setValue($asignatura['CODIGO_DEPARTAMENTO'].' - '.$asignatura['DEPARTAMENTO']);
    }
    $node->field_titulo_asignatura->setValue($asignatura['TITULO_ASIGNATURA']);
    if (isset($asignatura['TITULO_LARGO'])) {
      $node->field_titulo_largo->setValue($asignatura['TITULO_LARGO']);
    }
    if (isset($asignatura['TITULO_INGLES'])) {
      $node->field_titulo_ingles->setValue($asignatura['TITULO_INGLES']);
    }
    $node->field_credito_acad->setValue($asignatura['CREDITO_ACAD']);
    $node->field_credito_fact->setValue($asignatura['CREDITO_FACT']);
    $node->field_horas_teo->setValue($asignatura['HORAS_TEO']);
    $node->field_horas_lab->setValue($asignatura['HORAS_LAB']);
    $node->field_horas_otr->setValue($asignatura['HORAS_OTR']);
    $node->field_horas_contacto->setValue($asignatura['HORAS_CONTACTO']);
    $node->field_modo_calif->setValue($asignatura['MODO_CALIF']);
    $node->field_desc_modo_calif->setValue($asignatura['DESC_MODO_CALIF']);
    $node->field_nivel->setValue($asignatura['NIVEL']);
    $node->field_tipo_horario->setValue($asignatura['TIPO_HORARIO']);

    // DATOS SYLLABUS
    $periodo = $this->getPeriodoEfectivoAsignaturaBanner($asignatura);
    if (!empty($periodo)) {
      $node->field_asignaturas_banner_per_efe->setValue($periodo['PERIODO_EFECTIVO']);
      if (!empty($periodo['PARTE1'])) {
        $node->field_parte1->setValue(['value' => nl2br($periodo['PARTE1']->load()), 'format' => 'full_html']);
      }
    }

    $save = $node->save();
    switch ($save) {
      case 1:
        $message = "- Creado: ".$title." (".$node->id().")";
        break;
      case 2:
        $message = "- Actualizado: ".$title." (".$node->id().")";
        break;
    }

    print ($message."\n");
    \Drupal::logger('asignaturas_sincro')->info($message);
    return $message;
  }

  private function listadoAsignaturasBanner()
  {
    $conn = $this->conexion();
    $query = "SELECT
                *
              FROM
                SZVcata
              WHERE
                  materia||curso||periodo_efectivo NOT IN (
                    'ART4150200430','DIG0020200710','DIG4020200710',
                    'ADM4550200400','ELP7045200410','ELP8230200510',
                    'ADM9467200410','ADM9467200430','CBMCBM200600',
                    'CSO0080201010','DIG0020201030','DIG4020201030',
                    'CGH 0095201000','CGH 0097201000','EDU70013201010',
                    'PSI3040200800','ADM0465200900','OCA1212201210',
                    'IIN1368201200','CMN70031201110','IDS0045200110',
                    'ART4150201310','IGL5075201500','AMB43001201230',
                    'SOP43002201230','SOP43003201230','LEY15166201230',
                    'IGL5070201500','CMN64024201230','CSO0080201530',
                    'CGH 0202201400','CGH 0203201400','IST0008201600',
                    'IST0011201500','IST0009201500','ADM0550201700',
                    'DIG4102201830','HIS0560201700','MDO0351201700',
                    'HUM1310201800','FIN0016201600','FIN0018201600',
                    'FIN0017201600'
                  ) AND
                  LENGTH(TRIM(TRANSLATE(curso, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', ' '))) IS  NULL";
    $stid = oci_parse($conn, $query);
    oci_execute($stid);

    $out = [];
    while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
      $out[] = $row;
    }
    return $out;
  }

  private function getPeriodoEfectivoAsignaturaBanner($asignatura)
  {
    $conn = $this->conexion();
    $query = "SELECT
                PARTE1,
                PERIODO_EFECTIVO
              FROM
                SZVPARC
              WHERE
                MATERIA = :MATERIA
                AND CURSO = :CURSO
                AND PERIODO_EFECTIVO < :PERIODO_FINAL
                AND PERIODO_FINAL >= :PERIODO_FINAL
              ORDER BY
                PERIODO_EFECTIVO DESC";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':MATERIA', $asignatura['MATERIA']);
    oci_bind_by_name($stid, ':CURSO', $asignatura['CURSO']);
    oci_bind_by_name($stid, ':PERIODO_FINAL', $asignatura['PERIODO_FINAL']);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
      return $row;
    }
    return [];
  }

  private static function conexion()
  {
    try {
      if (!function_exists("oci_connect")) {
        throw new \Exception('Function oci_connect');
      }
      // DATOS DE CONEXION
      $db_oracle = Settings::get('banner', '1111');
      // ESTABLECE CONEXION
      $conn = oci_connect($db_oracle['db_username'], $db_oracle['db_password'], "{$db_oracle['server']}:{$db_oracle['port']}/{$db_oracle['service_name']}", 'AL32UTF8');
      if (!$conn) {
        $e = oci_error();
        throw new \Exception($e['message']);
      }
      return $conn;
    } catch (\Exception $e) {
      throw $e;
    }
  }
}
