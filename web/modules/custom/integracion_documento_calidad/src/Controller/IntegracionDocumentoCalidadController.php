<?php

namespace Drupal\integracion_documento_calidad\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use \Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;


class IntegracionDocumentoCalidadController extends ControllerBase
{

  public function recepcionUrlCalidad(Request $request)
  {
    \Drupal::logger('integracion_documento_calidad')->alert('recepcionUrlCalidad');
    $settings = Settings::get('OnlineDrive', '1111');
    $headers = $request->headers->all();
    // CHECK APIKEY ACCESS
    if (isset($headers['apikey']) && $headers['apikey'][0] === $settings['apikey']) {
      $postData = json_decode($request->getContent());
      \Drupal::logger('integracion_documento_calidad')->alert('recepcionUrlCalidad postData: ' . json_encode($postData));
      $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', ['plantillas', 'documentos_calidad'], 'IN');
      $or = $query->orConditionGroup();
      $or->condition('field_doc_calidad_drive_id', $postData->uuid);
      $or->condition('field_plantilla_drive_id', $postData->uuid);
      $query->condition($or);
      $nodeId = $query->execute();
      $nodeId = reset($nodeId);
      $node = Node::load($nodeId);
      $node->field_error_sincronizacion->setValue('');
      $type = $node->bundle();

      if (!empty($nodeId)) {
        switch ($type) {
          case 'plantillas':
            switch ($postData->status) {
              case '200':
                $node->field_plantilla_enlace->setValue($postData->url);
                $node->save();
                break;
              case '409':
                $node->field_plantilla_activo->setValue('No');
                $node->field_error_sincronizacion->setValue('Error en archivo, existe otro con el mismo nombre.');
                $node->save();
                break;
            }
            break;
          case 'documentos_calidad':
            switch ($postData->status) {
              case '200':
                $node->field_doc_calidad_enlace->setValue($postData->url);
                $node->save();
                break;
              case '409':
                $node->field_error_sincronizacion->setValue(json_encode($postData));
                $node->save();
                break;
            }
            break;
        }
        return $this->responseCode(Response::HTTP_ACCEPTED, []);
      } else {
        return $this->responseCode(Response::HTTP_NOT_FOUND, []);
      }
    }
    return $this->responseCode(Response::HTTP_FORBIDDEN, []);
  }

  public function respuestaDrivePlantilla()
  {
    $route_match = \Drupal::routeMatch();
    $node = $route_match->getParameter('node');
    $nodeId = $node->id();
    $html = '<div id="respuestaDrive" data-nodeid=' . $nodeId . '><div class="loader" ></div><div class="message" ></div></div>';
    $out['#markup'] = Markup::create($html);
    $out['#attached']['library'] = 'integracion_documento_calidad/respuestaDrive';
    $build['content'] = [$out];
    return $build;
  }

  public function respuestaDriveDocumento()
  {
    $route_match = \Drupal::routeMatch();
    $node = $route_match->getParameter('node');
    $nodeId = $node->id();
    $html = '<div id="respuestaDrive" data-nodeid=' . $nodeId . '><div class="loader" ></div><div class="message" ></div></div>';
    $out['#markup'] = Markup::create($html);
    $out['#attached']['library'] = 'integracion_documento_calidad/respuestaDrive';
    $build['content'] = [$out];
    return $build;
  }

  public function ajaxRespuestaDrive()
  {
    $route_match = \Drupal::routeMatch();
    $node = $route_match->getParameter('node');
    $type = $node->bundle();

    $out = FALSE;
    $mensaje = '';
    switch ($type) {
      case 'plantillas':
        if (
          !$node->field_plantilla_enlace->isEmpty() ||
          !$node->field_error_sincronizacion->isEmpty()
        ) {
          $out = TRUE;
          if (!$node->field_error_sincronizacion->isEmpty()) {
            $mensaje = $node->field_error_sincronizacion->getValue()[0]['value'];
          }
        }
        break;
      case 'documentos_calidad':
        if (
          !$node->field_doc_calidad_enlace->isEmpty() ||
          !$node->field_error_sincronizacion->isEmpty()
        ) {
          $out = TRUE;
          if (!$node->field_error_sincronizacion->isEmpty()) {
            $mensaje = $node->field_error_sincronizacion->getValue()[0]['value'];
          }
        }
        break;
    }
    $response = array(
      'cargado' => $out,
      'mensaje' => $mensaje,
    );
    return new JsonResponse($response);
  }

