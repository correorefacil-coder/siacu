<?php

namespace Drupal\import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use \Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use \Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\user\Entity\User;

/**
 * Returns responses for Import routes.
 */
class ImportController extends ControllerBase {

  /**
   * Import Campo Amplio.
   */
  public function campoAmplio() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/CampoAmplio.csv";

    $vid = "campo_amplio";
    $this->delete_terms_from_vocab($vid);

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        $new_term = Term::create([
          'vid' => $vid,
          'name' => $datos[0],
        ]);
        $new_term->enforceIsNew();
        $new_term->save();
        $message = "Creado CampoAmplio: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Campo Especifico.
   */
  public function campoEspecifico() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/CampoEspecifico.csv";

    $vid = "campo_especifico";
    $this->delete_terms_from_vocab($vid);

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        $termID = $this->getTermByName($datos[1],'campo_amplio');
        $new_term = Term::create([
          'vid' => $vid,
          'name' => $datos[0],
          'field_campo_amplio' => $termID,
        ]);
        $new_term->enforceIsNew();
        $new_term->save();
        $message = "Creado Campo Especifico: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Campo Especifico.
   */
  public function campoDetallado() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/CampoDetallado.csv";

    $vid = "campo_detallado";
    $this->delete_terms_from_vocab($vid);

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        $termID = $this->getTermByName($datos[1],'campo_especifico');
        $new_term = Term::create([
          'vid' => $vid,
          'name' => $datos[0],
          'field_campo_especifico' => $termID,
        ]);
        $new_term->enforceIsNew();
        $new_term->save();
        $message = "Creado CampoDetallado: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Categoria Adjuntos .
   */
  public function categoriaAdjuntos() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/categoriaAdjuntos.csv";

    $vid = "categorias_adjuntos";
    $this->delete_terms_from_vocab($vid);

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        $newTerm = [
          'vid' => $vid,
          'name' => $datos[0]
        ];
        if (!empty($datos[1])) {
          $termID = $this->getTermByName($datos[1],'categorias_adjuntos');
          $newTerm['parent'] = $termID;
        }
        $termID = $this->getTermByName($datos[1],'categorias_adjuntos');
        $new_term = Term::create($newTerm);
        $new_term->enforceIsNew();
        $new_term->save();
        $message = "Creado CategoriaAdjunto: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Adjuntos .
   */
  public function adjuntos() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/adjuntos.csv";
    $fileRepository = \Drupal::service('file.repository');
    $fileSystem = \Drupal::service('file_system');
    $base = \Drupal::config('system.file')->get('default_scheme');

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        // $urlFile = explode("/",$datos[2]);
        // $nameFile = array_pop($urlFile);
        // $dir = $base.'://'.implode("/",$urlFile);
        // $destination =  $base.'://'.$datos[2];
        // $fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);

        // // Create file object from remote URL.
        // $data = 'data';
        // $file = $fileRepository->writeData($data, $destination, FileSystemInterface::EXISTS_REPLACE);

