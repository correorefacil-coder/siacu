<?php

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml\Api\MoAuthApi;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;
use Drupal\miniorange_saml\Utilities;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;

class MiniorangeTrial extends MiniorangeSAMLFormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'miniorange_saml_trial';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $customer_template = [
      '#theme' => 'mo-saml-send-ticket-info',
      '#ticket_number' => 'DR-SP-93404549',
    ];
    $content = \Drupal::service('renderer')->renderPlain($customer_template);

    $user  = User::load(\Drupal::currentUser()->id())->getEmail();
    $email = $this->config->get('miniorange_saml_customer_admin_email');
    $email = !empty($email) ? $email : $user;
    $email = preg_match('/^(?!.*(?:noreply|no-reply)).*$/i', $email) ? $email : '';

    $form['miniorange_saml_demo'] = [
      '#type' => 'container',
      '#prefix' => '<div id="modal_support_form">',
      '#suffix' => '</div>',
    ];

    $form['miniorange_saml_demo']['mo_saml_script'] = [
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
          'miniorange_saml/miniorange_saml.admin',
        ],
      ],
    ];

    $form['miniorange_saml_demo']['mo_saml_status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['miniorange_saml_demo']['mo_saml_demo_email_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $email,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => t('Enter your email'),
      ],
    ];

    $form['miniorange_saml_demo']['mo_saml_demo_description'] = [
      '#type' => 'textarea',
      '#title' => t('Specify your use-case'),
      '#attributes' => ['placeholder' => t('Tell us about your requirement!')],
      '#rows' => 2,
      '#resizable' => 'both',
    ];

    $form['miniorange_saml_demo']['mo_saml_select_feature'] = [
      '#type' => 'item',
      '#markup' => Markup::create('<b>' . $this->t('Select the features you are interested in') . '</b><span style="color:#dc2323">*</span>'),
    ];

    $form['miniorange_saml_demo']['mo_saml_select_all_feature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all features'),
      '#attributes' => ['name' => 'mo_saml_select_all_feature'],
    ];

    $form['miniorange_saml_demo']['mo_saml_feature_list_table'] = [
      '#type' => 'table',
    ];

    $features = MiniorangeSAMLConstants::MODULE_FEATURES;
    foreach ($features as $feature) {
      $list[] = $feature;
    }
    $features = array_chunk($list, 3);
    $counter = 0;
    foreach ($features as $chunk) {
      $row = [];
      foreach ($chunk as $key => $value) {
        $row[$value] = [
          '#type' => 'checkbox',
          '#title' => $value,
          '#default_value' => 0,
          '#states' => [
            'checked' => [
              ':input[name="mo_saml_select_all_feature"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
      $form['miniorange_saml_demo']['mo_saml_feature_list_table'][$counter++] = $row;
    }

    $form['miniorange_saml_demo']['mo_saml_feature_not_listed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('The feature I am looking for is not listed.'),
      '#attributes' => ['name' => 'mo_saml_feature_not_listed'],
    ];

    $form['miniorange_saml_demo']['mo_saml_other_feature'] = [
      '#title' => 'Custom feature',
      '#title_display' => 'attribute',
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => t('Enter your custom feature')],
      '#states' => [
        'visible' => [
          ':input[name="mo_saml_feature_not_listed"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['miniorange_saml_demo']['actions'] = [
      '#type' => 'actions',
    ];

    $form['miniorange_saml_demo']['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Send Request'),
      '#attributes' => ['class' => ['use-ajax']],
      '#ajax' => [
        'callback' => '::submitTrialRequest',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Sending Request...'),
        ],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $interested_features = $form_state->getValue('mo_saml_feature_list_table');
    $feature_not_listed = $form_state->getValue('mo_saml_feature_not_listed');
    $custom_feature = $form_state->getValue('mo_saml_other_feature');

    $flag = FALSE;
    foreach ($interested_features as $list) {
      if (in_array(1, $list)) {
        $flag = TRUE;
      }
    }

    if (!$feature_not_listed && !$flag) {
      $form_state->setErrorByName('mo_saml_feature_list_table', t('Please select at least one feature'));
    }

    if ($feature_not_listed && empty($custom_feature)) {
      $form_state->setErrorByName('mo_saml_other_feature', t('Please specify the feature.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function submitTrialRequest(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
    }
    else {
      $form_values = $form_state->getValues();
      $email       = $form_values['mo_saml_demo_email_address'];
      $query_type  = 'Trial Request';
//      $ticket      = $this->generateTicket();

      $trial_details = [
        'User Email' => $email,
//        'Ticket Number' => $ticket,
        'Use-case/Requirements' => $form_values['mo_saml_demo_description'],
        'Interested Features' => $this->getFeaturesArray($form_values),
        'Timezone' => Utilities::getFormattedTimezone(),
      ];

      $query = $this->getQueryContent($trial_details);
      $support_response = Utilities::send_support_query($email, '', $query, $query_type);
//      $this->sendEmail($email, $ticket);
      $ajax_form = Utilities::getModalFormAfterQuery($support_response, $email, TRUE);
      $response->addCommand($ajax_form);
    }
    return $response;
  }

  protected function getFeaturesArray($form_values) {
    $features_list = $form_values['mo_saml_feature_list_table'];
    $custom_features = $form_values['mo_saml_other_feature'];
    $features = [];

    if ($form_values['mo_saml_select_all_feature']) {
      $features[] = 'All';
    }
    else {
      foreach ($features_list as $row) {
        foreach ($row as $key => $value) {
          if ($value == '1') {
            $features[] = $key;
          }
        }
      }
    }
    if (!empty($custom_features)) {
      $features[] = $custom_features;
    }
    return implode(', ', $features);
  }

  protected function generateTicket() {
    $unixTime = time();
    $unixTimeLastDigits = substr($unixTime, -8);
    return 'DR-SP-' . $unixTimeLastDigits;
  }

  //@todo -  optimize this send email sending function. Same kind of functions multiple times in the module.
  protected function sendEmail($customer_mail, $ticket) {
    $config = \Drupal::config('miniorange_saml.settings');
    $customerKey = $config->get('miniorange_saml_customer_id');
    $apikey = $config->get('miniorange_saml_customer_api_key');
    $subject = 'Your miniOrange SAML SP Module 7-day Trial Request Confirmation - Ticket #' . $ticket;

    $customer_template = [
      '#theme' => 'mo-saml-send-ticket-info',
      '#ticket_number' => $ticket,
    ];
    $content = \Drupal::service('renderer')->renderPlain($customer_template);

    $fields = [
      'customerKey' => empty($customerKey) ? MiniorangeSAMLConstants::DEFAULT_CUSTOMER_ID : $customerKey,
      'sendEmail' => TRUE,
      'email' => [
        'customerKey' => empty($customerKey) ? MiniorangeSAMLConstants::DEFAULT_CUSTOMER_ID : $customerKey,
        'fromEmail' => MiniorangeSAMLConstants::SUPPORT_EMAIL,
        'fromName' => 'miniOrange',
        'toEmail' => $customer_mail,
        'toName' => $customer_mail,
        'subject' => $subject,
        'content' => $content,
      ],
    ];

    $url = MiniorangeSAMLConstants::FEEDBACK_API;
    $api = $customerKey == '' ? new MoAuthApi() : new MoAuthApi($customerKey, $apikey);
    $header = $api->getHttpHeaderArray();
    $api->makeCurlCall($url, $fields, $header);
  }

  protected function getQueryContent($trial_details) {
    $html = '<br><br>Account Details and Usecase:';
    $html .= '<pre><code><table style="border-collapse: collapse; border: 1px solid black; width: 100%;">';
    foreach ($trial_details as $key => $value) {
      $html .= '<tr>';
      $html .= '<td style="padding: 10px; width: 15%;"><b>' . $key . ':</b></td>';
      $html .= '<td style="padding: 10px; width: 85%;">' . $value . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table></code></pre>';

    return $html;
  }

}