  public function crearPlantillaDocCalidadDrive(&$form_state)
  {
    \Drupal::logger('integracion_documento_calidad')->alert('crearPlantillaDocCalidadDrive');
    $fid = $form_state->getValue('field_plantilla_word')[0]['fids'];
    $node = $form_state->getFormObject()->getEntity();
    $file = File::load($fid[0]);
    $uuid = $this->generateUUID();
    $retorno = $this->enviarPlantillaAlDrive($file, $uuid);
    if ($retorno == '202') {
      $form_state->setValue('field_plantilla_drive_id', [array('value' => $uuid)]);
      $node = $form_state->getFormObject()->getEntity();
      $form_state->setRedirect(
        'entity.node.canonical',
        array('node' => $node->id()),
      );
    } else {
      $form_state->setErrorByName(
        'field_plantilla_word',
        'No se logro enviar el archivo al Drive.'
      );
      $form_state->setRebuild();
    }
    return $form_state;
  }

  public function crearDocumentoCalidadDrive($form_state)
  {
    \Drupal::logger('integracion_documento_calidad')->alert('crearDocumentoCalidadDrive');
    $node = $form_state->getFormObject()->getEntity();
    if (empty($form_state->getValue('field_doc_calidad_plantilla')[0])) {
      $form_state->setErrorByName(
        'field_doc_calidad_plantilla',
        'Campo debe estar diligenciado'
      );
      return $form_state;
    }

    $uuid = $this->generateUUID();
    $data = $this->getDatosEnvioDocumentoCalidad($form_state);
    $retorno = $this->crearDocumentoDrive($form_state, $data, $uuid);
    if ($retorno == '202') {
      $form_state->setValue('field_doc_calidad_drive_id', [array('value' => $uuid)]);
      $node = $form_state->getFormObject()->getEntity();
      $form_state->setRedirect(
        'entity.node.canonical',
        array('node' => $node->id()),
      );
    } else {
      $form_state->setErrorByName(
        'field_plantilla_word',
        'No se logro enviar el archivo al Drive.'
      );
      $form_state->setRebuild();
    }
    return $form_state;
  }

  private function generateUUID()
  {
    $uuid_service = \Drupal::service('uuid');
    return $uuid_service->generate();
  }

  private function getDatosEnvioDocumentoCalidad($form_state)
  {
    \Drupal::logger('integracion_documento_calidad')->alert('getDatosEnvioDocumentoCalidad');

    $current_user = \Drupal::currentUser();

    // INSTITUCIONAL
    $node_inf_institucional = Node::load(2);

    // PLANTILLA
    $field_doc_calidad_plantilla = $form_state->getValue('field_doc_calidad_plantilla');
    $node_plantilla = Node::load($field_doc_calidad_plantilla[0]['target_id']);
    $field_plantilla_word = $node_plantilla->get('field_plantilla_word');
    $fid = $field_plantilla_word[0]->getValue()['target_id'];
    $file = File::load($fid);
    $filename = $file->getFilename();

    // PROGRAMA ACADEMICO
    $field_programa_academico = $form_state->getValue('field_programa_academico');
    $node_prog_academico = Node::load($field_programa_academico[0]['target_id']);

    // ACREDITACIONES
    $acreditaciones = '';
    if (!$node_prog_academico->field_acreditaciones_vig->isEmpty()) {
      foreach ($node_prog_academico->field_acreditaciones_vig->getValue() as $k => $nid) {
        $acreditacion = Node::load($nid['target_id']);
        $acreditaciones .= $acreditacion->getTitle() . "\n";
      }
    }

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'programas_academicos');
    $programas_total = $query->count()->execute();

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_en_funcionamiento', 'En Funcionamiento', '=')
      ->condition('type', 'programas_academicos');
    $programas_en_funcionamiento = $query->count()->execute();

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_activo', 1, '=')
      ->condition('type', 'programas_academicos');
    $programas_activos = $query->count()->execute();