        // $new_file = File::create(['uri' => $file->getFileUri()]);
        // $new_file->setPermanent();
        // $new_file->save();

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'adjuntos');
        $query->condition('title', $datos[0]);

        $res = $query->execute();
        $nodeID = reset($res);
        $accion;
        if (empty($nodeID)) {
          continue;
          $accion = 'Creado';
          $newNode = Node::create(['type' => 'adjuntos']);
          $newNode->set('title', $datos[0]);

        } else {
          $accion = 'Actualizado';
          $newNode = Node::load($nodeID);
        }

        $newNode->set('body', ['value' => $datos[1], 'format' => 'basic_html']);
        $newNode->set('field_nota', $datos[4]);

        // $newNode1 = [
        //   'type' => 'adjuntos',
        //   'title' => $datos[0],
        //   'body' => [
        //     'value' => $datos[1],
        //     'format' => 'basic_html',
        //   ],
        //   'field_file' => [
        //     'target_id' => $new_file->id(),
        //   ],
        //   'field_nota' => $datos[4],
        // ];

        if (!empty($datos[3])) {
          $termID = $this->getTermByName($datos[3], 'categorias_adjuntos');
          $newNode->set('field_categoria', ['target_id' => $termID]);
        }
        //$node = Node::create($newNode);
        $newNode->save();

        $message = $accion . " Adjunto: " . $datos[0];
        \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Historial Acreditaciones
   */
  public function historialAcreditaciones() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/historialAcreditaciones.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {

        $newNode = Node::create(['type' => 'historio_acreditaciones']);
        $newNode->set('title', $datos[0]);
        $newNode->set('field_categoria_acreditacion', $datos[1]);
        $newNode->set('field_entidad_acreditadora', $datos[2]);
        $newNode->set('field_tipo_acreditacion', $datos[10]);
        $newNode->set('field_vigencia_acredita', $datos[11]);
        $newNode->set('field_doc_autoeval', $this->getNodeByTitle($datos[12],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_condiciones_iniciales', $this->getNodeByTitle($datos[13],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_cometarios_eval_extern', $this->getNodeByTitle($datos[15],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_eval_externa', $this->getNodeByTitle($datos[16],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_resolucion', $this->getNodeByTitle($datos[17],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_evidencias_adicionales', $this->getNodeByTitle($datos[18],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_resolucion', $datos[19]);

        // DATES
        $newNode->set('field_envio_autoevaluacion_men_a', $this->dateProcess($datos[3]));
        $newNode->set('field_fecha', $this->dateProcess($datos[5]));
        $newNode->set('field_fecha_vencimiento', $this->dateProcess($datos[6]));
        $newNode->set('field_visita_pares_fin', $this->dateProcess($datos[7]));
        $newNode->set('field_visita_pares_acreditacion', $this->dateProcess($datos[8]));
        $newNode->set('field_recepcion_resolucion_acred', $this->dateProcess($datos[9]));
        $newNode->set('field_recepcion_documento_dcpa_a', $this->dateProcess($datos[14]));

        // TERMS
        if (!empty($datos[4])) {
          $termID = $this->getTermByName($datos[4],'estado_acreditacion');
          $newNode->set('field_estado_acreditacion', ['target_id' => $termID]);
        }

        $newNode->save();
        $message = "Creado historialAcreditaciones: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Historial RegistroCalificado
   */
  public function historialRegistroCalificado() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/historialRegistroCalificado.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {

        $newNode = Node::create(['type' => 'historico_registro_calificado']);
        $newNode->set('title', $datos[0]);
        $newNode->set('field_reg_calif_categoria', $datos[1]);
        $newNode->set('field_doc_autoeval1', $this->getNodeByTitle($datos[2],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_autoeval2', $this->getNodeByTitle($datos[3],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_comentarios_evexterna', $this->getNodeByTitle($datos[4],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_evaluacion_externa', $this->getNodeByTitle($datos[5],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_doc_registro_calificado', $this->getNodeByTitle($datos[6],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_registro_doc_resolucion', $this->getNodeByTitle($datos[7],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_envio_autoevaluacion_men', $this->dateProcess($datos[8])); // FECHA
        $newNode->set('field_evidencias_adicionales', $this->getNodeByTitle($datos[10],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_recepcion_resolucion', $this->dateProcess($datos[11])); // FECHA
        $newNode->set('field_fecha_ini_proacre_hist', $this->dateProcess($datos[12])); // FECHA
        $newNode->set('field_fecha', $this->dateProcess($datos[13])); // FECHA
        $newNode->set('field_fecha_ven_hito_regcal', $this->dateProcess($datos[14])); // FECHA
        $newNode->set('field_recepcion_documento_dcpa', $this->dateProcess($datos[15])); // FECHA
        $newNode->set('field_resolucion', $datos[16]);
        $newNode->set('field_histo_registro_saces', $this->getNodeByTitle($datos[17],'saces_programa'));
        $newNode->set('field_histo_registro_saces2', $this->getNodeByTitle($datos[18],'saces_ii'));
        $newNode->set('field_vigenica_histo_reg_cal', $datos[19]);
        $newNode->set('field_visita_pares', $this->dateProcess($datos[20])); // FECHA

        if (!empty($datos[9])) {
          $termID = $this->getTermByName($datos[9],'estado_registro_calificado');
          $newNode->set('field_estado_registro_calificado', ['target_id' => $termID]);
        }

        $newNode->save();
        $message = "Creado historialRegistroCalificado: ".$datos[0];
         \Drupal::messenger()->addMessage($message);
      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Historial de Cambios
   */
  public function historialDeCambios() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/historialDeCambios.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'historico_cambios');
        $query->condition('title', $datos[0]);

        $res = $query->execute();
        $nodeID = reset($res);
        $accion;
        if (empty($nodeID)) {
          continue;
          $accion = 'Creado';
          $newNode = Node::create(['type' => 'historico_cambios']);
          $newNode->set('title', $datos[0]);

        } else {
          $accion = 'Actualizado';
          $newNode = Node::load($nodeID);
        }
        $newNode->set('title', $datos[0]);
        $newNode->set('field_resolucion_conaca', $datos[2]);
        $newNode->set('field_fecha_resolucion_conaca', $this->dateProcess($datos[3])); // FECHA
        $newNode->set('field_soporte_resolucion', $this->getNodeByTitle($datos[4], 'adjuntos'));  // ADJUNTOS
        $newNode->set('field_resolucion_ministerio', $datos[5]);
        $newNode->set('field_fecha_resolucion_ministeri', $this->dateProcess($datos[6])); // FECHA
        $newNode->set('field_soporte_resolucion_ministe', $this->getNodeByTitle($datos[7], 'adjuntos'));  // ADJUNTOS
        $newNode->set('field__histo_cambios_fecha_imple', $this->dateProcess($datos[8])); // FECHA
        $newNode->set('body', ['value' => ($datos[9]), 'format' => 'basic_html']);

        if (!empty($datos[1])) {
          $terms = explode('"', $datos[1]);
          $termsID = $this->getTermsByNames($terms, 'tipo_cambio_historico_programa');
          $field_tipo_cambio = [];
          foreach ($termsID as $key => $termID) {
            $field_tipo_cambio[] = ['target_id' => $termID];
          }
          $newNode->set('field_tipo_cambio', $field_tipo_cambio);
        }

        $newNode->save();
        $message = $accion . " historialDeCambios: " . $datos[0];
        \Drupal::messenger()->addMessage($message);
      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import acreditaciones Vigentes
   */
  public function acreditacionesVigentes() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/acreditacionesVigentes.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {

        $newNode = Node::create(['type' => 'acreditaciones_vigentes']);
        $newNode->set('title', $datos[0]);
        $newNode->set('field_fecha_resol_acreditacion', $this->dateProcess($datos[1])); // FECHA
        $newNode->set('field_fecha_vencimiento_acred', $this->dateProcess($datos[2])); // FECHA
        $newNode->set('field_numero_resol_acredita', $datos[3]);
        $newNode->set('field_resolucion_acreditacion', $this->getNodeByTitle($datos[4],'adjuntos'));  // ADJUNTOS
        $newNode->set('field_tiempo_vigencia_acreditaci', $datos[5]);
        $newNode->set('field_tipo_acredtacion', $datos[6]);

        $newNode->save();
        $message = "Creado acreditacionesVigentes: ".$datos[0];
         \Drupal::messenger()->addMessage($message);
      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import usuarios
   */
  public function usuarios() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/usuarios.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {
        $roles = str_replace(".","_",$datos[2]);
        $roles = str_replace('"','',$roles);
        $roles = strtolower($roles);
        $newUser = User::create([
          'roles' => explode(";",$roles),
          'status' => 1,
        ]);
        $newUser->setEmail($datos[0]);
        $newUser->setUsername($datos[1]);
        //$newUser->set('roles', $datos[2]);
        $newUser->set('field_primer_nombre', $datos[1]);
        $newUser->set('field_cas', $datos[3]);
        $newUser->save();
        $message = "Creado usuarios: ".$datos[0];
         \Drupal::messenger()->addMessage($message);
      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import dependencias.
   */
  public function dependencias() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/dependencias.csv";

    $vid = "dependencia";
    $this->delete_terms_from_vocab($vid);

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

        $new_term = Term::create([
          'vid' => $vid,
          'name' => $datos[1],
          'field_abreviatura' => $datos[2],
          'field_codigo_dep' => $datos[3],
        ]);
        $new_term->enforceIsNew();
        $new_term->save();
        $message = "Creado dependencias: ".$datos[0];
         \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

  /**
   * Import Programas Academicos
   */
  public function programasAcademicos() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path."/csv/programasAcademicos.csv";

    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 0, ",")) !== FALSE) {

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'programas_academicos');
        $query->condition('title', $datos[0]);
        $query->condition('field_snies', $datos[118]);

        $res = $query->execute();
        $nodeID = reset($res);
        $accion;
        if (empty($nodeID)) {
          $accion = 'Creado';
          $newNode = Node::create(['type' => 'programas_academicos']);
          $newNode->set('title', $datos[0]);

        }else {
          $accion = 'Actualizado';
          $newNode = Node::load($nodeID);
        }

        /* NODES

        field_pep_proyecto_educativo_del -
        field_pa_planes_historicos -
        field_planes_estudios -
        field_registro_calificado_unico -
        field_sede -

        */

        $newNode->set('field_acreditaciones_vig', $this->getNodeByTitle($datos[1],'acreditaciones_vigentes')); // acreditaciones_vigentes
        $newNode->set('field_convenio_ref', $this->getNodeByTitle($datos[7 ],'convenio'));
        $newNode->set('field_evidencias_adicionales_pro', $this->getNodeByTitle($datos[56],'adjuntos'));
        $newNode->set('field_historico_acreditaciones', $this->getNodeByTitle($datos[58],'historio_acreditaciones'));
        $newNode->set('field_historico_actas', $this->getNodeByTitle($datos[59],'adjuntos'));
        $newNode->set('field_historico_autoevaluaciones', $this->getNodeByTitle($datos[60],'historico_autoevaluaciones'));
        $newNode->set('field_historico_cambios', $this->getNodeByTitle($datos[61],'historico_cambios'));
        $newNode->set('field_historico_registros_cal', $this->getNodeByTitle($datos[62],'historico_registro_calificado'));
        $newNode->set('field_norma_extension', $this->getNodeByTitle($datos[32],'adjuntos'));
        $newNode->set('field_norma_registro_calificado', $this->getNodeByTitle($datos[40],'adjuntos'));

        /* FIELDS

        $newNode->set('field_convenio_docencia -
        $newNode->set('field_cred_formacion_general -
        $newNode->set('field_saces_sintesis -
        $newNode->set('field_linea_investigacion -
        $newNode->set('field_num_cred_ciclo_basico -
        $newNode->set('field_cred_ciclo_profesional -
        $newNode->set('field_cupo_minimo -
        $newNode->set('field_periodo -
        $newNode->set('field_planes_de_estudios_vigente -
        $newNode->set('field_total_creditos -
        $newNode->set('field_area_investigacion -

        */
        if ($datos[2] == 'true') {
          $alarmar_venc_acreditacion = 1;
        } else {
          $alarmar_venc_acreditacion = 0;
        }
        $newNode->set('field_alarmar_venc_acreditacion', $alarmar_venc_acreditacion);
        if ($datos[3] == 'true') {
          $alarmas_reg_calificado = 1;
        } else {
          $alarmas_reg_calificado = 0;
        }
        $newNode->set('field_alarmas_reg_calificado', $alarmas_reg_calificado);
        if ($datos[4] == 'Activo') {
          $activo = TRUE;
        } else {
          $activo = FALSE;
        }
        $newNode->set('field_activo', $activo);

        $newNode->set('field_ampliacion_lugar', $datos[5]); // Boolean a texto
        $newNode->set('field_numero_norma_registro_cal', $datos[34]);
        $newNode->set('field_anio_inicio_programa', $datos[6]);
        $newNode->set('field_procodigo', $datos[38]);
        $newNode->set('field_convenios_programa', $datos[8]);


        if (!empty($datos[52])) {
          $field_costo_matricula_nuevos = str_replace('$', '', $datos[52]);
          $field_costo_matricula_nuevos = str_replace('.00', '', $field_costo_matricula_nuevos);
          $field_costo_matricula_nuevos = number_format((float) $field_costo_matricula_nuevos, 2, ',', '.');
          $newNode->set('field_costo_matricula_nuevos', $field_costo_matricula_nuevos);
          $termID = $this->getTermByName('Pesos Colombianos', 'moneda');
          $newNode->set('field_moneda_matricula_para_estu', ['target_id' => $termID]);
        }

        $newNode->set('field_cupos_convenio', $datos[53]);
        $newNode->set('field_codigo_interno', $datos[9]);
        $newNode->set('field_registro_snies', $datos[43]);
        $newNode->set('field_direccion_programa', $datos[12]);
        $newNode->set('field_pa_duracion_tiempo', $datos[14]);
        $newNode->set('field_email_programa', $datos[17]);
        $newNode->set('field_extension_programa', $datos[19]);
        $newNode->set('field_lineas_investigacion', $datos[66]);
        $newNode->set('field_num_cred_elec', $datos[69]);
        $newNode->set('field_num_cred_oblig', $datos[70]);
        $newNode->set('field_cupo_maximo', $datos[78]);
        $newNode->set('field_numero_norma_extension', $datos[35]);
        $newNode->set('field_num_resol_reg_calificado', $datos[33]);
        $newNode->set('field_num_semanas_per_lectivo', $datos[71]);
        $newNode->set('field_objetivos_especificos_prog', $datos[73]);
        $newNode->set('field_origen_programa', $datos[36]);
        $newNode->set('field_perfil_ocupacional', $datos[74]);
        $newNode->set('field_perfil_egresado', $datos[77]);
        $newNode->set('field_periodicidad', $datos[37]);
        $newNode->set('field_poblacion_objeto', $datos[76]);
        $newNode->set('field_objetivo_general_programa', $datos[72]);
        if ($datos[39] == 'Sí') {
          $field_registro_vigente = 'Si';
        }else{
          $field_registro_vigente = 'No';
        }
        $newNode->set('field_registro_vigente', $field_registro_vigente);
        $newNode->set('field_snies', $datos[118]);
        $newNode->set('field_telefono_programa', $datos[44]);

        if (!empty($datos[63])) {
          $termID = $this->getTermByName($datos[63], 'idioma');
          $newNode->set('field_idioma_programa', ['target_id' => $termID]);
          $newNode->set('field_segunda_lengua', $datos[79]);
        }

        $newNode->set('field_titulo_otorga', $datos[45]);
        $newNode->set('field_vigencia', $datos[46]);
        $newNode->set('field_docencia_servicio', $datos[47]);
        $newNode->set('field_pertenece_prog_especializa', $datos[80]);
        $newNode->set('field_areas_investigacion', $datos[81]);

        if ($datos[18] == 'En Funcionamiento') {
          $newNode->set('field_en_funcionamiento', 1);
        }else {
          $newNode->set('field_en_funcionamiento', 0);
        }

        if (!empty($datos[69]) || !empty($datos[70])) {
          $newNode->set('field_total_creditos', intval($datos[69]) + intval($datos[70]));
        }

        /* FECHAS
        */

        $newNode->set('field_fecha_ini_proacre_vig', $this->dateProcess($datos[22])); // FECHA
        $newNode->set('field_fecha_norma_reg_cal', $this->dateProcess($datos[20])); // FECHA
        $newNode->set('field_fecha_norma_extension', $this->dateProcess($datos[23])); // FECHA
        $newNode->set('field_fecha_resol_reg_calif', $this->dateProcess($datos[24])); // FECHA
        $newNode->set('field_reg_vigente_notificacion', $this->dateProcess($datos[25])); // FECHA
        $newNode->set('field_fecha_primer_reg_calificad', $this->dateProcess($datos[26])); // FECHA
        $newNode->set('field_fecha_vencimiento_reg_cal', $this->dateProcess($datos[21])); // FECHA

        /* TERMS

        field_entidades -
        field_enfasis -
        field_lugar_extension -
        field_moneda_matricula_para_estu -
        field_personal_academico -

        */

        if (!empty($datos[48])) {
          $termID = $this->getTermByName($datos[48],'campo_amplio');
          $newNode->set('field_pa_campo_amplio', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[49])) {
          $termID = $this->getTermByName($datos[49],'campo_detallado');
          $newNode->set('field_pa_campo_detllado', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[50])) {
          $termID = $this->getTermByName($datos[50],'campo_especifico');
          $newNode->set('field_pa_campo_especifico', ['target_id' => $termID]);
        }

        $termID = null;
        if (!empty($datos[15])) {
          $termID = $this->getTermByName($datos[15],'duracion_del_programa');
          $newNode->set('field_duracion_programa', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[54])) {
          $termID = $this->getTermByName($datos[54], 'unidades_medida');
          $newNode->set('field_pa_duracion_medida', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[13])) {
          $termID = $this->getTermByName($datos[13],'dependencia');
          $newNode->set('field_escuela_dep', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[16])) {
          $termID = $this->getTermByName($datos[16],'modalidad_programa');
          $newNode->set('field_modalidad', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[11])) {
          $termID = $this->getTermByName($datos[11],'dependencia');
          $newNode->set('field_facultad', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[27])) {
          $termID = $this->getTermByName($datos[27],'dependencia');
          $newNode->set('field_instancia_exp_norma_reg', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[28])) {
          $termID = $this->getTermByName($datos[28],'dependencia');
          $newNode->set('field_instancia_exp_extension', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[64])) {
          $termID = $this->getTermByName($datos[64],'jornadas_de_programa');
          $newNode->set('field_jornadas', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[31])) {
          $termID = $this->getTermByName($datos[31],'nivel_de_programa');
          $newNode->set('field_nivel_programa', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[29])) {
          $termID = $this->getTermByName($datos[29],'divipola');
          $newNode->set('field_municipio_pa', ['target_id' => $termID]);
        }
        $termID = null;
        if (!empty($datos[67])) {
          $termID = $this->getTermByName($datos[67], 'metodologia');
          $newNode->set('field_modalidad_szvpric', ['target_id' => $termID]);
        }

        /* USERS
        field_responsables_acreditacion - 41
        */
        $mails = explode(";",$datos[42]);
        $users = [];
        foreach ($mails as $key => $mail) {
          $user = $this->getUserByMail($mail);
          if (!empty($user)) {
            $users[] = ['target_id' => $user->id()];
          }
        }
        $newNode->set('field_responsables_reg_calificad', $users);

        $mails = explode(";", $datos[41]);
        $users = [];
        foreach ($mails as $key => $mail) {
          $user = $this->getUserByMail($mail);
          if (!empty($user)) {
            $users[] = ['target_id' => $user->id()];
          }
        }
        $newNode->set('field_responsables_acreditacion', $users);

        $newNode->save();
        $message = $accion." Programa Academico: ".$datos[0];
        \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Importacion Programas Academicos!'),
    ];
    return $build;
  }

  /**
   * Import Programas Academicos
   */
  public function programasAcademicosTextoEnriquecido()
  {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = $path . "/csv/programasAcademicosLargoExport.csv";
    $IDs = null;
    if (($gestor = fopen($file, "r")) !== FALSE) {
      while (($datos = fgetcsv($gestor, 0, ",")) !== FALSE) {

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'programas_academicos');
        $query->condition('title', $datos[0]);
        $query->condition('field_snies', $datos[1]);

        $res = $query->execute();
        $nodeID = reset($res);

        if (empty($nodeID)) {
          continue;
        }

        $excepciones = array(
          "especialización en derecho contractual"=> '',
          "maestría en periodismo" => '',
          "especialización en desarrollo familiar" => '',
          "maestría en educación mediada por tic" => '',
          "maestría en trastornos cognoscitivos y del aprendizaje" => '',
          "especialización en seguridad de la información" => '',
          "especialización en derecho laboral" => 'barranquilla',
          "especialización en tributación" => 'barranquilla',
          "especialización en ingeniería de sistemas hídricos urbanos" => '',
          "especialización en pavimentos y geotecnia vial" => '',
          "especialización en derecho público" => 'barranquilla',
          "especialización en transformación digital" => '',
        );

        $municipio = FALSE;
        $newNode = Node::load($nodeID);
        $title = strtolower($newNode->getTitle());
        $municipioID = $newNode->get('field_municipio_pa')->getValue();
        $municipioTerm = Term::load($municipioID[0]['target_id']);
        if (!empty($municipioTerm)) {
          $municipio = strtolower($municipioTerm->getName());
        }

        // CHECK EXCEPCION NOMBRE PROGRAMA
        if (isset($excepciones[$title])){
          // CHECK EXCEPCION MUNICIPIO PROGRAMA
          if (empty($excepciones[$title])){
            continue;
          }elseif ($excepciones[$title] == $municipio) {
            continue;
          }
        }
        /*
        "Nombre",
        "SNIES",
        "Municipio de Oferta del Programa",
        "Objetivo General del Programa",
        "Objetivos Específicos del Programa",
        "Perfil Ocupacional del Egresado",
        "Población Objeto",
        "RESULTADO(S) DE APRENDIZAJE ESPERADO(S) A NIVEL PROGRAMA"
        */

        // $newNode->set('field_objetivos_especificos_prog', ['value' => ($datos[4]), 'format' => 'basic_html']);
        // $newNode->set('field_perfil_ocupacional', ['value' => ($datos[5]), 'format' => 'basic_html']);
        // $newNode->set('field_poblacion_objeto', ['value' => ($datos[6]), 'format' => 'basic_html']);

        // TABULADO; NO ENRIQUECIDO
        $newNode->set('field_objetivo_general_programa', $datos[3]);
        $newNode->set('field_perfil_egresado', $datos[7]);
        $newNode->save();

        $IDs[] = $nodeID;
        $message = "Actualizacion Texto Enriquecido Programa Academico: " . $datos[0];
        \Drupal::messenger()->addMessage($message);

      }
      fclose($gestor);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => 'Actualizacion Texto Enriquecido Programas Academicos:<br>'. json_encode($IDs) ,
    ];
    return $build;
  }

  /**
   * Import Planes de Estudio
   */
  public function planesEstudio() {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $file = getcwd().'/'. $path."/csv/planesEstudio.json";

    $data = file_get_contents($file);
    $planes = json_decode($data,true);

    foreach ($planes as $titulo => $plan) {

      echo '---------------------------------------------<br><br>';
      echo 'Plan de estudio: ' . $titulo . '<br>';
      //dd($plan);

      $query = \Drupal::entityQuery('node');
      $query->condition('status', 1);
      $query->condition('type', 'plan_estudios');
      $query->condition('title', $titulo); //searching
      $res = $query->execute();
      $nodeID = reset($res);

      $accion = null;
      $plan_estudio = null;
      if (empty($nodeID)) {
        $accion = 'Creado';
        $plan_estudio = Node::create(['type' => 'plan_estudios']);
        $plan_estudio->set('title', $titulo);
      } else {
        $accion = 'Actualizado';
        $plan_estudio = Node::load($nodeID);
      }

      $bloqueTerms = [];
      $properties['vid'] = 'bloque_academico';
      // $bloqueTerms = \Drupal::entityTypeManager()
      //   ->getStorage('taxonomy_term')
      //   ->loadByProperties($properties);

      // IMPORTANDO BLOQUES ACADEMICOS
      foreach ($bloqueTerms as $key => $bloqueTerm) {
        $semestre = $bloqueTerm->getName();
        if (isset($plan['bloques'][$semestre])) {
          $asignaturas = $plan['bloques'][$semestre];
          foreach ($asignaturas as $key => $asignatura) {
            echo "-- Asignatura: ".$asignatura['titulo'].'<br>';
            $asignaturaNode = $this->getNodeByTitle($asignatura['titulo'], 'szvcata');
            if (!empty($asignaturaNode)) {
              $this->migracion_bloque_academico_plan_estudio_semestre($plan_estudio,$semestre, $asignatura, $asignaturaNode[0]['target_id']);
            } else {
              $msg = 'No existe asignatura: ' . $asignatura['titulo'];
              \Drupal::messenger()->addError($msg);
            }
          }
        }

        //$plan_estudio->save();
        $message = $accion . " Plan de estudio: " . $titulo;
        \Drupal::messenger()->addMessage($message);
      }

      // IMPORTANDO REGLAS
      foreach ($plan['reglas'] as $key => $regla) {
        $this->migracion_reglas_plan_estudio($plan_estudio,$regla);
        # code...
      }
      $plan_estudio->save();
      //dd($titulo);
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Importacion Planes de Estudio!'),
    ];
    return $build;
  }


  /**
   * Builds the response.
   */

  public function asignaturas()
  {

    $path = \Drupal::service('extension.path.resolver')->getPath('module', 'import');
    $csv = $path . "/csv/asignaturas.csv";
    $batch = [
      'title' => 'Importing CSV...',
      'operations' => [],
      'init_message' => 'Starting...',
      'progress_message' => 'Processed @current out of @total.',
      'error_message' => 'An error occurred during processing',
      'finished' => '\Drupal\import\Controller\ImportController::importFinished',
    ];

    if ($handle = fopen($csv, 'r')) {
      while ($line = fgetcsv($handle, 0, ';')) {
        $batch['operations'][] = [
          '\Drupal\import\Controller\ImportController::importLine',
          [array_map('base64_encode', $line)],
        ];
      }
      fclose($handle);
    }
    batch_set($batch);
    return batch_process('user');
  }

  /**
   * Process a single line.
   */
  public static function importLine($line, &$context)
  {
    $context['results']['rows_imported']++;
    $line = array_map('base64_decode', $line);
    $context['message'] = t('Importando asignatura ' . $context['results']['rows_imported']);

    \Drupal\import\Controller\ImportController::asignatura($line);

  }

  /**
   * Handle batch completion.
   */
  public static function importFinished($success, $results, $operations)
  {
    $messenger = \Drupal::messenger();
    $messenger->addMessage('Importadas ' . $results['rows_imported'] . ' asignaturas.');

    return 'The CSV import has completed.';
  }

  /**
   * Import asignaturas
   */
  public static function asignatura($linea)
  {
    $datos = str_getcsv($linea[0]);
    $newNode = Node::create(['type' => 'szvcata']);

    $newNode->set('title', $datos[0]);
    //$newNode->set('field_asignatura_clase_biblio', $datos[1]);
    $newNode->set('field_clasif', $datos[2]);
    //$newNode->set('field_codigo_departamento', $datos[3]);
    //$newNode->set('field_codigo_division', $datos[4]);
    $newNode->set('field_curso', $datos[5]);
    $newNode->set('field_descripcion_asignatura', $datos[6]);
    //$newNode->set('field_desc_clasif', $datos[7]);
    $newNode->set('field_departamento', $datos[8]);
    $newNode->set('field_division', $datos[9]);
    $newNode->set('field_desc_modo_calif', $datos[10]);
    // field_existe_biblioteca
    $newNode->set('field_estado_visualizacion', $datos[12]);
    $newNode->set('field_fuente_origen', $datos[13]);
    //$newNode->set('field_horas_trabajo_ind', $datos[14]);
    //$newNode->set('field_horas_practicas', $datos[15]);
    // field_idioma_bibliografia
    // TODO $newNode->set('field_idioma', $datos[17]);
    $newNode->set('field_justificacion_parcelacion', $datos[18]);
    //$newNode->set('field_justificacion_asignatura', $datos[19]);
    $newNode->set('field_materia', $datos[20]);
    $newNode->set('field_modo_calif', $datos[21]);
    // field_nombre_bibliografia
    $newNode->set('field_nivel', $datos[23]);
    $newNode->set('field_credito_acad', $datos[24]);
    $newNode->set('field_credito_fact', $datos[25]);

    if (!empty($datos[26])) {
      $newNode->set('field_horas_contacto', $datos[26]);
    }
    if (!empty($datos[27])) {
      $newNode->set('field_horas_lab', $datos[27]);
    }
    if (!empty($datos[28])) {
      $newNode->set('field_horas_teo', $datos[28]);
    }
    if (!empty($datos[29])) {
      $newNode->set('field_horas_otr', $datos[29]);
    }

    //$newNode->set('field_numero_semanas', $datos[30]);
    //$newNode->set('field_asignatura_par_objetivos', $datos[31]);
    $newNode->set('field_periodo_asignatura', $datos[32]);
    $newNode->set('field_asignaturas_par_per_efecti', $datos[33]);
    $newNode->set('field_periodo_final', $datos[34]);
    $newNode->set('field_periodo_efectivo', $datos[35]);
    $newNode->set('field_asignaturas_banner_per_efe', $datos[36]);
    $newNode->set('field_parte1', $datos[37]);
    // field_referencia_bibliografia
    //$newNode->set('field_perfil_egresado_asignatura', $datos[39]);
    //$newNode->set('field_segundo_idioma', $datos[40]);
    //$newNode->set('field_subtemas', $datos[41]);
    //$newNode->set('field_curso_thp', $datos[42]);
    //$newNode->set('field_curso_thtc', $datos[43]);
    //$newNode->set('field_curso_tht', $datos[44]);
    // field_asignaturas_tipo_biblio
    $newNode->set('field_tipo_horario', $datos[46]);
    $newNode->set('field_titulo_largo', $datos[47]);
    $newNode->set('field_titulo_ingles', $datos[48]);
    $newNode->set('field_titulo_asignatura', $datos[49]);
    $newNode->set('field_unidad_tematica', $datos[50]);
    /*
    1	Bibliografía básica de la asignatura => field_bibliografia_asignatura
    2	Clasificación de componente curricular => field_clasif
    3	Codigo departamento => field_codigo_departamento
    4	Código division => field_codigo_division
    5	Código de Curso => field_curso
    6	Descripción de la asignatura => field_descripcion_asignatura
    7	Descripción de clasificación de componente curricular => field_desc_clasif
    8	Descripción departamento => field_departamento
    9	Descripción división => field_division
    10	Descripción modo de calificación => field_desc_modo_calif
    11	field_existe_biblioteca
    12	Estado visualización => field_estado_visualizacion
    13	Fuente => field_fuente_origen
    14	Horas de trabajo independiente => field_horas_trabajo_ind
    15	Horas practicas => field_horas_practicas
    16	field_idioma_bibliografia
    17	Idioma asignatura => field_idioma => Referencia de entidad
    18	Justificación de la asignatura => field_justificacion_parcelacion
    19	Justificación del curso => field_justificacion_asignatura
    20	Código de Materia => field_materia
    21	Modo de calificación => field_modo_calif
    22	field_nombre_bibliografia
    23	Nivel de la asignatura => field_nivel
    24	Número de créditos académicos => field_credito_acad
    25	Número de créditos facturables => field_credito_fact
    26	Número de horas de contacto => field_horas_contacto
    27	Número de horas de laboratorio => field_horas_lab
    28	Número de horas teoricas => field_horas_teo
    29	Número de otras horas => field_horas_otr
    30	Número de semanas => field_numero_semanas
    31	Objetivo(s) del curso => field_asignatura_par_objetivos
    32	Periodo => field_periodo_asignatura
    33	Periodo Efectivo del SYLLABUS => field_asignaturas_par_per_efecti
    34	Periodo Final => field_periodo_final
    35	Periodo efectivo => field_periodo_efectivo
    36	Periodo efectivo del SYLLABUS de Banner => field_asignaturas_banner_per_efe
    37	SYLLABUS de Banner => field_parte1
    38	field_referencia_bibliografia
    39	Resultados de aprendizaje => field_perfil_egresado_asignatura
    40	Segundo idioma => field_segundo_idioma => Referencia de entidad
    41	Subtemas => field_subtemas
    42	Tamaño del curso laboratorio => field_curso_thp
    43	Tamaño del curso otros => field_curso_thtc
    44	Tamaño del curso teorico => field_curso_tht
    45	field_asignaturas_tipo_biblio
    46	Tipo de horario en que se ofrece la asignatura => field_tipo_horario
    47	Título Largo de la asignatura => field_titulo_largo
    48	Titulo en inglés de la asignatura => field_titulo_ingles
    49	Título Asignatura => field_titulo_asignatura
    50	Unidad temática => field_unidad_tematica


    // Contenidos de la asignatura => field_contenidos_asignatura
    */

    $newNode->save();

    $message = "Creada Asignatura: " . $datos[0];
    \Drupal::messenger()->addMessage($message);

    return $newNode->id();
  }

  /**
   * Import asignaturas
   */
  public static function correccionUnidades()
  {

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'actividades_procesos');
    $tids = $query->execute();
    $nodeStorage = \Drupal::entityTypeManager()
      ->getStorage('node');
    $nodes = $nodeStorage->loadMultiple($tids);

    foreach ($nodes as $key => $node) {
       //field_act_procesos_unidad
      $field_act_procesos_unidad = $node->get('field_act_procesos_unidad');

      if (!$field_act_procesos_unidad->isEmpty()) {
        $act_procesos_unidad = $field_act_procesos_unidad->getValue();
        $term_act_procesos_unidad = Term::load($act_procesos_unidad[0]['target_id']);
        var_dump($act_procesos_unidad[0]['target_id']);
        if(!empty($term_act_procesos_unidad)){
          var_dump($term_act_procesos_unidad->getName());
          var_dump($term_act_procesos_unidad->bundle());
          dd(get_class_methods($term_act_procesos_unidad));

        }
      }
    }
    $newNode = '';
    /*
    $datos = str_getcsv($linea[0]);
    $newNode = Node::create(['type' => 'szvcata']);

    $newNode->set('field_periodo_asignatura', $datos[32]);

    $newNode->save();

    $message = "Creada Asignatura: " . $datos[0];
    \Drupal::messenger()->addMessage($message);
  */
    return $newNode->id();
  }

  /**
   * Fix Usernames
   */
  public static function correccionUsuarios()
  {

    $query = \Drupal::entityQuery('user');
    $query->condition('status', 1);
    $query->condition('mail', 'uninorte.edu.co', 'CONTAINS');
    $tids = $query->execute();
    $userStorage = \Drupal::entityTypeManager()
      ->getStorage('user');
    $users = $userStorage->loadMultiple($tids);

    foreach ($users as $key => $user) {
      $email = $user->getEmail();
      $username = $user->getAccountName();

      if (strpos($username, 'uninorte.edu.co') === false) {
        $user->setUsername($email);
        $user->save();
      }

    }
    $build['content'] = [
      '#type' => 'item',
      '#markup' => 'Correccion Usuarios Realizada!',
    ];
    return $build;
  }

  private function migracion_bloque_academico_plan_estudio_semestre(&$plan_estudio, $semestre, $datos, $asignatura_id)
  {
    $bloques_ids = $plan_estudio->field_plan_estudios_asignaturas->getValue();
    // BUSCA SI EXISTE Y RETORNA
    foreach ($bloques_ids as $key => $bloque_id) {
      $bloque = Paragraph::load($bloque_id['target_id']);
      if (
        !$bloque->field_semestre->isEmpty() &&
        !$bloque->field_plan_estudios_asig_seme->isEmpty()
      ) {
        $field_semestre = $bloque->field_semestre->getValue();
        $asignatura_sem_id = $bloque->field_plan_estudios_asig_seme->getValue();
        $semestreTerm = Term::load($field_semestre[0]['target_id']);
        if (
          $field_semestre[0]['target_id'] != '0' &&
          $semestreTerm->getName() == $semestre &&
          $asignatura_sem_id[0]['target_id'] == $asignatura_id
        ) {
          return $bloque;
        }
      }
    }

    // SI NO EXISTE LO CREA
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'bloque_academico')
      ->condition('name', $semestre)
      ->range(0, 1);
    $semestre_id = $query->execute();
    $semestre_id = reset($semestre_id);
    $asignatura = Node::load($asignatura_id);

    $bloque = Paragraph::create([
      'type' => 'bloque_academico',
      'field_plan_estudios_asig_seme' => $asignatura,
      'field_semestre' => array('target_id' => $semestre_id),
      'field_plan_estudios_area' => $datos['area'],
      'field_plan_estudios_flexibilidad' => $datos['flexibilidad'],
      'field_plan_estudios_lineas' => $datos['lineas'],
      'field_plan_estudios_descripcion' => $datos['descripcion'],
      'field_plan_estudios_observacione' => $datos['observaciones'],
      'field_procesos_tipo_cambio_plan' => $datos['tipo_cambio'],
      'field_plan_estudios_justificacio' => $datos['justificacion'],
    ]);
    $bloque->save();
    $plan_estudio->field_plan_estudios_asignaturas->appendItem($bloque);
    $plan_estudio->save();

    //\Drupal::messenger()->addStatus('Bloque creado:' . $datos[0]);
    return $bloque;
  }

  private function migracion_reglas_plan_estudio(&$plan_estudio, $regla)
  {
    $reglas_ids = $plan_estudio->field_regla->getValue();
    // BUSCA SI EXISTE Y RETORNA
    /*
    field_prog_materia subject_base
    field_reglas_asignaturas_relacio related_subjects
    field_reglas_enfasis_creditos emphasis_credits
    field_reglas_electiva_enfasis type_rule
    field__reglas_enfasis_nombre emphasis_name
    */
    foreach ($reglas_ids as $key => $reglas_id) {
      $reglaNode = Node::load($reglas_id['target_id']);
      if(!empty($reglaNode)){
        $reglaNode->getTitle();
        if ($reglaNode->getTitle() == $regla['rule_title']) {
          return $regla;
        }
      }
    }

    // SI NO EXISTE LO CREA

    //ASIGNATURA BASE
    $asignaturaNode = $this->getNodeByTitle($regla['subject_base'] , 'szvcata');
    // ASIGNATURAS RELACIONADAS
    $asignaturasRelacionadas = [];
    if (!empty($regla['related_subjects'])) {
      $related_subjects = explode(";",$regla['related_subjects']);
      foreach ($related_subjects as $key => $asignatura) {
        $asignaturaRelacionada = $this->getNodeByTitle($asignatura, 'szvcata');
        if (!empty($asignaturaRelacionada)) {
          $asignaturasRelacionadas[] = $asignaturaRelacionada;
        }
      }
    }
    $regla_programa = Node::create([
      'type' => 'reglas_programa',
      'title' => $regla['rule_title'],
      'field_prog_materia' => $asignaturaNode,
      'field_reglas_asignaturas_relacio' => $asignaturasRelacionadas,
      'field_reglas_enfasis_creditos' => $regla['emphasis_credits'],
      'field_reglas_electiva_enfasis' => $regla['type_rule'],
      'field__reglas_enfasis_nombre' => $regla['emphasis_name'],
    ]);
    $regla_programa->save();
    $plan_estudio->field_regla->appendItem($regla_programa);
    $plan_estudio->save();

    //\Drupal::messenger()->addStatus('Bloque creado:' . $datos[0]);
    return $regla_programa;
  }

  /**
   * Utility: Delete all taxonomy terms from a vocabulary
   */
  private function delete_terms_from_vocab($vid) {

    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();

    if (empty($tids)) {
      return;
    }

    $term_storage = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $entities = $term_storage->loadMultiple($tids);

    $term_storage->delete($entities);
  }

  /**
   * Utility: find term by name and vid.
   */
  private function getTermByName($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }else{
      return;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Utility: find terms by name and vid.
   */
  private function getTermsByNames($names = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($names)) {
      $properties['name'] = $names;
    }else{
      return;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    $out = [];
    foreach ($terms as $key => $term) {
      $out[] = $term->id();
    }

    return $out;
  }

  /**
   * Utility: find node by title and type.
   */
  private function getNodeByTitle($title = NULL, $type = NULL) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    if (!empty($title)) {
      $title = explode(';',$title);
      $query->condition('title', $title, 'IN');
    }else{
      return;
    }
    if (!empty($type)) {
      $query->condition('type', $type);
    }
    $nids = $query->execute();
    $out = [];
    foreach ($nids as $key => $nid) {
      $out[] = ['target_id' => $nid];
    }
    return $out;
  }

  /**
   * Utility: find user by email
   */
  private function getUserByMail($mail = NULL) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    if (empty($mail)) {
      return;
    }
    $out = user_load_by_mail($mail);
    return $out;
  }

  private function dateProcess($date = NULL) {
    if (empty($date)) {
      return;
    }
    $dateTime = \DateTime::createFromFormat('d/m/Y',$date);
    return $dateTime->format('Y-m-d');
  }

}
