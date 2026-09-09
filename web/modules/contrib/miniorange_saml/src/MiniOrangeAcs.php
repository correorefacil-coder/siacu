<?php
namespace Drupal\miniorange_saml;

use DOMDocument;
use Exception;
use DOMXpath;

/**
 * The MiniOrangeAcs class.
 */
class MiniOrangeAcs {

  /**
   * The function processSamlResponse.
   */
  public function processSamlResponse($post, $acs_url, $cert_fingerprint, $issuer, $base_url, $spEntityId, $username_attribute, $custom_attributes, $custom_roles) {
    if (array_key_exists('SAMLResponse', $post ) ) {
		$saml_response = $post['SAMLResponse'];
    } else {
      $error_message = 'Missing SAMLRequest or SAMLResponse parameter. Please contact your administrator.';
      \Drupal::logger('miniorange_saml')->error($error_message);

      $error ='Could not process your request.';
      $message='Please contact your administrator.';
      Utilities::showErrorMessage($error,$message,'');
    }

	$RelayState = $base_url;

	if(array_key_exists('RelayState', $post) && ($post['RelayState'] != 'testValidate')) {
		$RelayState = $post['RelayState'];
    }

    $saml_response = base64_decode($saml_response);
    $document = new DOMDocument();
    $document->loadXML($saml_response);
	$saml_response_xml = $document->firstChild;

	if ( $RelayState == "showSamlResponse" ) {
        Utilities::Print_SAML_Request($saml_response,"displaySamlResponse");
    }

	$doc = $document->documentElement;
	$xpath = new DOMXpath($document);
	$xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
	$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

	$status = $xpath->query('/samlp:Response/samlp:Status/samlp:StatusCode', $doc);
	$statusString = $status->item(0)->getAttribute('Value');
	$statusChildString = '';
	if( $status->item(0)->firstChild !== null ) {
		$statusChildString = $status->item(0)->firstChild->getAttribute('Value');
	}

	$stat = explode(":", $statusString);
	$status = $stat[7];

	if($status!="Success"){
		if( !empty( $statusChildString ) ) {
			$stat = explode(":", $statusChildString);
			$status = $stat[7];
		}
		$this->show_error_message($status,$post);
	}

    $cert_fingerprint = XMLSecurityKey::getRawThumbprint($cert_fingerprint);

    $saml_response = new SAML2_Response($saml_response_xml);
    $cert_fingerprint = preg_replace('/\s+/', '', $cert_fingerprint);
    if (\Drupal::config('miniorange_saml.settings')->get('miniorange_saml_character_encoding')){
      $cert_fingerprint = iconv("UTF-8", "CP1252//IGNORE", $cert_fingerprint);
    }

    $response_signature_data = $saml_response->getSignatureData();
    $assertion_signature_data = current($saml_response->getAssertions())->getSignatureData();
    if ( is_null( $response_signature_data ) && is_null( $assertion_signature_data ) ) {
      echo 'Neither response nor assertion is signed';
      exit();
    }

    if ( !is_null($response_signature_data) ) {
      $response_valid_signature = Utilities::processResponse($acs_url, $cert_fingerprint, $response_signature_data, $saml_response );
      if(!$response_valid_signature) {
        echo 'Invalid Signature in SAML Response';
        exit();
      }
    }

    if ( !is_null( $assertion_signature_data ) ) {
      $assertion_valid_signature = Utilities::processResponse($acs_url, $cert_fingerprint, $assertion_signature_data, $saml_response);
      if( !$assertion_valid_signature ) {
        echo 'Invalid Signature in SAML Assertion';
        exit();
      }
    }

    /* Check the expiry of the SAML assertion */

    $not_after    = current($saml_response->getAssertions())->getNotOnOrAfter();
    $not_before   = current($saml_response->getAssertions())->getNotBefore();
    $allowed_sec  = MiniorangeSAMLConstants::ASSERTION_ALLOWANCE_SECONDS;


    if ($not_after &&  ($not_after + $allowed_sec <= time())) {
      $timeDetails = [
        '%notOnOrAfter' => date('M j, Y g:i a', $not_after),
        '%currentTime' => date('M j, Y g:i a', time())
      ];
      $error   = t('Authentication failed: The SAML assertion has expired.');
      $message = t('The assertion was only valid until %notOnOrAfter, and the current time is %currentTime.', $timeDetails);
      Utilities::showErrorMessage($error, '', $message);
    }

    if ($not_before && ($not_before > $allowed_sec + time())) {
      $timeDetails = [
        '%notBefore' => date('M j, Y g:i a', $not_before),
        '%currentTime' => date('M j, Y g:i a', time())
      ];

      $error   = t("Authentication failed: The SAML assertion is not yet valid.");
      $message = t("The assertion will be valid from %notBefore, and the current time is %currentTime.", $timeDetails);
      Utilities::showErrorMessage($error, '', $message);
    }

    Utilities::validateIssuerAndAudience($saml_response, $spEntityId, $issuer);

    $attrs = current($saml_response->getAssertions())->getAttributes();

    if ($username_attribute != 'NameID') {
      if ( array_key_exists( $username_attribute, $attrs ) ) {
        $username = $attrs[$username_attribute][0];
      }
      else {
        // Get NameID value if username attribute doesnt exist in response.
        $username = current(current($saml_response->getAssertions())->getNameId());
      }
    }
    else {
      // Get Name ID value.
      $username = current(current($saml_response->getAssertions())->getNameId());
    }

    // Get Email.
    $email_attribute = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_email_attribute');
    if ( $email_attribute == 'NameID' ) {
      $email_value = current(current($saml_response->getAssertions())->getNameId());
    }
    else {
      $email_value = $attrs[$email_attribute][0];
    }

	 // Get RelayState if any.
	$relay_state = $base_url;
    if(array_key_exists('RelayState', $post)) {
      if($post['RelayState'] == 'testValidate') {
        $this->showTestResults($username, $attrs);
      } else if($post['RelayState'] != '/') {
		$relay_state = $post['RelayState'];
	  }
    }

	$sessionIndex = current($saml_response->getAssertions())->getSessionIndex();
	$nameId = current(current($saml_response->getAssertions())->getNameId());

	/*Custom Attributes*/
	$custom_attribute_values = array();

	foreach($custom_attributes as $key=>$value){
		if(array_key_exists($value, $attrs)){
			$attr_value = $attrs[$value][0];
			$custom_attribute_values[$key] = $attr_value;
		}
	}



    $response = array();
    $response['email']    = $email_value;
    $response['username'] = $username;
	$response['NameID']   = $nameId;
	$response['sessionIndex'] = $sessionIndex;

	if( !empty( $relay_state ) ) {
		$response['relay_state'] = $relay_state;
	}
    return $response;
  }

