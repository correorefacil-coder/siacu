<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\MiniorangeSamlCustomerSetup.
 */

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;
use Drupal\miniorange_saml\Utilities;
use Drupal\Core\Form\FormStateInterface;

class MiniorangeSamlCustomerSetup extends FormBase {

    public function getFormId() {
        return 'miniorange_saml_customer_setup';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $current_status = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_status');

        $form['#attached']['library'][] = 'miniorange_saml/miniorange_saml.admin';
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

        if (empty($current_status) || $current_status == 'CUSTOMER_SETUP') {
          $form['div_for_start'] = array(
            '#markup' => t('<div class="mo_saml_sp_table_layout_1"><div class="mo_saml_table_layout mo_saml_sp_container">'),
          );

          $form['markup_14'] = array(
            '#markup' => $this->t('<div class="mo_saml_font_for_heading">Register/Login</div><p style="clear: both"></p><hr>'),
          );

          $form['mo_saml_login_tab'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Please login with your miniOrange account.'),
            '#attributes' => array( 'style' => 'padding:2% 2% 5%; margin-bottom:2%' ),
          );

          $form['mo_saml_login_tab']['miniorange_saml_customer_login_username'] = array(
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
          );

          $form['mo_saml_login_tab']['miniorange_saml_customer_login_password'] = array(
            '#type' => 'password',
            '#title' => $this->t('Password'),
            '#required' => TRUE,
          );

          $form['mo_saml_login_tab']['miniorange_saml_customer_login_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Login'),
            '#button_type' => 'primary',
            '#prefix' => '<div class="container-inline">',
          );

          $form['mo_saml_login_tab']['register_link'] = array(
            '#type' => 'link',
            '#title' => $this->t('Create an account'),
            '#url' => Url::fromUri(MiniorangeSAMLConstants::CREATE_ACCOUNT_URL),
            '#attributes' => array('class' => 'button', 'target' => '_blank'),
            '#suffix' => '</div></div>'
          );
        }
        elseif ($current_status == 'PLUGIN_CONFIGURATION') {

            $form['markup_top'] = array(
                '#markup' => t('<div class="mo_saml_sp_table_layout_1"><div class="mo_saml_table_layout mo_saml_sp_container">
                                  <div class="mo_saml_welcome_message">Thank you for registering with miniOrange</div>')
            );

            /**
             * Create container to hold @moProfileDetails form elements.
             */
            $form['mo_saml_profile_details_tab'] = array(
                '#type' => 'fieldset',
                '#title' => $this->t('Profile Details:'),
                '#attributes' => array( 'style' => 'padding:2% 2% 6%; margin-bottom:2%' ),
            );

          $module_info = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_saml');

          $header = array( t('Attribute'),  t('Value'),);
            $options = array(
                array( 'Customer Email', \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_customer_admin_email')),
                array( 'Customer ID', \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_customer_id')),
                array( 'Module Version', $module_info['version']),
                array( 'Drupal Version', Utilities::mo_get_drupal_core_version()),
                array( 'PHP Version', phpversion()),
            );

            $form['mo_saml_profile_details_tab']['fieldset']['customerinfo'] = array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $options,
                '#prefix' => '<br>',
                '#attributes' => array( 'style' => 'margin:1% 0% 7%;' ),
            );

            $form['mo_saml_profile_details_tab']['miniorange_saml_customer_Remove_Account_info'] = array(
                '#markup' => t('<br/><h4>Remove Account:</h4><p>This section will help you to remove your current
                        logged in account without losing your current configurations.</p>')
            );

            $form['mo_saml_profile_details_tab']['miniorange_saml_customer_Remove_Account'] = array(
                '#type' => 'link',
                '#title' => $this->t('Remove Account'),
                '#url' => Url::fromRoute('miniorange_saml.modal_form'),
                '#attributes' => [
                    'class' => [
                        'use-ajax',
                        'button',
                    ],
                ],
                '#suffix' => '</div>'
            );

            $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

            Utilities::advertiseNetworkSecurity($form, $form_state);

            return $form;
        }

        Utilities::advertiseNetworkSecurity($form, $form_state);

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $form_values = $form_state->getValues();

        $username = trim($form_values['miniorange_saml_customer_login_username']);
        $password = trim($form_values['miniorange_saml_customer_login_password']);

        Utilities::customer_setup_submit($username, '', $password, true);
    }

}
