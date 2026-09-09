<?php

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;

class MiniorangeGenerateCertificate extends FormBase{

  public function getFormId() {
    return 'miniorange_saml_generate_certificate';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    \Drupal::messenger()->addWarning(t('This feature is available in the <a target="_blank" href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">Enterprise</a> version'));

    $form['miniorange_saml_status_messages'] = array(
      '#type'     => 'status_messages',
      '#weight'   => -10,
    );

    $form['mo_saml_add_custom_certificate'] = array(
      '#type' => 'details',
      '#title' => t('Add Custom Certificate'),
      '#open' => TRUE,
    );

    $form['mo_saml_add_custom_certificate']['miniorange_saml_private_certificate'] = array(
      '#type' => 'textarea',
      '#title' => t('X.509 Private Certificate'),
      '#attributes' => array(
        'style' => 'width:60%; background-color: hsla(0,0%,0%,0.08) !important;',
        'placeholder' => t('Copy and Paste the content from the downloaded certificate or copy the content enclosed in X509Certificate tag (has parent tag KeyDescriptor use=signing) in IdP-Metadata XML file.')
      ),
      '#disabled' => TRUE,
      '#description'=> t('<strong>Note :</strong> Format of the Private key:<br /><strong>-----BEGIN PRIVATE KEY-----<br />
                  XXXXXXXXXXXXXXXXXXXXXXXXXXX<br />-----END PRIVATE KEY-----</strong><br /><br />'),
    );

    $form['mo_saml_add_custom_certificate']['miniorange_saml_publ_certificate'] = array(
      '#type' => 'textarea',
      '#title' => t('X.509 Public Certificate '),
      '#attributes' => array(
        'style' => 'width:60%; background-color: hsla(0,0%,0%,0.08) !important;',
        'placeholder' => t('Copy and Paste the content from the downloaded certificate or copy the content enclosed in X509Certificate tag (has parent tag KeyDescriptor use=signing) in IdP-Metadata XML file.')
      ),
      '#disabled' => TRUE,
      '#description'=> t('<strong>Note :</strong> Format of the certificate:<br><strong>-----BEGIN CERTIFICATE-----<br />
                  XXXXXXXXXXXXXXXXXXXXXXXXXXX<br />-----END CERTIFICATE-----</strong><br><br>'),
    );

    $form['mo_saml_add_custom_certificate']['save_config_elements'] = array(
      '#type' => 'submit',
      '#name'=>'submit',
      '#button_type' => 'primary',
      '#value' => t('Upload'),
      '#submit' => array('::submitForm'),
      '#disabled' => TRUE,
    );

    $form['mo_saml_add_custom_certificate']['save_config_elements1'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate'] = array(
      '#type' => 'details',
      '#title' =>t('Generate Custom Certificate'),
      '#open' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_country_code_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Country Code:'),
      '#description' => t('<b>Note: </b>Check your country code <a href="https://www.digicert.com/kb/ssl-certificate-country-codes.htm">here</a>.'),
      '#attributes' => array(
        'placeholder' => t('Enter Country code')
      ),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_certificate_state_name'] = array(
      '#type' => 'textfield',
      '#title' => t('State:'),
      '#attributes' => array('placeholder' => t('State Name')),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_certificate_company_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Company:'),
      '#attributes' => array('placeholder' => t('Company Name')),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['miniorange_saml_unit_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Unit:',
      '#attributes' => array('placeholder' => t('Unit name')),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_certificate_common_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Common:'),
      '#attributes' => array('placeholder' => t('Common Name')),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_select_digest_algo'] = array(
      '#type' => 'select',
      '#title' => t('Digest Algorithm:'),
      '#options' => array(
        'sha512' => t('SHA512'),
        'sha384' => t('SHA384'),
        'sha256' => t('SHA256'),
        'sha1' => t('SHA1'),
      ),
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_select_private_key_bit'] = array(
      '#type' => 'select',
      '#title' => t('Bits to generate the private key:'),
      '#options' => array(
        '2048' => t('2048 bits'),
        '1024' => t('1024 bits'),
      ),
    );

    $form['mo_saml_generate_custom_certificate']['mo_saml_select_valid_days'] = array(
      '#type' => 'select',
      '#title' => t('Valid Days:'),
      '#options' => array(
        '365' => t('365 days'),
        '180' => t('180 days'),
        '90' => t('90 days'),
        '45' => t('45 days'),
        '30' => t('30 days'),
        '15' => t('15 days'),
        '7' => t('7 days'),
      ),
    );

    $form['mo_saml_generate_custom_certificate']['generate_config_elements'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Generate Self-Signed Certs'),
      '#disabled' => TRUE,
    );

    $form['mo_saml_generate_custom_certificate']['clear_genrate_certificate_data'] = array(
      '#type' => 'submit',
      '#value' => t('Clear Data'),
      '#disabled' => TRUE,
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