    $data = [
      'nombre' => '', /// NOMBRE DOCUMENTO
      'correo' => '', // CORREO DE USUARIO
      'archivo' => '', // PLANTILLA

      'programa_nombre' => '',
      'programa_snies' => '',
      'programa_titulo' => '',
      'programa_acta_creacion' => '',
      'programa_fecha_acta_creacion' => '',
      'programa_instancia_acta_creacion' => '',
      'programa_fecha_primer_registro' => '',
      'programa_origen' => '',
      'programa_activo' => '',
      'programa_funcionamiento' => '',
      'programa_ano' => '',
      'programa_periodo' => '',
      'programa_departamento' => '',
      'programa_responsables_registro' => '',
      'programa_responsables_acreditacion' => '',
      'programa_alarma_registro' => '',
      'programa_alarma_acreditacion' => '',
      'programa_division' => '',
      'programa_municipio' => '',
      'programa_direccion' => '',
      'programa_telefono' => '',
      'programa_email' => '',
      'programa_ampliacion' => '',
      'programa_extension' => '',
      'programa_convenios' => '',
      'programa_registro' => '',
      'programa_acreditacion' => '',
      'programa_nivel' => '',
      'programa_tipo' => '',
      'programa_periodicidad' => '',
      'programa_duracion' => '',
      'programa_jornada' => '',
      'programa_metodologia' => '',
      'programa_procodigo' => '',
      'programa_especializacion' => '',
      'programa_modalidad' => '',
      'programa_cupomax' => '',
      'programa_cupomin' => '',
      'programa_matricula' => '',
      'programa_creditos_obligatorios' => '',
      'programa_creditos_electivos' => '',
      'programa_creditos_total' => '',
      'programa_semanas' => '',
      'programa_perfil_profesional' => '',
      'programa_perfil_ocupacion' => '',
      'programa_objetivo_general' => '',
      'programa_objetivo_especificos' => '',
      'programa_curriculo' => '',
      'programa_poblacion' => '',

      'institucional_ciudad' => '',
      'institucional_direccion' => '',
      'institucional_telefono' => '',
      'institucional_paginaweb' => '',
      'institucional_acreditaciones' => '',
      'institucional_total_programas' => '',
      'institucional_snies' => '',
      'institucional_nit' => '',
      'institucional_activos' => '',
      'institucional_funcionamiento' => '',

      'institucional_res_creacion' => '',
      'institucional_perfil' => '',
    ];

    $data['nombre'] = $form_state->getValue('title')[0]['value'];
    $data['correo'] = $current_user->getEmail();
    $data['archivo'] = '/Documentos compartidos/Scripts/templates/' . $filename;

