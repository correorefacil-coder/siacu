<?php /**
 * @file
 * Contains \Drupal\miniorange_saml\Controller\DefaultController.
 */

namespace Drupal\miniorange_saml\Controller;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\Api\MoAuthApi;
use Drupal\miniorange_saml\MiniOrangeAcs;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\HttpFoundation\Response;
use Drupal\miniorange_saml\MiniOrangeAuthnRequest;
use Drupal\miniorange_saml\MiniorangeSAMLConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\miniorange_saml\miniorange_saml_sp_registration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Default controller for the miniorange_saml module.
 */
class miniorange_samlController extends ControllerBase {
    protected $formBuilder;

    public function __construct(FormBuilder $formBuilder = NULL){
        $this->formBuilder = $formBuilder;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get("form_builder")
        );
    }

    public function openModalForm() {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_saml\Form\MiniorangeSAMLRemoveLicense');
        $response->addCommand(new OpenModalDialogCommand('Remove Account', $modal_form, ['width' => '800']));
        return $response;
    }

    public function saml_login($relay_state="") {
        if($relay_state != 'testValidate') {
          \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_sso_tried','Yes')->save();
        }
        $base_url = Utilities::getBaseUrl();
        Utilities::is_sp_configured($relay_state);
        $issuer =  Utilities::getIssuer();
        $saml_login_url = $base_url . '/samllogin';

        if ( empty($relay_state) || $relay_state==$saml_login_url ) {
            $relay_state = $_SERVER['HTTP_REFERER'] ?? '';
        }
        if(empty($relay_state) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])){
          $pre = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
          $url = $pre . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
          $relay_state = $url;
        }
        if ( empty($relay_state) || $relay_state==$saml_login_url ) {
            $relay_state = $base_url;
        }

        $acs_url = Utilities::getAcsUrl();
        $sso_url = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_login_url');
        $nameid_format = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_nameid_format');
        $authn_request = new MiniOrangeAuthnRequest();
        $redirect = $authn_request->initiateLogin( $acs_url, $sso_url, $issuer, $nameid_format, $relay_state );
        $response = new RedirectResponse( $redirect );
        $response->send();
        return new Response();
    }

    public function saml_response() {
        $base_url = Utilities::getBaseUrl();
        $acs_url = Utilities::getAcsUrl();
        $cert_fingerprint = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_x509_certificate');
        $issuer = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_issuer');
        $sp_entity_id = Utilities::getIssuer();

        $username_attribute = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_email_attribute');

        if ( isset( $_GET['SAMLResponse'] ) ) {
            session_destroy();
            $response = new RedirectResponse($base_url);
            $response->send();
            return new Response();
        }
        $attrs = array();
        $role = array();
        $response_obj = new MiniOrangeAcs();
        $response = $response_obj->processSamlResponse($_POST, $acs_url, $cert_fingerprint, $issuer, $base_url, $sp_entity_id, $username_attribute, $attrs, $role);

        $account = user_load_by_name( $response['username'] );

        // Create user if not already present.
        if ( $account == NULL ) {

            $random_password = \Drupal::service('password_generator')->generate(8);

            $new_user = [
                'name' => $response['username'],
                'mail' => $response['email'],
                'pass' => $random_password,
                'status' => 1,
            ];
            // user_save() is now a method of the user entity.
            $account = User::create( $new_user );
            $account->save();

            $enable_roleMapping = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_rolemapping');

            if ( $enable_roleMapping ) {
                /**
                 * Getting machine names of the roles.
                 */
                $roles_with_machine_name = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
                $roles_with_machine_name_array = array();
                foreach ( $roles_with_machine_name as $key => $values ) {
                    $roles_with_machine_name_array[$key] = strtolower( $values->label() );
                }

                /**
                 * Get machine name of the default role. (eg. Authenticated User(role) = authenticated(machine name))
                 */
                $default_role = \Drupal::config('miniorange_saml.settings')->get( 'miniorange_saml_default_role' );
                foreach ( $roles_with_machine_name_array as $machine_name => $role_name ) {
                    if ( $role_name == strtolower( $default_role ) ) {
                        $default_role_value = $machine_name;
                    }
                }

                /**
                 * Assign default role for user is default role is other than AUTHENTICATED USER.
                 */
                if ( isset( $default_role_value ) && $default_role_value != 'authenticated' ) {
                    $account->addRole( $default_role_value );
                    $account->save();
                }
            }
        }

        if ( user_is_blocked($response['username'] ) == FALSE ) {
            $rediectUrl = $base_url;
            if ( array_key_exists('relay_state', $response) && !empty( trim($response['relay_state']) ) ) {
                $rediectUrl = $response['relay_state'];
            }
            $_SESSION['sessionIndex'] = $response['sessionIndex'];
            $_SESSION['NameID'] = $response['NameID'];
            $_SESSION['mo_saml']['logged_in_with_idp'] = TRUE;

			     /**
            * Invoke the hook and check whether 2FA is enabled or not.
            */
            \Drupal::moduleHandler()->invokeAll( 'invoke_miniorange_2fa_before_login', [$account] );

            user_login_finalize($account);

            $response = new RedirectResponse( $rediectUrl );
            $request  = \Drupal::request();
            $request->getSession()->save();
            $response->prepare($request);
            \Drupal::service('kernel')->terminate($request, $response);
            $response->send();exit();
            return new Response();

        } else {
            $error = t('User Blocked By Administrator.');
            $message = t('Please Contact your administrator.');
            $cause = t('This user account is not allowed to login.');
            Utilities::ssoLogs('User Blocked By Administrator');
            Utilities::showErrorMessage($error, $message, $cause);
            return new Response();
        }
    }

    /**
     * Test configuration callback
     */

    function test_configuration() {
      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_test_configuration','Yes')->save();
        $this->saml_login('testValidate');
        return new Response();
    }

    function saml_request() {
        $this->saml_login("displaySAMLRequest");
        return new Response();
    }

    function saml_response_generator() {
        $this->saml_login('showSamlResponse');
        return new Response();
    }

    function saml_metadata() {
        $entity_id  = Utilities::getIssuer();
        $acs_url    = Utilities::getAcsUrl();
        $validUntil = Utilities::getMetadataValidationTime();
        $header    = isset($_REQUEST['download']) && boolval($_REQUEST['download']) ? 'Content-Disposition: attachment; filename="Metadata.xml"' : 'Content-Type: text/xml';
        header($header);
        echo '<?xml version="1.0"?>
                <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" validUntil="'. $validUntil .'" cacheDuration="PT'. MiniorangeSAMLConstants::TIME_CACHED .'S" entityID="' . $entity_id . '">
                  <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
                    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
                    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . $acs_url . '" index="1"/>
                  </md:SPSSODescriptor>
                  <md:Organization>
                    <md:OrganizationName xml:lang="en-US">miniOrange</md:OrganizationName>
                    <md:OrganizationDisplayName xml:lang="en-US">miniOrange</md:OrganizationDisplayName>
                    <md:OrganizationURL xml:lang="en-US">http://miniorange.com</md:OrganizationURL>
                  </md:Organization>
                  <md:ContactPerson contactType="technical">
                    <md:GivenName>miniOrange</md:GivenName>
                    <md:EmailAddress>info@xecurify.com</md:EmailAddress>
                  </md:ContactPerson>
                  <md:ContactPerson contactType="support">
                    <md:GivenName>miniOrange</md:GivenName>
                    <md:EmailAddress>info@xecurify.com</md:EmailAddress>
                  </md:ContactPerson>
                </md:EntityDescriptor>';
        exit;
    }

    function paymentPageVisited($plan_name) {
      $payment_page_string = \Drupal::configFactory()->getEditable('miniorange_saml.settings')->get('miniorange_saml_payment_page_name');
      $payment_page        = !isset($payment_page_string) ? array() : json_decode($payment_page_string,TRUE);
      $admin_email         = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_customer_admin_email');
      $admin_email         = (isset($admin_email) && !empty($admin_email)) ? $admin_email : '';
      $URL_Redirect        = '';

      if($plan_name == 'standard') {
        $URL_Redirect   = 'https://portal.miniorange.com/initializepayment?requestOrigin=drupal8_miniorange_saml_standard_plan';
        $payment_page['standard'] = 'STANDARD PLAN';
      }
      if($plan_name == 'premium') {
        $URL_Redirect   = 'https://portal.miniorange.com/initializepayment?requestOrigin=drupal8_miniorange_saml_premium_plan';
        $payment_page['premium'] = 'PREMIUM PLAN';
      }
      if($plan_name == 'enterprise') {
        $URL_Redirect   = 'https://portal.miniorange.com/initializepayment?requestOrigin=drupal8_miniorange_saml_enterprise_plan';
        $payment_page['enterprise'] = 'ENTERPRISE PLAN';
      }
      $payment_page_string = json_encode($payment_page);
      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_payment_page_name', $payment_page_string)->save();
      \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_payment_page_visited','Yes')->save();

      return new TrustedRedirectResponse($URL_Redirect);
    }


    public function miniorange_saml_close_registration(){
        Utilities::saml_back(true);
        return new Response();
    }

    public function add_new_idp() {
      $response     = new AjaxResponse();
      $upgradePlans =  Url::fromRoute('miniorange_saml.licensing')->toString();
      $trialForm    =  Url::fromRoute('miniorange_saml.trial')->toString();

      $form['miniorange_saml_add_idp'] = [
        '#type' => 'item',
        '#markup' => t('You can configure only one Identity Provider (IdP) in the free version of the module. Multiple IdPs are supported in the '). '<a target="_blank" href="'.$upgradePlans.'">[ENTERPRISE]</a>'. $this->t(' version of the module.') . '<br>
                        <a class="button button--small button--primary js-form-submit mo_top_bar_button form-submit use-ajax" href="'.$trialForm.'" data-dialog-type = "modal" data-dialog-options="{&quot;width&quot;:&quot;50%&quot;}" >Request 7-days Trial</a>',
      ];

      $response->addCommand(new OpenModalDialogCommand(t('Add New IDP'), $form, ['width' => '40%']));
      return $response;
    }
}
