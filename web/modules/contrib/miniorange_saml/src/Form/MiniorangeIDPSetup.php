<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\MiniorangeIDPSetup.
 */

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;

class MiniorangeIDPSetup extends FormBase {

    public function getFormId() {
        return 'miniorange_saml_idp_setup';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_saml');

        \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_last_tab_visited', 'SERVICE PROVIDER METADATA')->save();

        $base_url = Utilities::getBaseUrl();
        $acs_url = $base_url . '/samlassertion';

        $form['miniorange_saml_copy_button'] = array(
            '#attached' => array(
                'library' => array(
                    'miniorange_saml/miniorange_saml_copy.icon',
                    'miniorange_saml/miniorange_saml.admin',
                    'core/drupal.dialog.ajax',
                )
            ),
            '#prefix' => '<div class="mo_saml_sp_table_layout_1"><div class="mo_saml_table_layout mo_saml_sp_container_full">',
        );

        /**
         * Create container to hold @ServiceProviderMetadata form elements.
         */



        $form['mo_saml_metadata_option'] = array(
            '#markup' => t('<div class="mo_saml_font_for_heading">Service Provider Metadata</div>&nbsp;&nbsp;
                            <a class="button button--small" target="_blank" href="https://faq.miniorange.com/kb/drupal/saml-drupal/">FAQs</a>
                            <a class="button button--small" target="_blank" href="https://www.drupal.org/docs/contributed-modules/saml-sp-single-sign-on-sso-saml-service-provider-by-miniorange">Setup Guides</a><p style="clear: both"></p><hr>'),
            );


        $form['markup_idp_sp_2'] = array(
            '#markup' => t('<br><div class="mo_saml_font_SP_setup_for_heading"><strong>Provide following Service Provider\'s metadata to the Identity Provider.</strong></div>
                          <br><div>
                          <div class="container-inline"><b>a) Metadata URL:</b>
                            <div id="idp_metadata_url">
                               <code><b>
                                    <span>
                                        <a target="_blank" href="' . $base_url . '/saml_metadata">' . $base_url . '/saml_metadata' . '</a>&nbsp;
                                    </span></b>
                                </code></div>
                              <span class ="mo_copy button button--small">&#128461; Copy</span><span><a class="button button--small button--primary" href="' . $base_url . '/saml_metadata?download=true">Download XML Metadata</a></span></div>
                        </div>'),
        );

        $form['mo_table_info'] =array(
          '#markup' => '<div><br><b>b) Metadata details: </b></div>',
        );

        $copy_image = '<span class ="fa-pull-right mo_copy mo_copy button button--small">&#128461; Copy</span>';

        $SP_Entity = [
            'data' => Markup::create('<span id="issuer_id">' . Utilities::getIssuer() . '</span>'. $copy_image )
        ];
        $SP_ACS = [
            'data' => Markup::create('<span id="login_url">' . $acs_url . '</span>'. $copy_image )
        ];
        $Audience = [
            'data' => Markup::create('<span id="audience_url">' . Utilities::getIssuer() . '</span>'. $copy_image )
        ];
        $X_509_certificate = [
            'data' => Markup::create('Available in <b><a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Standard, Premium and Enterprise]</a></b> version.' )
        ];
        $Custom_X_509_certificate = [
            'data' => Markup::create('Click <a class="use-ajax"  data-dialog-type = "modal"  data-ajax-progress="fullscreen" data-dialog-options="{&quot;width&quot;:&quot;80%&quot;}"  href="'.$base_url.'/admin/config/people/miniorange_saml/generate_certificate">here</a> to generate custom certificate' )
        ];
        $Recipient = [
            'data' => Markup::create('<span id="recipientURL">' . $acs_url . '</span>'. $copy_image )
        ];
        $Destination = [
            'data' => Markup::create('<span id="destinationURL">' . $acs_url . '</span>'. $copy_image )
        ];
        $SingleLogoutURL = [
            'data' => Markup::create('Available in <b><a href="' . $base_url . MiniorangeSAMLConstants::LICENSING_TAB_URL .'">[Premium and Enterprise]</a></b> version.' )
        ];
        $NameIDFormat = [
            'data' => Markup::create('<span id="nameID">' . "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified" .'</span>'. $copy_image )
        ];

        $mo_table_content = array (
            array( 'SP Entity ID/Issuer',      $SP_Entity ),
            array( 'SP ACS URL',               $SP_ACS ),
            array( 'Audience URI',             $Audience ),
            array( 'X.509 Certificate',        $X_509_certificate ),
            array( 'Custom X.509 Certificate', $Custom_X_509_certificate),
            array( 'Recipient URL',            $Recipient ),
            array( 'Destination URL',          $Destination ),
            array( 'Single Logout URL',        $SingleLogoutURL ),
            array( 'NameID Format',            $NameIDFormat),
        );

        $form['mo_saml_attrs_list_idp'] = array(
            '#type' => 'table',
            '#header'=> array( 'ATTRIBUTE', 'VALUE' ),
            '#rows' => $mo_table_content,
            '#id' => 'metadata-table',
            '#empty' => t('Something went wrong. Please run the update script or contact us at <a href="'.MiniorangeSAMLConstants::SUPPORT_EMAIL.'">'.MiniorangeSAMLConstants::SUPPORT_EMAIL.'</a>'),
            '#responsive' => TRUE ,
            '#sticky'=> TRUE,
            '#size'=> 2,
        );

        $form['miniorange_saml_update_url_note'] = array(
            '#markup'=>t('<br><div class="mo_saml_highlight_background_note_1"><strong>Note: </strong>Updating the following fields will automatically update the SP metadata. Please ensure the IdP is configured with the <strong>latest metadata</strong> for seamless integration.</div>')
        );

        $form['miniorange_saml_base_url'] = array(
            '#type' => 'textfield',
            '#title' => t('SP Base URL:'),
            '#default_value' => Utilities::getBaseUrl(),
            '#attributes' => array('style' => 'width:70%'),
        );

        $form['miniorange_saml_entity_id'] = array(
            '#type' => 'textfield',
            '#title' => t('SP Entity ID/Issuer:'),
            '#default_value' => Utilities::getIssuer(),
            '#attributes' => array('style' => 'width:70%'),
        );

        $form['miniorange_saml_idp_config_submit'] = array(
            '#type' => 'submit',
            '#value' => t('Update'),
            '#button_type' => 'primary',
            '#suffix' => '</div>',
        );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      $form_values = $form_state->getValues();
      $b_url       = $form_values['miniorange_saml_base_url'];
      $issuer_id   = $form_values['miniorange_saml_entity_id'];
      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_base_url', $b_url)->save();
      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_entity_id', $issuer_id)->save();
      \Drupal::messenger()->addStatus(t('Base URL and/or Issuer updated successfully.'));
    }
}
