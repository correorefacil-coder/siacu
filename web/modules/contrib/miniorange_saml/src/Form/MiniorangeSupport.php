<?php
/**
 * @file
 * Contains support form for miniOrange 2FA Login Module.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;
use Drupal\miniorange_saml\Utilities;

/**
 *  Showing Support form info.
 */
class MiniorangeSupport extends MiniorangeSAMLFormBase
{
    public function getFormId()
    {
        return 'miniorange_SAML_support';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $email  = $this->config->get('miniorange_saml_customer_admin_email');
        $phone  = $this->config->get('miniorange_saml_customer_admin_phone');

        $form['miniorange_saml_support'] = array(
        '#type'      => 'container',
        '#prefix'   => '<div id="modal_support_form">',
        '#suffix'   => '</div>',
        );
        $form['miniorange_saml_support']['mo_otp_verification_script'] = array(
        '#attached' => array('library' => array('core/drupal.dialog.ajax','miniorange_saml/miniorange_saml.admin')),
        );
        $form['miniorange_saml_support']['mo_otp_verification_status_messages'] = array(
        '#type'     => 'status_messages',
        '#weight'   => -10,
        );

        $form['miniorange_saml_support']['mo_saml_markup_1'] = array(
        '#markup' => t('<div class="mo_saml_highlight_background_note"><p>
                    We are here to help you with configuring this module on your site. Feel free to reach out with your query, and we\'ll be happy to assist you promptly.</p></div>'),
        );

        $form['miniorange_saml_support']['mo_saml_support_email_address'] = array(
        '#type'          => 'email',
        '#title'         => $this->t('Email'),
        '#default_value' => $email,
        '#required'      => true,
        '#attributes'    => array('placeholder' => t('Enter your email'), 'style' => 'width:99%;margin-bottom:1%;'),
        );

        $form['miniorange_saml_support']['mo_saml_support_query'] = array(
        '#type'          => 'textarea',
        '#title'         => $this->t('Query'),
        '#required'      => true,
        '#attributes'    => array('placeholder' => $this->t('Describe your query here!'), 'style' => 'width:99%'),
        '#suffix'        => '<br>',
        );

        $form['miniorange_saml_support']['actions'] = array(
        '#type' => 'actions',
        );

        $form['miniorange_saml_support']['actions']['submit'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Submit query'),
        '#attributes' => array('class' => array('use-ajax')),
        '#ajax' => array(
          'callback' => '::submitQuery',
          'progress'  => array(
            'type'    => 'throbber',
            'message' => $this->t('Sending Query...'),
          ),
        ),
        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function submitQuery(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        if ($form_state->hasAnyErrors()) {
            $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
        } else {
            $form_values = $form_state->getValues();
            $email = $form_values['mo_saml_support_email_address'];
            $phone = '';
            $query = $form_values['mo_saml_support_query'] . '<br><br>' . 'Timezone: ' . Utilities::getFormattedTimezone();
            $query_type = 'Support';

            $support_response = Utilities::send_support_query($email, $phone, $query, $query_type);
            $ajax_form = Utilities::getModalFormAfterQuery($support_response, $email, FALSE);
            $response->addCommand($ajax_form);
        }
        return $response;
    }
}
