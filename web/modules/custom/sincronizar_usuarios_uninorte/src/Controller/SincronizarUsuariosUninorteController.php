<?php

namespace Drupal\sincronizar_usuarios_uninorte\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\Core\Site\Settings;

/**
 * Returns responses for Sincronizar Usuarios Uninorte routes.
 */
class SincronizarUsuariosUninorteController extends ControllerBase {

  private $urlUsernames = 'https://tananeo.uninorte.edu.co/ActiveDirectoryWS/api/ad/users/gap';
  private $urlUserRoles = 'https://tananeo.uninorte.edu.co/ActiveDirectoryWS/api/ad/roles/GAP/';

  /**
   * Builds the response.
   */
  public function sync() {

    $usernames = $this->getUsernamesAPI();
    foreach ($usernames as $key => $username) {

      echo '<pre>';
      var_dump(('----------------------------------------------'));
      var_dump(('Process: ' . $username));
      $newRoles = $this->getUserRolesAPI($username);
      if ($newRoles !== FALSE) {

        $user = $this->getUserByMail($username . '@uninorte.edu.co');

        // CREA EL USUARIO SI NO EXISTE
        if ($user == FALSE){
          $user = User::create([
            'name' => $username . '@uninorte.edu.co',
            'mail' => $username . '@uninorte.edu.co',
            'pass' => \Drupal::service('password_generator')->generate(),
            'roles' => array('authenticated'),
            'status' => 1
          ]);
          $user->save();
          var_dump(('- Created: '. $username));
        }

        $oldRoles = Role::loadMultiple($user->getRoles());

        var_dump(('- Roles Actuales'));
        var_dump((array_keys($oldRoles)));

        var_dump(('- Roles Nuevos'));
        var_dump((($newRoles)));

        // LOOP ELIMINACION DE ROLES
        $oldRolesLabel = [];
        foreach ($oldRoles as $key => $oldRole) {
          if ($key != 'authenticated') {
            $oldRoleLabel = $oldRole->label();
            $oldRolesLabel[] = $oldRoleLabel;
            if (!in_array($oldRoleLabel,$newRoles)) {
              var_dump(('- No DEBE TENER el rol'));
              var_dump(($oldRoleLabel));
              $user->removeRole($key);
              $message = "Eliminado rol $key del usuario $username";
              \Drupal::logger('sincronizar_usuarios_uninorte')->debug($message);
            }
          }
        }

        // LOOP ADICION DE ROLES
        foreach ($newRoles as $key => $newRole) {
          if (!in_array($newRole,$oldRolesLabel)) {
            $newRoleEntity = $this->user_role_load_by_name($newRole);
            if (!empty($newRoleEntity)) {
              var_dump(('- No TIENE el rol'));
              var_dump(($newRole));
              $newRoleLabel = key($newRoleEntity);
              $user->addRole($newRoleLabel);
              $message = "Adicionado rol $newRole del usuario $username";
              \Drupal::logger('sincronizar_usuarios_uninorte')->debug($message);
            }
          }
        }
        $user->save();
      }
      echo '</pre>';
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Usuarios Sincronizados!'),
    ];

    return $build;
  }

  private function user_role_load_by_name(string $role_name) {
    $properties = [
      'label' => $role_name,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties($properties);
  }

  private function getUserByMail($mail = NULL)
  {
    if (empty($mail)) {
      return;
    }
    $out = user_load_by_mail($mail);
    return $out;
  }

  private function getUsernamesAPI(){

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $this->urlUsernames,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('sincronizar_usuarios_uninorte')->debug('getUsernamesAPI->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      return json_decode($response);
    } else {
      $error = curl_error($curl);
      \Drupal::logger('sincronizar_usuarios_uninorte')->error('getUsernamesAPI->error: ' . $error);
      return [];
    }
  }
  private function getUserRolesAPI(string $username)
  {

    $curl = curl_init();
    curl_setopt_array(
      $curl,
      array(
        CURLOPT_URL => $this->urlUserRoles.$username,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
      )
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    \Drupal::logger('sincronizar_usuarios_uninorte')->debug('getUserRolesAPI->httpcode: ' . $httpcode);
    if ($response !== FALSE) {
      return json_decode($response);
    } else {
      $error = curl_error($curl);
      \Drupal::logger('sincronizar_usuarios_uninorte')->error('getUserRolesAPI->error: ' . $error);
      return FALSE;
    }
  }

  /**
   * Builds the response.
   */
  public function sync_nombres()
  {

    $users = $this->getUninorteUsers();

    if (!empty($users)) {
      foreach ($users as $key => $user) {
        $email = $user->getEmail();
        $alias = str_replace("@uninorte.edu.co", "", $email);
        $userData = $this->getUserData($alias);
        $user->field_primer_nombre->setValue($userData['PRIMER_NOMBRE']);
        if (isset($userData['SEGUNDO_NOMBRE'])) {
          $user->field_segundo_nombre->setValue($userData['SEGUNDO_NOMBRE']);
        }
        $user->field_primer_apellido->setValue($userData['APELLIDO']);
        $user->field_segundo_apellido->setValue('');
        $user->save();
      }
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Nombres de Usuarios Sincronizados!'),
    ];

    return $build;
  }

  private function getUninorteUsers(){
    $query = \Drupal::entityQuery('user');
    $query->condition('mail', '%uninorte.edu.co%', 'LIKE');
    $ids = $query->execute();
    $users = User::loadMultiple($ids);
    return $users;
  }
  private function getUserData($alias)
  {
    $conn = $this->conexion();
    $query = "SELECT
                initcap(s.spriden_first_name) primer_nombre,
                initcap(s.spriden_mi) segundo_nombre,
                initcap(s.spriden_last_name) apellido
              FROM
                Saturn.Spriden s,
                General.Gobtpac g
              WHERE
                s.spriden_change_ind is null AND
                s.spriden_pidm=g.gobtpac_pidm AND
                g.Gobtpac_External_User = :ALIASUNINORTE";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':ALIASUNINORTE', $alias);
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