    $data['programa_nombre'] = $node_prog_academico->getTitle();
    $data['programa_snies'] = $node_prog_academico->field_snies->getValue()[0]['value'];
    $data['programa_titulo'] = $node_prog_academico->field_titulo_otorga->getValue()[0]['value'];
    $data['programa_acta_creacion'] = $node_prog_academico->field_numero_norma_registro_cal->getValue()[0]['value'];
    if (!empty($node_prog_academico->field_fecha_norma_reg_cal->getValue())) {
      $data['programa_fecha_acta_creacion'] = $node_prog_academico->field_fecha_norma_reg_cal->getValue()[0]['value'];
    }
    //dd($node_prog_academico->field_instancia_exp_norma_reg->getValue());
    if (!empty($node_prog_academico->field_instancia_exp_norma_reg->getValue())) {
      $data['programa_instancia_acta_creacion'] = Term::load($node_prog_academico->field_instancia_exp_norma_reg->getValue()[0]['target_id'])->getName();
    }
    $data['programa_fecha_primer_registro'] = $node_prog_academico->field_fecha_primer_reg_calificad->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_origen_programa->getValue())) {
      $data['programa_origen'] = $node_prog_academico->field_origen_programa->getValue()[0]['value'];
    }
    $data['programa_activo'] = $node_prog_academico->field_activo->getValue()[0]['value'];
    $data['programa_funcionamiento'] = $node_prog_academico->field_en_funcionamiento->getValue()[0]['value'];
    $data['programa_ano'] = $node_prog_academico->field_anio_inicio_programa->getValue()[0]['value'];
    $data['programa_periodo'] = $node_prog_academico->field_periodo->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_facultad->getValue())) {
      $data['programa_departamento'] = Term::load($node_prog_academico->field_facultad->getValue()[0]['target_id'])->getName();
    }
    //////////////verificar como sirve//////////////
    // 'programa_responsables_registro'] = $responsables_registro;
    //'programa_responsables_registro'] = Node::load($node_prog_academico->field_responsables_reg_calificad->getValue()[0]['target_id'])->name;
    // 'programa_responsables_acreditacion'] = $responsables_acreditacion;
    ///////////////////////////////////////////////

    $data['programa_alarma_registro'] = $node_prog_academico->field_alarmar_venc_acreditacion->getValue()[0]['value'];
    $data['programa_alarma_acreditacion'] = $node_prog_academico->field_alarmar_venc_acreditacion->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_escuela_dep->getValue()) && $node_prog_academico->field_escuela_dep->getValue()[0]['target_id'] != '0') {
      //dd($node_prog_academico->field_escuela_dep->getValue());
      $data['programa_division'] = Term::load($node_prog_academico->field_escuela_dep->getValue()[0]['target_id'])->getName();
    }
    $data['programa_municipio'] = Term::load($node_prog_academico->field_municipio_pa->getValue()[0]['target_id'])->getName();

    if (!empty($node_prog_academico->field_direccion_programa->getValue())) {
      $data['programa_direccion'] = $node_prog_academico->field_direccion_programa->getValue()[0]['value'];
    }
    if (!empty($node_prog_academico->field_telefono_programa->getValue())) {
      $data['programa_telefono'] = $node_prog_academico->field_telefono_programa->getValue()[0]['value'];
    }

    if (!empty($node_prog_academico->field_email_programa->getValue())) {
      $data['programa_email'] = $node_prog_academico->field_email_programa->getValue()[0]['value'];
    }

    if (!empty($node_prog_academico->field_ampliacion_lugar->getValue())) {
      $data['programa_ampliacion'] = $node_prog_academico->field_ampliacion_lugar->getValue()[0]['value'];
    }
    $data['programa_extension'] = $node_prog_academico->field_extension_programa->getValue()[0]['value'];
    $data['programa_convenios'] = $node_prog_academico->field_convenios_programa->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_registro_vigente->getValue())) {
      $data['programa_registro'] = $node_prog_academico->field_registro_vigente->getValue()[0]['value'];
    }

    if (!empty($node_prog_academico->field_acreditaciones_vig->getValue())) {
      $data['programa_acreditacion'] = Node::load($node_prog_academico->field_acreditaciones_vig->getValue()[0]['target_id'])->getTitle();
    }

    $data['programa_nivel'] = Term::load($node_prog_academico->field_nivel_programa->getValue()[0]['target_id'])->getName();
    //$data['programa_tipo'] = Node::load($node_prog_academico->field_tipo_programa->getValue()[0]['target_id'])->title;
    $data['programa_periodicidad'] = $node_prog_academico->field_periodicidad->getValue()[0]['value'];
    //$data['programa_duracion'] = Term::load($node_prog_academico->field_duracion_programa->getValue()[0]['target_id'])->getName();

    if (!empty($node_prog_academico->field_jornadas->getValue())) {
      if (!empty($node_prog_academico->field_jornadas->getValue()["target_id"])) {
        $data['programa_jornada'] = Term::load($node_prog_academico->field_jornadas->getValue()[0]['target_id'])->getName();
      }
    }

    // if (!empty($node_prog_academico->field_metodologia) && !empty($node_prog_academico->field_metodologia->getValue())) {
    //   $data['programa_metodologia'] = $node_prog_academico->field_metodologia->getValue()[0]['value'];
    // };
    $data['programa_procodigo'] = $node_prog_academico->field_procodigo->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_pertenece_prog_especializa->getValue())) {
      $data['programa_especializacion'] = $node_prog_academico->field_pertenece_prog_especializa->getValue()[0]['value'];
    }

    if (!empty($node_prog_academico->field_modalidad->getValue())) {
      $data['programa_modalidad'] = Term::load($node_prog_academico->field_modalidad->getValue()[0]['target_id'])->getName();
    }
    $data['programa_cupomax'] = $node_prog_academico->field_cupo_maximo->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_cupo_minimo->getValue())) {
      $data['programa_cupomin'] = $node_prog_academico->field_cupo_minimo->getValue()[0]['value'];
    }
    if (!empty($node_prog_academico->field_costo_matricula_nuevos->getValue())) {
      $data['programa_matricula'] = $node_prog_academico->field_costo_matricula_nuevos->getValue()[0]['value'];
    }
    $field_num_cred_oblig = 0;
    if (!empty($node_prog_academico->field_num_cred_oblig->getValue())) {
      $field_num_cred_oblig = $node_prog_academico->field_num_cred_oblig->getValue()[0]['value'];
      $data['programa_creditos_obligatorios'] = $field_num_cred_oblig;
    }

    $field_num_cred_elec = 0;
    if (!empty($node_prog_academico->field_num_cred_elec->getValue())) {
      $field_num_cred_elec = $node_prog_academico->field_num_cred_elec->getValue()[0]['value'];
      $data['programa_creditos_electivos'] = $field_num_cred_elec;
    }

    $data['programa_creditos_total'] = $field_num_cred_oblig + $field_num_cred_elec;

    $data['programa_semanas'] = $node_prog_academico->field_num_semanas_per_lectivo->getValue()[0]['value'];

    if (!empty($node_prog_academico->field_perfil_egresado->getValue())) {
      $data['programa_perfil_profesional'] = $node_prog_academico->field_perfil_egresado->getValue()[0]['value'];
    }
    if (!empty($node_prog_academico->field_perfil_ocupacional->getValue())) {
      $data['programa_perfil_ocupacion'] = $node_prog_academico->field_perfil_ocupacional->getValue()[0]['value'];
    }
    if (!empty($node_prog_academico->field_objetivo_general_programa->getValue())) {
      $data['programa_objetivo_general'] = $node_prog_academico->field_objetivo_general_programa->getValue()[0]['value'];
    }

    if (!empty($node_prog_academico->field_objetivos_especificos_prog->getValue())) {
      $data['programa_objetivo_especificos'] = $node_prog_academico->field_objetivos_especificos_prog->getValue()[0]['value'];
    }
    //$data['programa_curriculo'] = $node_prog_academico->field_registrar_curriculo_prog->getValue()[0]['value'];
    if (!empty($node_prog_academico->field_poblacion_objeto->getValue())) {
      $data['programa_poblacion'] = $node_prog_academico->field_poblacion_objeto->getValue()[0]['value'];
    }

    $data['institucional_ciudad'] = Term::load($node_prog_academico->field_municipio_pa->getValue()[0]['target_id'])->getName();
    $data['institucional_direccion'] = $node_inf_institucional->field_direccion->getValue()[0]['value'];
    $data['institucional_telefono'] = $node_inf_institucional->field_telefono->getValue()[0]['value'];

    if (!$node_inf_institucional->field_pagina_web->isEmpty()) {
      $node_pagina_web = Node::load($node_inf_institucional->field_pagina_web->getValue()[0]['target_id']);
      $data['institucional_paginaweb'] = $node_pagina_web->field_enlace->getValue()[0]['uri'];
    }

    $data['institucional_acreditaciones'] = $acreditaciones;
    $data['institucional_total_programas'] = $programas_total;
    $data['institucional_snies'] = $node_inf_institucional->field_codigo_snies->getValue()[0]['value'];
    $data['institucional_nit'] = $node_inf_institucional->field_nit->getValue()[0]['value'];
    $data['institucional_activos'] = $programas_activos;
    $data['institucional_funcionamiento'] = $programas_en_funcionamiento;

    if (!$node_inf_institucional->field_resolucion_->isEmpty()) {
      $data['institucional_res_creacion'] = Node::load($node_inf_institucional->field_resolucion_->getValue()[0]['target_id'])->getTitle();
    }
    //$data['institucional_perfil'] = $node_inf_institucional->field_perfil_egreso->getValue()[0]['value'];

    return $data;
  }

  private function validarDatosPrograma($node)
  {
    \Drupal::logger('integracion_documento_calidad')->alert('validarDatosPrograma');

    $faltan = [];

    $field_codigo_interno = $node->field_codigo_interno->getValue();
    if (!isset($field_codigo_interno[0]['value'])) {
      $faltan[] = 'Código Interno del Programa';
    }

    $field_snies = $node->field_snies->getValue();
    if (!isset($field_snies[0]['value'])) {
      $faltan[] = 'SNIES';
    }

    $field_extension_programa = $node->field_extension_programa->getValue();
    if (!isset($field_extension_programa[0]['value'])) {
      $faltan[] = 'Extensión';
    }

    $field_periodicidad = $node->field_periodicidad->getValue();
    if (!isset($field_periodicidad[0]['value'])) {
      $faltan[] = 'Periodicidad de admisión';
    }

    $field_pa_duracion_tiempo = $node->field_pa_duracion_tiempo->getValue();
    if (!isset($field_pa_duracion_tiempo[0]['value'])) {
      $faltan[] = 'Duración estimada del programa';
    }

    $field_modalidad = $node->field_modalidad->getValue();
    if (!isset($field_modalidad[0]['target_id'])) {
      $faltan[] = 'Especialidad nivel de formación maestría';
    }

    $field_instancia_exp_norma_reg = $node->field_instancia_exp_norma_reg->getValue();
    if (!isset($field_instancia_exp_norma_reg[0]['target_id'])) {
      $faltan[] = 'Instancia que expide el acta de creación';
    }

    $field_num_cred_oblig = $node->field_num_cred_oblig->getValue();
    $field_num_cred_elec = $node->field_num_cred_elec->getValue();
    if (
      !isset($field_num_cred_oblig[0]['value']) &&
      !isset($field_num_cred_elec[0]['value'])
    ) {
      $faltan[] = 'Número de Créditos Obligatorios y/o Número de Créditos Electivos';
    }

    $field_activo = $node->field_activo->getValue();
    if (!isset($field_activo[0]['value'])) {
      $faltan[] = 'Activo';
    }

    $field_cupo_maximo = $node->field_cupo_maximo->getValue();
    if (!isset($field_cupo_maximo[0]['value'])) {
      $faltan[] = 'Número de estudiantes del primer periodo';
    }

    $field_procodigo = $node->field_procodigo->getValue();
    if (!isset($field_procodigo[0]['value'])) {
      $faltan[] = 'Centro de costos';
    }

    $field_nivel_programa = $node->field_nivel_programa->getValue();
    if (!isset($field_nivel_programa[0]['target_id'])) {
      $faltan[] = 'Nivel del Programa (dos caracteres)';
    }

    return $faltan;
  }

  public function finalizarDocumentoCalidadDrive($node)
  {

    $titleWindow = 'Confirmación de acción';
    $htmlWindow = "¿Desea finalizar el documento de calidad: <br>" .
      "<b>" . $node->getTitle() . "</b>?<br><br>" .
      '<a href="/node/' . $node->id() . '/finalizarDocumentoCalidadDriveCurl" class="btn btn-primary">Confirmar</a>';

    $response = new AjaxResponse();
    $dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogText['#markup'] = $htmlWindow;
    $response->addCommand(new OpenModalDialogCommand($titleWindow, $dialogText, ['width' => '600']));
    return $response;

  }

  public function finalizarDocumentoCalidadDriveCurl($node)
  {

    $settings = Settings::get('OnlineDrive', '1111');
    $field_doc_calidad_drive_id = $node->get('field_doc_calidad_drive_id');
    $nombre = $node->getTitle();
    $uuid = $field_doc_calidad_drive_id->getValue()[0]['value'];

    $post = array(
      'uid' => $uuid,
      'usuarios' => [],
      'archivo' => '/Documentos compartidos/Scripts/' . $nombre . '.docx',
    );

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $settings['finalize_item'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
          'Cookie: ' . $settings['cookie'],
          'Content-Type:application/json'
        ),
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('integracion_documento_calidad')->alert('finalizarDocumentoCalidadDrive->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      $this->setEstadoDocumento($node, 'Finalizado');
    } else {
      $error = curl_error($curl);
      \Drupal::messenger()->addError($error);
      \Drupal::logger('integracion_documento_calidad')->alert('finalizarDocumentoCalidadDrive->error: ' . $error);
    }

    $markup = "<div>Documento Calidad '" . $node->getTitle() . "' Finalizado.</div>" .
      "<div><a href='" . $node->toUrl()->toString() . "' >Volver</a></div>";
    $build['content'] = [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $build;
  }

  public function reactivarDocumentoCalidadDrive($node)
  {
    if (!empty($faltan)) {
      $titleWindow = 'Programa académico incompleto';
      $faltantes = '';
      foreach ($faltan as $key => $faltante) {
        $faltantes = '<div>- ' . $faltante . '</div>' . $faltantes;
      }
      $htmlWindow = '<div>La información del programa académico <b>' .
        $node->getTitle() . '</b> se encuentra incompleta para el envio a Banner. </div><br>' .
        '<div>Faltan las siguientes campos por diligenciar:</div><br>' . $faltantes;
    } else {
      $titleWindow = 'Confirmación de acción';
      $htmlWindow = "¿Desea reactivar el documento de calidad: <br>" .
        "<b>" . $node->getTitle() . "</b>?<br><br>" .
        '<a href="/node/' . $node->id() . '/reactivarDocumentoCalidadDriveCurl" class="btn btn-primary">Confirmar</a>';
    }

    $response = new AjaxResponse();
    $dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogText['#markup'] = $htmlWindow;
    $response->addCommand(new OpenModalDialogCommand($titleWindow, $dialogText, ['width' => '600']));
    return $response;
  }

  public function reactivarDocumentoCalidadDriveCurl($node)
  {
    $settings = Settings::get('OnlineDrive', '1111');
    $field_doc_calidad_drive_id = $node->get('field_doc_calidad_drive_id');
    $uuid = $field_doc_calidad_drive_id->getValue()[0]['value'];
    $nombre = $node->getTitle();
    $user = \Drupal::currentUser();
    $user_email = $user->getEmail();

    $post = array(
      'uid' => $uuid,
      'correo' => $user_email,
      'archivo' => '/Documentos compartidos/Scripts/' . $nombre . '.docx',
    );

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $settings['reactivate_item'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
          'Cookie: ' . $settings['cookie'],
          'Content-Type:application/json'
        ),
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('integracion_documento_calidad')->alert('finalizarDocumentoCalidadDrive->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      $this->setEstadoDocumento($node, 'En edición');
    } else {
      $error = curl_error($curl);
      \Drupal::messenger()->addError($error);
      \Drupal::logger('integracion_documento_calidad')->alert('finalizarDocumentoCalidadDrive->error: ' . $error);
    }
    $markup = "<div>Documento Calidad '" . $node->getTitle() . "' Reactivado.</div>" .
      "<div><a href='" . $node->toUrl()->toString() . "' >Volver</a></div>";
    $build['content'] = [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $build;
  }

  public function zipDocumentoCalidadDrive($node)
  {
    if (!empty($faltan)) {
      $titleWindow = 'Programa académico incompleto';
      $faltantes = '';
      foreach ($faltan as $key => $faltante) {
        $faltantes = '<div>- ' . $faltante . '</div>' . $faltantes;
      }
      $htmlWindow = '<div>La información del programa académico <b>' .
        $node->getTitle() . '</b> se encuentra incompleta para el envio a Banner. </div><br>' .
        '<div>Faltan las siguientes campos por diligenciar:</div><br>' . $faltantes;
    } else {
      $titleWindow = 'Confirmación de acción';
      $htmlWindow = "¿Desea enviar por correo el ZIP del documento de calidad: <br>" .
        "<b>" . $node->getTitle() . "</b>?<br><br>" .
        '<a href="/node/' . $node->id() . '/zipDocumentoCalidadDriveCurl" class="btn btn-primary">Confirmar</a>';
    }

    $response = new AjaxResponse();
    $dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogText['#markup'] = $htmlWindow;
    $response->addCommand(new OpenModalDialogCommand($titleWindow, $dialogText, ['width' => '600']));
    return $response;
  }

  public function zipDocumentoCalidadDriveCurl($node)
  {
    $settings = Settings::get('OnlineDrive', '1111');
    $field_doc_calidad_drive_id = $node->get('field_doc_calidad_drive_id');
    $uuid = $field_doc_calidad_drive_id->getValue()[0]['value'];
    $nombre = $node->getTitle();
    $user = \Drupal::currentUser();
    $user_email = $user->getEmail();

    $post = array(
      'uid' => $uuid,
      'correo' => $user_email, // llega el correo con el ZIP
      'archivo' => '/Documentos compartidos/Scripts/' . $nombre . '.docx',
    );

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $settings['zip_item'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
          'Cookie: ' . $settings['cookie'],
          'Content-Type:application/json'
        ),
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('integracion_documento_calidad')->alert('ZipDocumentoCalidadDrive->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      $this->setEstadoDocumento($node, 'En edición');
    } else {
      $error = curl_error($curl);
      \Drupal::messenger()->addError($error);
      \Drupal::logger('integracion_documento_calidad')->alert('ZipDocumentoCalidadDrive->error: ' . $error);
    }
    $markup = "<div>ZIP del Documento Calidad '" . $node->getTitle() . "' fue enviado.</div>" .
      "<div><a href='" . $node->toUrl()->toString() . "' >Volver</a></div>";
    $build['content'] = [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $build;
  }

  public function getEstadoDocumento($node)
  {
    $field_estado = $node->field_doc_calidad_activo->getValue();
    $field_estado = reset($field_estado);
    if (empty($field_estado['value'])) {
      return FALSE;
    }
    return $field_estado['value'];
  }
  public function setEstadoDocumento($node, $value)
  {
    $node->field_doc_calidad_activo->setValue($value);
    return $node->save();
  }

  private function crearDocumentoDrive($form_state, $data, $uuid)
  {
    $settings = Settings::get('OnlineDrive', '1111');
    $current_user = \Drupal::currentUser();
    $field_doc_calidad_plantilla = $form_state->getValue('field_doc_calidad_plantilla')[0];
    $node_plantilla = Node::load($field_doc_calidad_plantilla['target_id']);
    $fid = $node_plantilla->get('field_plantilla_word');
    $file = File::load($fid->getValue()[0]['target_id']);
    $filename = $file->getFilename();

    $post = array(
      "uid" => $uuid,
      'archivo' => '/Documentos compartidos/Scripts/templates/' . $filename,
      "correo" => $current_user->getEmail(),
    );
    $post = array_merge($post, $data);

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $settings['create_document'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
          'Cookie: ' . $settings['cookie'],
          'Content-Type:application/json'
        ),
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    \Drupal::logger('integracion_documento_calidad')->alert('crearDocumentoDrive->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      return $httpcode;
    } else {
      $error = curl_error($curl);
      \Drupal::messenger()->addError($error);
      \Drupal::logger('integracion_documento_calidad')->alert('crearDocumentoDrive->error: ' . $error);
      return $error;
    }
  }

  private function enviarPlantillaAlDrive($file, $uuid)
  {
    $settings = Settings::get('OnlineDrive', '1111');
    $uri = $file->getFileUri();
    $file_full_path = \Drupal::service('file_system')->realpath($uri);
    $filename = $file->getFilename();
    $post = array(
      'file' => new \CURLFile($file_full_path),
      'name' => $filename,
      'uid' => $uuid
    );

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $settings['upload_template'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_HTTPHEADER => array(
          'Cookie: ' . $settings['cookie']
        ),
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('integracion_documento_calidad')->alert('enviarPlantillaAlDrive->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      return $httpcode;
    } else {
      $error = curl_error($curl);
      \Drupal::messenger()->addError($error);
      \Drupal::logger('integracion_documento_calidad')->alert('enviarPlantillaAlDrive->error: ' . $error);

      return $error;
    }
  }

  private function responseCode($code, $data)
  {
    return new JsonResponse(
      $data,
      $code,
      ['content-type' => 'application/json']
    );
  }

  /*
  CREATE EL ZIP
  https://prod-13.westus.logic.azure.com:443/workflows/4433379fa5db40eebabbd4948384a259/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=NE715hc13Fh0SfO7hY-C3cjRshrurUZl0CketS9OrEA

  POST
  {
    "uid": "uid",
    "correo": "duvan.munoz@zymdev.com", // llega el correo con el ZIP
    "archivo": "/Documentos compartidos/Scripts/Untitled.docx"
  }
  */

}
