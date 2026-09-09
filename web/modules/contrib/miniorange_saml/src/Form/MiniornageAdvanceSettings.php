<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\Export.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;

class MiniornageAdvanceSettings extends FormBase
{
  public function getFormId()
  {
    return 'miniorange_saml_export';
  }
  public function buildForm(array $form, FormStateInterface $form_state)
  {
      $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_last_tab_visited', 'ADVANCE SETTINGS')->save();

      $form['miniorange_saml_markup_library'] = array(
        '#attached' => array(
          'library' => array(
            'miniorange_saml/miniorange_saml.admin',
            'core/drupal.dialog.ajax',
          )
        ),
      );
      $form['markup_top_1'] = array (
          '#markup' => t('<div class="mo_saml_sp_table_layout_1"><div class="mo_saml_table_layout mo_saml_sp_container">&nbsp;&nbsp;&nbsp;
                    <div class="mo_saml_font_for_heading">Advance Settings</div><p style="clear: both"></p><hr/>')
      );


      /**
       * Create container to hold @moSAMLAdvanseSettings form elements.
       */
      $form['mo_saml_import_export_configurations'] = array(
          '#type' => 'details',
          '#title' => t('Import/Export configurations' ),
          '#open' => TRUE,
          '#attributes' => array( 'style' => 'padding:0% 2%; margin-bottom:2%' )
      );

      $form['mo_saml_import_export_configurations']['markup_prem_export_plan'] = array(
          '#markup' => t('<div class="mo_saml_font_for_sub_heading">Export Configuration</div><hr><br>'),
      );

      $login_URL = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_login_url');
      $ACS_URL = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_issuer');
      $disableButton = FALSE;
      if( $login_URL == NULL || $ACS_URL == NULL ){
          $disableButton = TRUE;
          $form['mo_saml_import_export_configurations']['markup_note'] = array(
              '#markup' => t('<div class="mo_saml_configure_message">Please <a href="' . $base_url . '/admin/config/people/miniorange_saml/sp_setup">configure module </a> first to download configuration file.</div>'),
          );
      }
      $form['mo_saml_import_export_configurations']['miniorange_saml_imo_option_exists_export'] = array(
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => t('Download Module Configuration'),
          '#submit' => array('::miniorange_import_export'),
          '#disabled' => $disableButton,
      );

      $form['mo_saml_import_export_configurations']['markup_prem_plan'] = array(
          '#markup' => t('<br><br><div class="mo_saml_font_for_sub_heading">Import Configuration <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Standard, Premium and Enterprise]</a></div><hr>'),
      );

      $form['mo_saml_import_export_configurations']['import_Config_file'] = array(
          '#type' => 'file',
          '#disabled' => TRUE,
          '#prefix' => '<div class="container-inline">',
      );
      $form['mo_saml_import_export_configurations']['miniorange_saml_idp_import'] = array(
          '#type' => 'submit',
          '#value' => t('Upload'),
          '#disabled' => TRUE,
          '#suffix' => '</div></div>'
      );

      Utilities::advertiseNetworkSecurity($form, $form_state, 'SCIM');

      return $form;
	}
	function miniorange_import_export() {
        $tab_class_name = array(
            'Service_Provider_Metadata' => 'mo_options_enum_identity_provider',
            'Identity_Provider_Setup' => 'mo_options_enum_service_provider',
        );

		$configuration_array = array();
		foreach($tab_class_name as $key => $value) {
			$configuration_array[$key] = self::mo_get_configuration_array($value);
		}

		$configuration_array["Version_dependencies"] = self::mo_get_version_informations();
		header("Content-Disposition: attachment; filename = miniorange_saml_config.json");
		echo(json_encode($configuration_array, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		exit;
	}

    function mo_get_configuration_array( $class_name ) {
        $class_object = Utilities::getVariableArray( $class_name );
        $mo_array = array();
        foreach( $class_object as $key => $value ) {
            $mo_option_exists = \Drupal::config('miniorange_saml.settings')->get($value);
            if( $mo_option_exists ) {
                $mo_array[$key] = $mo_option_exists;
            }
        }
        return $mo_array;
    }

    function mo_get_version_informations() {
        $array_version = array();
        $array_version["PHP_version"] = phpversion();
        $array_version["Drupal_version"] = Utilities::mo_get_drupal_core_version();
        $array_version["OPEN_SSL"] = self::mo_saml_is_openssl_installed();
        $array_version["CURL"] = self::mo_saml_is_curl_installed();
        $array_version["ICONV"] = self::mo_saml_is_iconv_installed();
        $array_version["DOM"] = self::mo_saml_is_dom_installed();
        return $array_version;
    }

	function mo_saml_is_openssl_installed() {
		if ( in_array( 'openssl', get_loaded_extensions() ) ) {
			return 1;
		}
		return 0;
	}
    function mo_saml_is_curl_installed() {
        if ( in_array( 'curl', get_loaded_extensions() ) ) {
            return 1;
        }
        return 0;
    }
    function mo_saml_is_iconv_installed() {
        if ( in_array( 'iconv', get_loaded_extensions() ) ) {
            return 1;
        }
        return 0;
    }
    function mo_saml_is_dom_installed() {
        if ( in_array( 'dom', get_loaded_extensions() ) ) {
            return 1;
        }
        return 0;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

    }
}
