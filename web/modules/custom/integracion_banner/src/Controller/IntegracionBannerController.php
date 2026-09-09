<?php

namespace Drupal\integracion_banner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

class IntegracionBannerController extends ControllerBase {

  public function confirmaEnviarProgramaBanner(NodeInterface $node){

    // VALIDA DATOS COMPLETOS
    $faltan = $this->validarDatosPrograma($node);

    if (!empty($faltan)) {
      $titleWindow = 'Programa académico incompleto';
      $faltantes = '';
      foreach ($faltan as $key => $faltante) {
        $faltantes = '<div>- '.$faltante.'</div>'.$faltantes;
      }
      $htmlWindow = '<div>La información del programa académico <b>'.
        $node->getTitle().'</b> se encuentra incompleta para el envio a Banner. </div><br>'.
        '<div>Faltan las siguientes campos por diligenciar:</div><br>'.$faltantes;
    }else {
      $titleWindow = 'Confirmación de acción';
      $htmlWindow = "¿Desea enviar a Banner el programa academico: <br>".
        "<b>".$node->getTitle()."</b>?<br><br>".
        '<a href="/node/'.$node->id().'/enviarProgramaBanner" class="btn btn-primary">Confirmar</a>';
    }

    $response = new AjaxResponse();
    $dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogText['#markup'] = $htmlWindow;
    $response->addCommand(new OpenModalDialogCommand($titleWindow, $dialogText, ['width' => '600']));
    return $response;
  }

  public function enviarProgramaBanner(NodeInterface $node){

    // OBTIENE DATOS DEL PROGRAMA
    $data = $this->getDatosPrograma($node);

    // VERIFICA SI EXISTE UN REGISTRO ANTERIOR
    $existe = $this->verificarExistePrograma($data);

    $accion = '';
    if($existe) {
      // ACTUALIZA BANNER
      $hecho = $this->actualizarPrograma($data);
      $accion = 'actualizado';
    }else {
      // INSERTA BANNER
      $hecho = $this->insertaPrograma($data);
      $accion = 'añadido';
    }

    if($hecho === TRUE){
      $html = '<div>El programa académico <b>'.$node->getTitle().'</b> fue '.$accion.' exitosamiente en Banner</div>'.
        $node->toLink('Volver al programa académico')->toString();
    }else{
      $html = '<div>No se pudo realizar la accion:<br> <b>Error: </b> '.$hecho.'</div>'.
        $node->toLink('Volver al programa académico')->toString();
    }

    $build['content'] = ['#markup' => $html];
    return $build;
  }