	public function show_error_message( $statusCode, $post ) {
		if( $post['RelayState'] == 'testValidate' ) {
      Utilities::testConfigurationLogs('Invalid SAML Response Status');
			echo '<div style="font-family:Calibri;padding:0 3%;">';
			echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
			<div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong> Invalid SAML Response Status.</p>
			<p><strong>Causes</strong>: Identity Provider has sent \''.$statusCode.'\' status code in SAML Response. </p>
							<p><strong>Reason</strong>: '.$this->get_status_message($statusCode).'</p><br>
			</div>

			<div style="margin:3%;display:block;text-align:center;">
			<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';
							exit;
		  }
		  else {
        Utilities::ssoLogs('Invalid SAML Response Status');
				if( $statusCode == 'RequestDenied' ) {
					echo 'You are not allowed to login into the site. Please contact your Administrator.';
					exit;
				} else {
					echo 'We could not sign you in. Please contact your Administrator.';
					exit;
				}
		  }
	}

	public function get_status_message($statusCode) {
		switch($statusCode) {
			case 'RequestDenied':
				return 'You are not allowed to login into the site. Please contact your Administrator.';
				break;
			case 'Requester':
				return 'The request could not be performed due to an error on the part of the requester.';
				break;
			case 'Responder':
				return 'The request could not be performed due to an error on the part of the SAML responder or SAML authority.';
				break;
			case 'VersionMismatch':
				return 'The SAML responder could not process the request because the version of the request message was incorrect.';
				break;
			default:
				return 'Unknown';
		}
	}

  public function showTestResults($username, $attrs) {
	$module_path = \Drupal::service('extension.list.module')->getPath('miniorange_saml');
    echo '<div style="font-family:Calibri;padding:0 3%;">';
    if (!empty($username)) {
      echo '<div
    style="color: #3c763d; background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt; border-radius:10px;margin-top:17px;">TEST SUCCESSFUL</div>
<div style="display:block;text-align:center;margin-bottom:4%;"><svg class="animate" width="100" height="100">
        <filter id="dropshadow" height="">
            <feGaussianBlur in="SourceAlpha" stdDeviation="3" result="blur"></feGaussianBlur>
            <feFlood flood-color="rgba(76, 175, 80, 1)" flood-opacity="0.5" result="color"></feFlood>
            <feComposite in="color" in2="blur" operator="in" result="blur"></feComposite>
            <feMerge>
                <feMergeNode></feMergeNode>
                <feMergeNode in="SourceGraphic"></feMergeNode>
            </feMerge>
        </filter>
        <circle cx="50" cy="50" r="46.5" fill="none" stroke="rgba(76, 175, 80, 0.5)" stroke-width="5"></circle>
        <path d="M67,93 A46.5,46.5 0,1,0 7,32 L43,67 L88,19" fill="none" stroke="rgba(76, 175, 80, 1)" stroke-width="5"
            stroke-linecap="round" stroke-dasharray="80 1000" stroke-dashoffset="-220" style="filter:url(#dropshadow)">
        </path>
    </svg>
    <style>
        svg.animate path {
            animation: dash 1.5s linear both;
            animation-delay: 0.25s;
        }

        @keyframes dash {
            0% {
                stroke-dashoffset: 210;
            }
            75% {
                stroke-dashoffset: -220;
            }
            100% {
                stroke-dashoffset: -205;
            }
        }
    </style>
</div>';
    }
    else {
      Utilities::testConfigurationLogs('Some Attributes Did Not Match');
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;">TEST FAILED</div>
          <div style="color: #a94442;font-size:14pt; margin-bottom:20px;">WARNING: Some Attributes Did Not Match.</div>
          <div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;"src="'. $module_path . 'includes/images/wrong.png"></div>';
    }
    echo '<span style="font-size:14pt;"><b>Hello</b>, '.$username.'</span><br/><p style="font-weight:bold;font-size:14pt;margin-left:1%;">ATTRIBUTES RECEIVED:</p>
        <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:14pt;background-color:#EDEDED;">
        <tr style="text-align:center;"><td style="font-weight:bold;border:2px solid #949090;padding:2%;">ATTRIBUTE NAME</td><td style="font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td></tr>';
    if (!empty($attrs)) {
      echo "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>NameID</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" . $username . "</td></tr>";
      foreach ($attrs as $key => $value) {
        echo "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>" . $key . "</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" . implode("<br/>",$value) . "</td></tr>";
      }
    }
    else {
      echo "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>NameID</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" . $username . "</td></tr>";
    }
    echo '</table></div>';
    echo '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';
    exit;
  }
}
