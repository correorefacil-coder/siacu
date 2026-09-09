<?php
/**
 * @file
 * Contains Attribute and Role Mapping for miniOrange SAML Login Module.
 */

 /**
 * Showing Settings form.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;

class Mapping extends FormBase {

  public function getFormId() {
    return 'miniorange_saml_mapping';
  }

 public function buildForm(array $form, FormStateInterface $form_state) {

  $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_last_tab_visited', 'ATTRIBUTE/ROLE MAPPING')->save();

     $form['miniorange_saml_markup_library'] = array(
       '#attached' => array(
         'library' => array(
           'miniorange_saml/miniorange_saml.admin',
           'core/drupal.dialog.ajax',
         )
       ),
     );

     $form['markup_top'] = array(
         '#markup' => t('<div class="mo_saml_sp_table_layout_1"><div class="mo_saml_table_layout" >
                       <div class="mo_saml_font_for_heading" >Attribute/Role Mapping <a target="_blank" href="https://developers.miniorange.com/docs/drupal/saml-sso/saml-mapping">[Know More]</a></div>
                       <p style="clear: both"></p><hr>')
     );

     /**
      * Create container to hold @RoleMapping form elements.
      */
     $form['mo_saml_role_mapping'] = array(
         '#type' => 'details',
         '#title' => $this->t('Role Mapping'),
         '#open' => TRUE,
     );

     $form['mo_saml_role_mapping']['miniorange_saml_enable_rolemapping'] = array(
         '#type' => 'checkbox',
         '#title' => t('Enable Role Mapping'),
         '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_rolemapping'),
         '#attributes' => array('class="mo_saml_checkbox"'),
         '#prefix' => t('<div id="role-mapping-checkbox">'),
         '#suffix' => t('</div>')
     );

     $mrole = user_role_names(TRUE);
     $default_role_index = \Drupal::configFactory()->getEditable('miniorange_saml.settings')->get('miniorange_saml_default_role_index');

     $form['mo_saml_role_mapping']['miniorange_saml_default_mapping'] = array(
         '#type' => 'select',
         '#title' => t('Select default role for the new users'),
         '#options' => array_values($mrole),
         '#description'=>t('This role will be assigned to the new user when the user is created in Drupal after SSO.'),
         '#default_value' => $default_role_index,
         '#states' => array('enabled' => array(':input[name = "miniorange_saml_enable_rolemapping"]' => array( 'checked' => TRUE ), ), ),
         '#prefix' => '<div id="Default_Mapping">',
         '#suffix' => '</div>',
     );

     $form['mo_saml_role_mapping']['miniorange_saml_disable_role_update'] = array(
         '#type' => 'checkbox',
         '#title' => t('Check this option if you do not want to update user role if roles not mapped <b><a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Premium and Enterprise]</a></b>'),
         '#disabled' => TRUE,
         //'#attributes' => array('background-color: hsla(0,0%,0%,0.08) !important;'),
     );

     $form['mo_saml_role_mapping']['miniorange_saml_gateway_config2_submit'] = array(
         '#type' => 'submit',
         '#button_type' => 'primary',
         '#value' => t('Save Configuration'),
         '#suffix' => '<br><br>',
     );


     /**
      * Create container to hold @moCustomRoleMapping form elements.
      */
     $form['mo_saml_custom_role_mapping'] = array(
         '#type' => 'details',
         '#title' => $this->t('Custom Role Mapping <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Premium and Enterprise]</a>'),
         '#open' => TRUE,
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_idp_attr1_name'] = array(
         '#type' => 'textfield',
         '#title' => t('Role Key'),
         '#attributes' => array('style' => 'width:50%;','placeholder' => 'Enter Role Attribute'),
         '#description' => t('<b>Note:</b> You will find role key value in the test configuration window.<br><br><br>'),
         '#disabled' => TRUE,
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_custom_role_buttons']=array(
         '#markup'=> t('<b>Role Attributes Mapping Table</b>'),
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_custom_role_table'] = array(
         '#type' => 'table',
         '#header' => [
           ['data' => $this->t('SP Role'), 'width' => '40%'],
           ['data' => $this->t('IDP Role'), 'width' => '40%'],
           ['data' => $this->t('Operation'), 'width' => '20%'],
         ],
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_custom_role_table'][0]['sp_role'] = array(
         '#type' => 'select',
         '#options' => $mrole,
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_custom_role_table'][0]['idp_role'] = array(
         '#type' => 'textfield',
         '#size' => 15,
         '#disabled' => true,
         '#attributes' => array(
           'placeholder' => $this->t('semi-colon(;) separated')
         )
     );

     $form['mo_saml_custom_role_mapping']['miniorange_saml_custom_role_table'][0]['delete_button'] = array(
         '#markup' => '<span class="button">Remove</span>',
     );

   $form['mo_saml_custom_role_mapping']['role_mapping_add_button'] = [
     '#prefix' => '<div class="container-inline">',
     '#type' => 'submit',
     '#value' => 'Add',
     '#disabled' => TRUE,
   ];

   $form['mo_saml_custom_role_mapping']['role_mapping_total_items'] = [
     '#type' => 'number',
     '#default_value' => 1,
     '#min' => 1,
     '#max' => 50,
     '#suffix' => '&nbsp;&nbsp;more rows</div>',
   ];

     $form['mo_saml_custom_role_mapping']['miniorange_saml_gateway_config4_submit'] = array(
         '#type' => 'submit',
         '#value' => t('Save Configuration'),
         '#disabled' => TRUE,
         '#prefix' => '<br>',
         '#suffix' => '<br><br>',
     );


     /**
      * Create container to hold @AttributeMapping form elements.
      */
   $form['mo_saml_attribute_mapping'] = [
     '#type' => 'details',
     '#title' => $this->t('Attribute Mapping <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL . '">[Standard, Premium and Enterprise]</a>'),
     '#prefix' => '<div id="basing-mapping-details">',
     '#suffix' => '</div>',
     '#open' => TRUE,
   ];

   $form['mo_saml_attribute_mapping']['miniorange_saml_username_attribute'] = [
     '#type' => 'textfield',
     '#title' => t('Username Attribute'),
     '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_username_attribute'),
     '#attributes' => ['style' => 'width:50%;'],
     '#disabled' => TRUE,
   ];

   $form['mo_saml_attribute_mapping']['miniorange_saml_email_attribute'] = [
     '#type' => 'textfield',
     '#title' => t('Email Attribute'),
     '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_email_attribute'),
     '#attributes' => ['style' => 'width:50%;'],
     '#disabled' => TRUE,
   ];

   $form['mo_saml_attribute_mapping']['miniorange_saml_gateway_config1_submit'] = [
     '#type' => 'submit',
     '#value' => t('Save Configuration'),
     '#prefix' => '<br>',
     '#suffix' => '<br><br>',
     '#disabled' => TRUE,
   ];

   /* Create container to hold @moCustomAttributeMapping form elements. */
   $form['mo_saml_custom_attribute_mapping'] = [
     '#type' => 'details',
     '#title' => t('Custom Attribute Mapping <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL . '">[Premium and Enterprise]</a>'),
     '#prefix' => '<div id="custom-attribute-mapping-details">',
     '#suffix' => '</div>',
     '#open' => TRUE,
   ];

   $form['mo_saml_custom_attribute_mapping']['markup_cam'] = [
     '#markup' => '<br><br>
				<div class="mo_saml_highlight_background_note_1">'.$this->t('In this section you can map any attribute of the IDP to the Drupal user profile field.') .
       '<br><br>' . $this->t('To add a new Drupal field go to Configuration->Account Settings -> ') . '<a target="_blank" href = "'. $base_url . '/admin/config/people/accounts/fields">'. $this->t('Manage fields'). '</a> and then click on Add field.</p>'
       .'<b>SP Attribute Machine Name:</b> Machine name of the custom Drupal user profile attribute.
				<br> <b>IdP Attribute Name:</b> Name which you want to get from your IDP. It should be unique.<br>'.
       '<b>Separator (Optional): </b>'
       . $this->t('If the value received from IdP is an array or then put , as separator</p></div>'),
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_custom_attr_table'] = [
     '#type' => 'table',
     '#header' => [
       ['data' => $this->t('Drupal Field Machine Name'), 'width' => '35%' ],
       ['data' => $this->t('IDP Attribute Name'), 'width' => '35%'],
       ['data' => $this->t('Separator'), 'width' => '20%'],
       ['data' => $this->t('Operation'), 'width' => '10%'],
     ],
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_custom_attr_table'][0]['drupal_attribute'] = [
     '#type' => 'select',
     '#options' => Utilities::customUserFields(),
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_custom_attr_table'][0]['idp_attribute'] = [
     '#type' => 'textfield',
     '#disabled' => TRUE,
     '#attributes' => [
       'placeholder' => $this->t('Enter IdP Attribute Name'),
     ],
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_custom_attr_table'][0]['separator'] = [
     '#type' => 'textfield',
     '#disabled' => TRUE,
     '#attributes' => [
       'placeholder' => $this->t('Separator'),
     ],
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_custom_attr_table'][0]['delete_custom_attr'] = [
     '#markup' => '<span class="button">Remove</span>',
   ];

   $form['mo_saml_custom_attribute_mapping']['custom_mapping_add_button'] = [
     '#prefix' => '<div class="container-inline">',
     '#type' => 'submit',
     '#value' => 'Add',
     '#disabled' => TRUE,
   ];

   $form['mo_saml_custom_attribute_mapping']['custom_mapping_total_items'] = [
     '#type' => 'number',
     '#default_value' => 1,
     '#min' => 1,
     '#max' => 50,
     '#suffix' => '&nbsp;&nbsp;more rows</div>',
   ];

   $form['mo_saml_custom_attribute_mapping']['miniorange_saml_gateway_config3_submit'] = [
     '#type' => 'submit',
     '#value' => t('Save Configuration'),
     '#disabled' => TRUE,
     '#prefix' => '<br>',
   ];

   $form['mo_saml_profile_module_mapping'] = [
     '#type' => 'details',
     '#title' => $this->t('Profile Module Mapping <a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL . '">[Enterprise]</a>'),
     '#prefix' => '<div id="profile-mapping-details">',
     '#suffix' => '</div>',
     '#open' => TRUE,
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_enable_profile_mapping'] = [
     '#type' => 'checkbox',
     '#title' => $this->t('Check this option if you want to enable Profile Mapping.'),
     '#default_value' => FALSE,
     '#disabled' => TRUE,
     '#suffix' => '<div class="mo_saml_align_inline">',
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_entity_type'] = [
     '#type' => 'select',
     '#title' => $this->t('Profile Type'),
     '#options' => ['Select Profile Type'],
     '#disabled' => TRUE,
     '#prefix' => '<div class="container-inline">',
     '#suffix' => '&nbsp;&nbsp;',
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_save_profile_entity_type'] = [
     '#type' => 'submit',
     '#button_type' => 'primary',
     '#value' => $this->t('Save'),
     '#disabled' => TRUE,
     '#suffix' => '</div>',
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_add_button'] = [
     '#markup' => t('<br><b>Role Attributes Mapping Table</b>'),
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_table'] = [
     '#type' => 'table',
     '#header' => [
       ['data' => $this->t('SP Profile field'), 'width' => '40%'],
       ['data' => $this->t('IdP Attribute Name'), 'width' => '40%'],
       ['data' => $this->t('Operation'), 'width' => '20%'],
     ],
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_table'][0]['sp_field'] = [
     '#type' => 'select',
     '#options' => [$this->t('Select Profile Field')],
     '#disabled' => TRUE,
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_table'][0]['idp_attribute'] = [
     '#type' => 'textfield',
     '#disabled' => TRUE,
     '#size' => 15,
     '#attributes' => ['placeholder' => $this->t('Enter IDP Attribute name')],
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_table'][0]['delete_button_profile'] = [
     '#markup' => '<span class="button">Remove</span>',
   ];

   $form['mo_saml_profile_module_mapping']['profile_mapping_add_button'] = [
     '#prefix' => '<div class="container-inline">',
     '#type' => 'submit',
     '#value' => 'Add',
     '#disabled' => TRUE,
   ];

   $form['mo_saml_profile_module_mapping']['profile_mapping_total_items'] = [
     '#type' => 'number',
     '#default_value' => 1,
     '#min' => 1,
     '#max' => 50,
     '#suffix' => '&nbsp;&nbsp;more rows</div>',
   ];

   $form['mo_saml_profile_module_mapping']['miniorange_saml_profile_mapping_submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Save Configuration'),
     '#disabled' => TRUE,
     '#prefix' => '<br>',
     '#suffix' => '</div></div>',
   ];

     return $form;
 }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $form_values = $form_state->getValues();
        $username_attribute = $form_values['miniorange_saml_username_attribute'];
        $email_attribute    = $form_values['miniorange_saml_email_attribute'];
        $enable_rolemapping = $form_values['miniorange_saml_enable_rolemapping'];
        $default_mapping    = $form_values['miniorange_saml_default_mapping'];

        $i = 0;
        $mrole = user_role_names(TRUE );
        foreach( $mrole as $key => $value ) {
            $def_role[$i] = $value;
            $i++;
        }

        $enable_rolemapping_value = $enable_rolemapping == 1 ? TRUE : FALSE;

        if( $enable_rolemapping_value ) {
            \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_default_role', $def_role[$default_mapping]);
            \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_default_role_index', $default_mapping);
        }else {
            \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_default_role', $mrole['authenticated'])->save();
        }

        \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_username_attribute', $username_attribute)->save();
        \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_email_attribute', $email_attribute)->save();
        \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_enable_rolemapping', $enable_rolemapping_value)->save();
        \Drupal::messenger()->addMessage(t('Mapping Settings successfully saved'), 'status');
    }
  }
