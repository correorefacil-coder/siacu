<?php
/**
 * @file
 * Contains Login Settings for miniOrange SAML Login Module.
 */

 /**
 * Showing Settings form.
 */
 namespace Drupal\miniorange_saml\Form;

 use Drupal\Core\Form\FormStateInterface;
 use Drupal\Core\Form\FormBase;
 use Drupal\miniorange_saml\Utilities;
 use Drupal\miniorange_saml\MiniorangeSAMLConstants;

 class MiniorangeSignonSettings extends FormBase {

  public function getFormId() {
    return 'miniorange_saml_login_setting';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

      $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_last_tab_visited', 'SIGNIN SETTINGS')->save();

      $form['miniorange_saml_markup_library'] = array(
        '#attached' => array(
          'library' => array(
            'miniorange_saml/miniorange_saml.admin',
            'core/drupal.dialog.ajax',
          )
        ),
      );

      $form['markup_start'] = array(
         '#markup' => t('<div class="mo_saml_sp_table_layout_1"><div id="signon_settings_tab" class="mo_saml_table_layout mo_saml_sp_container">')
      );

      /**
       * Create container to hold @SigninSettings form elements.
       */
      $form['mo_saml_singnin_settings'] = array(
          '#type' => 'fieldset',
          //'#title' => t('Signin Settings'),
          '#attributes' => array( 'style' => 'padding:2% 2% 5%; margin-bottom:2%' ),
          '#prefix'=>'<div></div><div class="mo_saml_font_for_heading_none_float">SIGNIN SETTINGS <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Standard, Premium and Enterprise]</a></div><hr>'
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_disable_autocreate_users'] = array(
          '#type' => 'checkbox',
          '#title' => t('Disable <b>auto creation</b> of users via SSO.'),
          '#disabled' => TRUE,
          '#description'=> t('This will block creation of new users upon SSO. Only existing Drupal users will be allowed to login via SSO.<br><br>'),
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_force_auth'] = array(
        '#type' => 'checkbox',
        '#title' => t('Protect website against anonymous access'),
        '#description' => t('Users will be redirected to your IdP for force-authentication in case user is not logged in and tries to access website.<br><br>'),
        '#disabled' => TRUE,
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_auto_redirect'] = array(
        '#type' => 'checkbox',
        '#title' => t('Auto redirect the user to IdP'),
        '#description' => t('Users will be redirected to your IdP for login when the login page is accessed.<br><br>'),
        '#disabled' => TRUE,
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_enable_backdoor'] = array(
        '#type' => 'checkbox',
        '#title' => t('Backdoor Login.</b>'),
        '#disabled' => TRUE,
        '#description' => t('Checking this option <b>creates a backdoor to login to your website using Drupal credentials</b>
              <br><b>Backdoor URL: <span style="color: #FF0000"><code>Backdoor URL is available in standard, premium & enterprise version of the module.</code></span></b><br><br>'),
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_default_relaystate'] = array(
        '#type' => 'textfield',
        '#title' => t('Default Redirect URL after login.'),
        '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important','placeholder' => t('Enter Default Redirect URL')),
        '#disabled' => TRUE,
      );

      $form["mo_saml_singnin_settings"]["miniorange_saml_default_redirect_url_after_logout"] = array(
        "#type" => "textfield",
        "#title" => t("Default Redirect URL after logout"),
        "#attributes" => array("style" => "width:80%", "placeholder" => "Enter Default Redirect URL after logout"),
        "#disabled" => true,
    );

      $form['mo_saml_singnin_settings']['mo_saml_domain_restriction'] = array(
          '#type' => 'fieldset',
          //'#title' => t('Domain Restriction'),
          '#attributes' => array( 'style' => 'padding:2% 2%; margin-bottom:2%' ),
      );

      $form['mo_saml_singnin_settings']['mo_saml_domain_restriction']['miniorange_saml_domain_restriction_checkbox'] = array(
        '#type' => 'checkbox',
        '#title' => t('Check this option if you want  <b>Domain Restriction <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Enterprise]</a></b>'),
        '#disabled' => TRUE,
      );
      $form['mo_saml_singnin_settings']['mo_saml_domain_restriction']['miniorange_saml_allow_or_block_domains']=array(
          '#type'=>'radios',
          '#maxlength' => 1,
          '#options' => array(  0 => t('I want to allow only some of the domains'),1 => t('I want to block some of the domains')),
          '#disabled' => TRUE,

      );
      $form['mo_saml_singnin_settings']['mo_saml_domain_restriction']['miniorange_saml_domains'] = array(
        '#type' => 'textfield',
        '#title' => t('Enter list of domains'),
        '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important',
          'placeholder' => t('Enter semicolon(;) separated domains (Eg. xxxx.com; xxxx.com)')),
        '#disabled' => TRUE,
        '#suffix' => '',
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_attribute_fieldset'] = array(
        '#type' => 'fieldset',
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_attribute_fieldset'] ['miniorange_saml_attribute_based_restriction'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Allow SSO login  based on attributes <b><a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Enterprise]</a></b>'),
        '#disabled' => true,
        '#default_value' => false,
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_attribute_fieldset']['miniorange_saml_attribute_based_rules'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Specify rules to login based on attributes.'),
        '#disabled' => true,
        '#description' => ' Enter one rule per line, in the format: <b>attribute name|attribute value|operator</b><br>
                              <b>Note: </b>The Attribute values are case sensitive and the possible operators are <b>starts_with</b>, <b>contains</b>, <b>equal_to</b>, <b>ends_with</b>',
      );

      $form['mo_saml_singnin_settings']['miniorange_saml_gateway_config_submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save Configuration'),
        '#disabled' => TRUE,
        '#suffix' => '</div>',
      );

      Utilities::advertiseNetworkSecurity($form, $form_state, 'SCIM');

  return $form;

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
 }