  private function verificarExistePrograma($data){

    $res = [];
    $conn = $this->conexion();
    $query = "SELECT * FROM SZVPRIC WHERE SZVPRIC_CODE = :procodigo";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':procodigo', $data['SZVPRIC_CODE']);
    oci_execute($stid);
    oci_fetch_all($stid, $res);
    if(oci_num_rows($stid) != 0){
      return TRUE;
    }else {
      return FALSE;
    }

  }

  private function actualizarPrograma($data){
    $conn = $this->conexion();
    $query = "UPDATE
                SZVPRIC
              SET
                SZVPRIC_FECHA_CREA_DATE = TO_DATE(:SZVPRIC_FECHA_CREA_DATE,'DD/MM/YYYY'),
                SZVPRIC_FECHA_REG_DATE = TO_DATE(:SZVPRIC_FECHA_REG_DATE,'DD/MM/YYYY'),
                SZVPRIC_FECHA_CARGUE_SNIES = TO_DATE(:SZVPRIC_FECHA_CARGUE_SNIES,'DD/MM/YYYY'),
                SZVPRIC_FECHA_VENC_REGISTRO = TO_DATE(:SZVPRIC_FECHA_VENC_REGISTRO,'DD/MM/YYYY'),
                SZVPRIC_ACTIVITY_DATE = TO_DATE(:SZVPRIC_ACTIVITY_DATE,'DD/MM/YYYY'),
                SZVPRIC_OFRE_IND = :SZVPRIC_OFRE_IND,
                SZVPRIC_ICFES_DESC = :SZVPRIC_ICFES_DESC,
                SZVPRIC_TPRO_CODE = :SZVPRIC_TPRO_CODE,
                SZVPRIC_IND_VIGENTE = :SZVPRIC_IND_VIGENTE,
                SZVPRIC_ACTA_CONSEJO = :SZVPRIC_ACTA_CONSEJO,
                SZVPRIC_COD_CIUDAD_PROG = :SZVPRIC_COD_CIUDAD_PROG,
                SZVPRIC_CONS_MEN_PROG = :SZVPRIC_CONS_MEN_PROG,
                SZVPRIC_NRO_RESOL_PROG = :SZVPRIC_NRO_RESOL_PROG,
                SZVPRIC_NRO_CREDITOS = :SZVPRIC_NRO_CREDITOS,
                SZVPRIC_MOMA_CODE = :SZVPRIC_MOMA_CODE,
                SZVPRIC_IND_DE = :SZVPRIC_IND_DE,
                SZVPRIC_MODALIDAD = :SZVPRIC_MODALIDAD
              WHERE
                SZVPRIC_CODE = :SZVPRIC_CODE";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_CREA_DATE', $data['SZVPRIC_FECHA_CREA_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_REG_DATE', $data['SZVPRIC_FECHA_REG_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_CARGUE_SNIES', $data['SZVPRIC_FECHA_CARGUE_SNIES']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_VENC_REGISTRO', $data['SZVPRIC_FECHA_VENC_REGISTRO']);
    oci_bind_by_name($stid, ':SZVPRIC_ACTIVITY_DATE', $data['SZVPRIC_ACTIVITY_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_OFRE_IND', $data['SZVPRIC_OFRE_IND']);
    oci_bind_by_name($stid, ':SZVPRIC_ICFES_DESC', $data['SZVPRIC_ICFES_DESC']);
    oci_bind_by_name($stid, ':SZVPRIC_TPRO_CODE', $data['SZVPRIC_TPRO_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_IND_VIGENTE', $data['SZVPRIC_IND_VIGENTE']);
    oci_bind_by_name($stid, ':SZVPRIC_ACTA_CONSEJO', $data['SZVPRIC_ACTA_CONSEJO']);
    oci_bind_by_name($stid, ':SZVPRIC_COD_CIUDAD_PROG', $data['SZVPRIC_COD_CIUDAD_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_CONS_MEN_PROG', $data['SZVPRIC_CONS_MEN_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_NRO_RESOL_PROG', $data['SZVPRIC_NRO_RESOL_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_NRO_CREDITOS', $data['SZVPRIC_NRO_CREDITOS']);
    oci_bind_by_name($stid, ':SZVPRIC_MOMA_CODE', $data['SZVPRIC_MOMA_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_IND_DE', $data['SZVPRIC_IND_DE']);
    oci_bind_by_name($stid, ':SZVPRIC_CODE', $data['SZVPRIC_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_MODALIDAD', $data['SZVPRIC_MODALIDAD']);
    $result = oci_execute($stid);

    if ($result == FALSE) {
      $e = oci_error($stid);
      return $e['message'];
    }else{
      return TRUE;
    }

  }

  private function insertaPrograma($data){

    $conn = $this->conexion();
    $query = "INSERT INTO
                SZVPRIC (
                  SZVPRIC_CODE,
                  SZVPRIC_ICFES_DESC,
                  SZVPRIC_TPRO_CODE,
                  SZVPRIC_OFRE_IND,
                  SZVPRIC_FECHA_REG_DATE,
                  SZVPRIC_FECHA_CREA_DATE,
                  SZVPRIC_ACTIVITY_DATE,
                  SZVPRIC_IND_VIGENTE,
                  SZVPRIC_ACTA_CONSEJO,
                  SZVPRIC_COD_CIUDAD_PROG,
                  SZVPRIC_CONS_MEN_PROG,
                  SZVPRIC_NRO_RESOL_PROG,
                  SZVPRIC_FECHA_VENC_REGISTRO,
                  SZVPRIC_FECHA_CARGUE_SNIES,
                  SZVPRIC_NRO_CREDITOS,
                  SZVPRIC_MOMA_CODE,
                  SZVPRIC_IND_DE,
                  SZVPRIC_MODALIDAD
                ) VALUES (
                  :SZVPRIC_CODE,
                  :SZVPRIC_ICFES_DESC,
                  :SZVPRIC_TPRO_CODE,
                  :SZVPRIC_OFRE_IND,
                  TO_DATE(:SZVPRIC_FECHA_REG_DATE,'DD/MM/YYYY'),
                  TO_DATE(:SZVPRIC_FECHA_CREA_DATE,'DD/MM/YYYY'),
                  TO_DATE(:SZVPRIC_ACTIVITY_DATE,'DD/MM/YYYY'),
                  :SZVPRIC_IND_VIGENTE,
                  :SZVPRIC_ACTA_CONSEJO,
                  :SZVPRIC_COD_CIUDAD_PROG,
                  :SZVPRIC_CONS_MEN_PROG,
                  :SZVPRIC_NRO_RESOL_PROG,
                  TO_DATE(:SZVPRIC_FECHA_VENC_REGISTRO,'DD/MM/YYYY'),
                  TO_DATE(:SZVPRIC_FECHA_CARGUE_SNIES,'DD/MM/YYYY'),
                  :SZVPRIC_NRO_CREDITOS,
                  :SZVPRIC_MOMA_CODE,
                  :SZVPRIC_IND_DE,
                  :SZVPRIC_MODALIDAD
                )";

    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':SZVPRIC_CODE', $data['SZVPRIC_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_ICFES_DESC', $data['SZVPRIC_ICFES_DESC']);
    oci_bind_by_name($stid, ':SZVPRIC_TPRO_CODE', $data['SZVPRIC_TPRO_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_OFRE_IND', $data['SZVPRIC_OFRE_IND']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_REG_DATE', $data['SZVPRIC_FECHA_REG_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_CREA_DATE', $data['SZVPRIC_FECHA_CREA_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_ACTIVITY_DATE', $data['SZVPRIC_ACTIVITY_DATE']);
    oci_bind_by_name($stid, ':SZVPRIC_IND_VIGENTE', $data['SZVPRIC_IND_VIGENTE']);
    oci_bind_by_name($stid, ':SZVPRIC_ACTA_CONSEJO', $data['SZVPRIC_ACTA_CONSEJO']);
    oci_bind_by_name($stid, ':SZVPRIC_COD_CIUDAD_PROG', $data['SZVPRIC_COD_CIUDAD_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_CONS_MEN_PROG', $data['SZVPRIC_CONS_MEN_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_NRO_RESOL_PROG', $data['SZVPRIC_NRO_RESOL_PROG']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_VENC_REGISTRO', $data['SZVPRIC_FECHA_VENC_REGISTRO']);
    oci_bind_by_name($stid, ':SZVPRIC_FECHA_CARGUE_SNIES', $data['SZVPRIC_FECHA_CARGUE_SNIES']);
    oci_bind_by_name($stid, ':SZVPRIC_NRO_CREDITOS', $data['SZVPRIC_NRO_CREDITOS']);
    oci_bind_by_name($stid, ':SZVPRIC_MOMA_CODE', $data['SZVPRIC_MOMA_CODE']);
    oci_bind_by_name($stid, ':SZVPRIC_IND_DE', $data['SZVPRIC_IND_DE']);
    oci_bind_by_name($stid, ':SZVPRIC_MODALIDAD', $data['SZVPRIC_MODALIDAD']);
    $result = oci_execute($stid);

    if($result){
      return TRUE;
    }else{
      $e = oci_error($stid);
      return $e['message'];
    }

  }

  private function getDatosPrograma($node){

    $data = [
      "SZVPRIC_CODE" => "",
      "SZVPRIC_FECHA_CREA_DATE" => "",
      "SZVPRIC_FECHA_REG_DATE" => "",
      "SZVPRIC_FECHA_CARGUE_SNIES" => "",
      "SZVPRIC_FECHA_VENC_REGISTRO" => "",
      "SZVPRIC_ACTIVITY_DATE" => "",
      "SZVPRIC_OFRE_IND" => "",
      "SZVPRIC_IND_VIGENTE" => "",
      "SZVPRIC_IND_DE" => "",
      "SZVPRIC_MOMA_CODE" => "",
      "SZVPRIC_CONS_MEN_PROG" => "",
      "SZVPRIC_TPRO_CODE" => "",
      "SZVPRIC_ICFES_DESC" => "",
      "SZVPRIC_COD_CIUDAD_PROG" => "",
      "SZVPRIC_NRO_CREDITOS" => "",
      "SZVPRIC_ACTA_CONSEJO" => "",
      "SZVPRIC_NRO_RESOL_PROG" => "",
      "SZVPRIC_MODALIDAD" => ""
    ];

    $field_snies = $node->field_snies->getValue();
    $data['SZVPRIC_CONS_MEN_PROG'] = $field_snies[0]['value'];

    $field_nivel_programa = $node->field_nivel_programa->getValue();
    $nivelPrograma = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($field_nivel_programa[0]['target_id']);
    $field_banner = $nivelPrograma->field_banner->getValue();
    if (!empty($field_banner[0]['value'])) {
      $data['SZVPRIC_TPRO_CODE'] = strtoupper($field_banner[0]['value']);
    }

    $field_municipio_pa = $node->field_municipio_pa->getValue();
    $municipioTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($field_municipio_pa[0]['target_id']);
    $municipio = $municipioTerm->name->value;
    $data['SZVPRIC_ICFES_DESC'] = $node->getTitle()." - ".$municipio;

    $data['SZVPRIC_COD_CIUDAD_PROG'] = $this->getCodigoMunicipio($municipio);

    $field_num_cred_oblig = $node->field_num_cred_oblig->getValue();
    $field_num_cred_oblig = (isset($field_num_cred_oblig[0]) ? $field_num_cred_oblig[0]['value'] : 0);
    $field_num_cred_elec = $node->field_num_cred_elec->getValue();
    $field_num_cred_elec = (isset($field_num_cred_elec[0]) ? $field_num_cred_elec[0]['value'] : 0);
    $data['SZVPRIC_NRO_CREDITOS'] = intval($field_num_cred_oblig) + intval($field_num_cred_elec);

    $field_fecha_norma_reg_cal = $node->field_fecha_norma_reg_cal->getValue();
    $SZVPRIC_FECHA_CREA_DATE = \DateTime::createFromFormat('Y-m-d',$field_fecha_norma_reg_cal[0]['value']);
    $data['SZVPRIC_FECHA_CREA_DATE'] = $SZVPRIC_FECHA_CREA_DATE->format("d/m/Y");

    $field_fecha_resol_reg_calif = $node->field_fecha_resol_reg_calif->getValue();
    $SZVPRIC_FECHA_REG_DATE = \DateTime::createFromFormat('Y-m-d',$field_fecha_resol_reg_calif[0]['value']);
    $data['SZVPRIC_FECHA_REG_DATE'] = $SZVPRIC_FECHA_REG_DATE->format("d/m/Y");
    $data['SZVPRIC_FECHA_CARGUE_SNIES'] = $SZVPRIC_FECHA_REG_DATE->format("d/m/Y");

    $field_fecha_vencimiento_reg_cal = $node->field_fecha_vencimiento_reg_cal->getValue();
    $SZVPRIC_FECHA_VENC_REGISTRO = \DateTime::createFromFormat('Y-m-d',$field_fecha_vencimiento_reg_cal[0]['value']);
    $data['SZVPRIC_FECHA_VENC_REGISTRO'] = $SZVPRIC_FECHA_VENC_REGISTRO->format("d/m/Y");

    $changed = $node->changed->getValue();
    $data['SZVPRIC_ACTIVITY_DATE'] = date("d/m/Y", $changed[0]['value']);

    $field_extension_programa = $node->field_extension_programa->getValue();
    $data['SZVPRIC_OFRE_IND'] = ($field_extension_programa[0]['value'] == 'No') ? 1 : 2;

    $field_activo = $node->field_activo->getValue();
    $data['SZVPRIC_IND_VIGENTE'] = ($field_activo[0]['value'] == '1') ? 1 : Null;

    $field_pertenece_prog_especializa = $node->field_pertenece_prog_especializa->getValue();
    $data['SZVPRIC_IND_DE'] = ($field_pertenece_prog_especializa[0]['value'] == 'No') ? 'N' : 'Y';

    $field_numero_norma_registro_cal = $node->field_numero_norma_registro_cal->getValue();
    $data['SZVPRIC_ACTA_CONSEJO'] = $field_numero_norma_registro_cal[0]['value'];

    if (!$node->field_num_resol_reg_calificado->isEmpty()) {
      $field_num_resol_reg_calificado = $node->field_num_resol_reg_calificado->getValue();
      $data['SZVPRIC_NRO_RESOL_PROG'] = $field_num_resol_reg_calificado[0]['value'];
    }

    if (!$node->field_modalidad_szvpric->isEmpty()) {
      $field_modalidad_szvpric = $node->field_modalidad_szvpric->getValue();
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($field_modalidad_szvpric[0]['target_id']);
      $data['SZVPRIC_MODALIDAD'] = $term->name->value;
    }

    $field_modalidad = $node->field_modalidad->getValue();
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($field_modalidad[0]['target_id']);
    if (!empty($field_modalidad[0]['target_id'])) {
      $data['SZVPRIC_MOMA_CODE'] = '';
      switch ($term->name->value) {
        case 'Profundización':
          $data['SZVPRIC_MOMA_CODE'] = 'P';
          break;
        case 'Profundización e Investigación':
          $data['SZVPRIC_MOMA_CODE'] = 'A';
          break;
        case 'Investigación':
          $data['SZVPRIC_MOMA_CODE'] = 'I';
          break;
      }
    }

    $field_procodigo = $node->field_procodigo->getValue();
    $data['SZVPRIC_CODE'] = $field_procodigo[0]['value'];

    return $data;
  }

  private function validarDatosPrograma($node){

    $faltan = [];

    $field_procodigo = $node->field_procodigo->getValue();
    if(!isset($field_procodigo[0]['value'])){
      $faltan[]= 'PROCÓDIGO';
    }

    $field_snies = $node->field_snies->getValue();
    if(!isset($field_snies[0]['value'])){
      $faltan[]= 'SNIES';
    }

    $field_nivel_programa = $node->field_nivel_programa->getValue();
    if(!isset($field_nivel_programa[0]['target_id'])){
      $faltan[]= 'NIVEL DEL PROGRAMA';
    }

    $field_extension_programa = $node->field_extension_programa->getValue();
    if(!isset($field_extension_programa[0]['value'])){
      $faltan[]= 'EXTENSIÓN';
    }

    $field_fecha_resol_reg_calif = $node->field_fecha_resol_reg_calif->getValue();
    if(!isset($field_fecha_resol_reg_calif[0]['value'])){
      $faltan[]= 'FECHA DE LA RESOLUCIÓN DEL REGISTRO CALIFICADO';
    }

    $field_fecha_resol_reg_calif = $node->field_fecha_resol_reg_calif->getValue();
    if(!isset($field_fecha_resol_reg_calif[0]['value'])){
      $faltan[]= 'FECHA DEL ACTA DE CREACIÓN';
    }

    $field_activo = $node->field_activo->getValue();
    if(!isset($field_activo[0]['value'])){
      $faltan[]= 'ACTIVO';
    }

    $field_numero_norma_registro_cal = $node->field_numero_norma_registro_cal->getValue();
    if(!isset($field_numero_norma_registro_cal[0]['value'])){
      $faltan[]= 'NÚMERO DEL ACTA DE CREACIÓN';
    }

    $field_municipio_pa = $node->field_municipio_pa->getValue();
    if(!isset($field_municipio_pa[0]['target_id'])){
      $faltan[]= 'MUNICIPIO DE OFERTA DEL PROGRAMA';
    }

    $field_num_resol_reg_calificado = $node->field_num_resol_reg_calificado->getValue();
    if(!isset($field_num_resol_reg_calificado[0]['value'])){
      $faltan[]= 'NÚMERO DE LA RESOLUCIÓN DEL REGISTRO CALIFICADO';
    }

    $field_fecha_vencimiento_reg_cal = $node->field_fecha_vencimiento_reg_cal->getValue();
    if(!isset($field_fecha_vencimiento_reg_cal[0]['value'])){
      $faltan[]= 'FECHA DE VENCIMIENTO DEL REGISTRO CALIFICADO';
    }

    $field_num_cred_oblig = $node->field_num_cred_oblig->getValue();
    $field_num_cred_elec = $node->field_num_cred_elec->getValue();
    if(
      !isset($field_num_cred_oblig[0]['value']) &&
      !isset($field_num_cred_elec[0]['value'])
    ){
      $faltan[]= 'TOTAL DE CRÉDITOS';
    }

    /*
    $field_modalidad = $node->field_modalidad->getValue();
    if(!isset($field_modalidad[0]['target_id'])){
      $faltan[]= 'ESPECIALIDAD NIVEL DE FORMACIÓN MAESTRÍA';
    }
    */

    $field_pertenece_prog_especializa = $node->field_pertenece_prog_especializa->getValue();
    if(!isset($field_pertenece_prog_especializa[0]['value'])){
      $faltan[]= '¿PERTENECE EL PROGRAMA A LA DIRECCIÓN DE ESPECIALIZACIONES?';
    }

    $field_pertenece_prog_especializa = $node->field_pertenece_prog_especializa->getValue();
    if (!isset($field_pertenece_prog_especializa[0]['value'])) {
      $faltan[] = '¿PERTENECE EL PROGRAMA A LA DIRECCIÓN DE ESPECIALIZACIONES?';
    }

    $field_pertenece_prog_especializa = $node->field_pertenece_prog_especializa->getValue();
    if (!isset($field_pertenece_prog_especializa[0]['value'])) {
      $faltan[] = '¿PERTENECE EL PROGRAMA A LA DIRECCIÓN DE ESPECIALIZACIONES?';
    }

    $field_modalidad = $node->field_nivel_programa->getValue();
    if (!isset($field_nivel_programa[0]['target_id'])) {
      $faltan[] = 'NIVEL DEL PROGRAMA';
    }

    return $faltan;
  }

  private function getCodigoMunicipio($municipio) {

    $res = [];
    $conn = $this->conexion();
    $query = "SELECT GTVZIPC_CODE FROM GTVZIPC WHERE GTVZIPC_NATN_CODE = 'COL' AND UPPER(GTVZIPC_CITY) = :municipio";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':municipio', $municipio);
    oci_execute($stid);
    oci_fetch_all($stid, $res);

    foreach ($res as $row) {
      return $row[0];
    }

    return null;

  }

  private static function conexion(){

    try {
      if(!function_exists("oci_connect")) {
        throw new Exception('Function oci_connect dont exist.');
      }
      // DATOS DE CONEXION
      $db_oracle = Settings::get('banner', '1111');

      // ESTABLECE CONEXION
      $conn = oci_connect($db_oracle['db_username'], $db_oracle['db_password'], "{$db_oracle['server']}:{$db_oracle['port']}/{$db_oracle['service_name']}", 'AL32UTF8');
      if(!$conn) {
        $e = oci_error();
        throw new \Exception($e['message']);
      }
      return $conn;

    } catch (\Exception $e) {
        throw $e;
    }

  }

}

