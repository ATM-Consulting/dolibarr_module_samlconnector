<?php

use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Auth;

/**
 * @param int $fk_idp
 * @return array
 */
function saml_settings(int $fk_idp = 0): array {
    global $conf;
    $saml_url_root = dol_buildpath('/samlconnector', 2);

    $certContent = $privKeyContent = '';
    if(file_exists($conf->global->SAMLCONNECTOR_SP_CERT_PATH)) $certContent = file_get_contents($conf->global->SAMLCONNECTOR_SP_CERT_PATH);
    if(file_exists($conf->global->SAMLCONNECTOR_SP_PRIV_KEY_PATH)) $privKeyContent = file_get_contents($conf->global->SAMLCONNECTOR_SP_PRIV_KEY_PATH);

	$xmlMetadataSource = $xmlMetadataFilePath = $xmlMetadataUrl = '';
	$entity = 0;
	if(!empty($fk_idp)) {
		global $db;
		dol_include_once('samlconnector/class/samlconnectoridp.class.php');
		$idp = new SamlConnectorIDP($db);
		$res = $idp->fetch($fk_idp);
		if($res > 0) {
			$xmlMetadataSource = SamlConnectorIDP::TSourceFile[$idp->metadata_source];
			$xmlMetadataFilePath = $idp->metadata_xml_path;
			$xmlMetadataUrl = $idp->metadata_url;
			$entity = $idp->entity;
		}
	} else {
		$xmlMetadataSource = $conf->global->SAMLCONNECTOR_IDP_METADATA_SOURCE;
		$xmlMetadataFilePath = $conf->global->SAMLCONNECTOR_IDP_METADATA_XML_PATH;
		$xmlMetadataUrl = $conf->global->SAMLCONNECTOR_IDP_METADATA_URL;
		$entity = $conf->entity;
	}

    $settings = [
        // If 'strict' is True, then the PHP Toolkit will reject unsigned
        // or unencrypted messages if it expects them to be signed or encrypted.
        // Also it will reject the messages if the SAML standard is not strictly
        // followed: Destination, NameId, Conditions ... are validated too.
        'strict' => false,

        // Enable debug mode (to print errors).
        'debug' => false,

        // Set a BaseURL to be used instead of try to guess
        // the BaseURL of the view that process the SAML Message.
        // Ex http://sp.example.com/
        //    http://example.com/sp/
        'baseurl' => null,

        // Service Provider Data that we are deploying.
        'sp' => [
            // Identifier of the SP entity  (must be a URI)
            'entityId' => DOL_MAIN_URL_ROOT,
            // Specifies info about where and how the <AuthnResponse> message MUST be
            // returned to the requester, in this case our SP.
            'assertionConsumerService' => [
                // URL Location where the <Response> from the IdP will be returned
                'url' => $saml_url_root.'/acs.php?entity='.$entity.'&fk_idp='.$fk_idp,
                // SAML protocol binding to be used when returning the <Response>
                // message. OneLogin Toolkit supports this endpoint for the
                // HTTP-POST binding only.
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
            ],
            // If you need to specify requested attributes, set a
            // attributeConsumingService. nameFormat, attributeValue and
            // friendlyName can be omitted
            'attributeConsumingService' => [
                'serviceName' => 'Dolibarr CRM',
                'serviceDescription' => 'ERP for small enterprises',
                'requestedAttributes' => [
                    [
                        'name' => 'mail',
                        'isRequired' => true,
                        'nameFormat' => '',
                        'friendlyName' => 'User email address',
                        'attributeValue' => []
                    ],
                    [
                        'name' => 'uid',
                        'isRequired' => false,
                        'nameFormat' => '',
                        'friendlyName' => 'User name',
                        'attributeValue' => []
                    ]
                ]
            ],
            // Specifies info about where and how the <Logout Response> message MUST be
            // returned to the requester, in this case our SP.
            'singleLogoutService' => [
                // URL Location where the <Response> from the IdP will be returned
                'url' => $saml_url_root.'/sls.php?entity='.$entity.'&fk_idp='.$fk_idp,
                // SAML protocol binding to be used when returning the <Response>
                // message. OneLogin Toolkit supports the HTTP-Redirect binding
                // only for this endpoint.
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
            ],
            // Specifies the constraints on the name identifier to be used to
            // represent the requested subject.
            // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported.
            'NameIDFormat' => Constants::NAMEID_EMAIL_ADDRESS,
            // Usually x509cert and privateKey of the SP are provided by files placed at
            // the certs folder. But we can also provide them with the following parameters
            'x509cert' => $certContent,
            'privateKey' => $privKeyContent,

            /*
             * Key rollover
             * If you plan to update the SP x509cert and privateKey
             * you can define here the new x509cert and it will be
             * published on the SP metadata so Identity Providers can
             * read them and get ready for rollover.
             */
            // 'x509certNew' => '',
        ],

        // Identity Provider Data that we want connected with our SP.
        'idp' => [
            // Identifier of the IdP entity  (must be a URI)
            'entityId' => '',
            // SSO endpoint info of the IdP. (Authentication Request protocol)
            'singleSignOnService' => [
                // URL Target of the IdP where the Authentication Request Message
                // will be sent.
                'url' => '',
                // SAML protocol binding to be used when returning the <Response>
                // message. OneLogin Toolkit supports the HTTP-Redirect binding
                // only for this endpoint.
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
            ],
            // SLO endpoint info of the IdP.
            'singleLogoutService' => [
                // URL Location of the IdP where SLO Request will be sent.
                'url' => '',
                // URL location of the IdP where the SP will send the SLO Response (ResponseLocation)
                // if not set, url for the SLO Request will be used
                'responseUrl' => '',
                // SAML protocol binding to be used when returning the <Response>
                // message. OneLogin Toolkit supports the HTTP-Redirect binding
                // only for this endpoint.
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
            ],
            // Public x509 certificate of the IdP
            'x509cert' => ''
            /*
             *  Instead of use the whole x509cert you can use a fingerprint in order to
             *  validate a SAMLResponse, but we don't recommend to use that
             *  method on production since is exploitable by a collision attack.
             *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it,
             *   or add for example the -sha256 , -sha384 or -sha512 parameter)
             *
             *  If a fingerprint is provided, then the certFingerprintAlgorithm is required in order to
             *  let the toolkit know which algorithm was used. Possible values: sha1, sha256, sha384 or sha512
             *  'sha1' is the default value.
             *
             *  Notice that if you want to validate any SAML Message sent by the HTTP-Redirect binding, you
             *  will need to provide the whole x509cert.
             */
            // 'certFingerprint' => '',
            // 'certFingerprintAlgorithm' => 'sha1',

            /* In some scenarios the IdP uses different certificates for
             * signing/encryption, or is under key rollover phase and
             * more than one certificate is published on IdP metadata.
             * In order to handle that the toolkit offers that parameter.
             * (when used, 'x509cert' and 'certFingerprint' values are
             * ignored).
             */
            // 'x509certMulti' => array(
            //      'signing' => array(
            //          0 => '<cert1-string>',
            //      ),
            //      'encryption' => array(
            //          0 => '<cert2-string>',
            //      )
            // ),
        ]
    ];

    $advancedSettings = [
        // Compression settings
        'compress' => [
            'requests' => true,
            'responses' => true
        ],
        // Security settings
        'security' => [
            /** signatures and encryptions offered */

            // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
            // will be encrypted.
            'nameIdEncrypted' => false,

            // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
            // will be signed.  [Metadata of the SP will offer this info]
            'authnRequestsSigned' => true,

            // Indicates whether the <samlp:logoutRequest> messages sent by this SP
            // will be signed.
            'logoutRequestSigned' => true,

            // Indicates whether the <samlp:logoutResponse> messages sent by this SP
            // will be signed.
            'logoutResponseSigned' => true,

            /* Sign the Metadata
             False || True (use sp certs) || array (
                                                        keyFileName => 'metadata.key',
                                                        certFileName => 'metadata.crt'
                                                    )
            */
            'signMetadata' => false,

            /** signatures and encryptions required **/

            // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest>
            // and <samlp:LogoutResponse> elements received by this SP to be signed.
            'wantMessagesSigned' => false,

            // Indicates a requirement for the <saml:Assertion> elements received by
            // this SP to be encrypted.
            'wantAssertionsEncrypted' => false,

            // Indicates a requirement for the <saml:Assertion> elements received by
            // this SP to be signed. [Metadata of the SP will offer this info]
            'wantAssertionsSigned' => true,

            // Indicates a requirement for the NameID element on the SAMLResponse
            // received by this SP to be present.
            'wantNameId' => true,

            // Indicates a requirement for the NameID received by
            // this SP to be encrypted.
            'wantNameIdEncrypted' => false,

            // Authentication context.
            // Set to false and no AuthContext will be sent in the AuthNRequest.
            // Set true or don't present this parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'.
            // Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509').
            'requestedAuthnContext' => false,

            // Indicates if the SP will validate all received xmls.
            // (In order to validate the xml, 'strict' and 'wantXMLValidation' must be true).
            'wantXMLValidation' => true,

            // If true, SAMLResponses with an empty value at its Destination
            // attribute will not be rejected for this fact.
            'relaxDestinationValidation' => false,

            // Algorithm that the toolkit will use on signing process. Options:
            //    'http://www.w3.org/2000/09/xmldsig#rsa-sha1'
            //    'http://www.w3.org/2000/09/xmldsig#dsa-sha1'
            //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
            //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384'
            //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'
            // Notice that sha1 is a deprecated algorithm and should not be used
            'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

            // Algorithm that the toolkit will use on digest process. Options:
            //    'http://www.w3.org/2000/09/xmldsig#sha1'
            //    'http://www.w3.org/2001/04/xmlenc#sha256'
            //    'http://www.w3.org/2001/04/xmldsig-more#sha384'
            //    'http://www.w3.org/2001/04/xmlenc#sha512'
            // Notice that sha1 is a deprecated algorithm and should not be used
            'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',

            // ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses
            // uppercase. Turn it True for ADFS compatibility on signature verification
            'lowercaseUrlencoding' => false
        ]

        // Contact information template, it is recommended to supply a
        // technical and support contacts.
        //    'contactPerson' => array (
        //        'technical' => array (
        //            'givenName' => '',
        //            'emailAddress' => ''
        //        ),
        //        'support' => array (
        //            'givenName' => '',
        //            'emailAddress' => ''
        //        ),
        //    ),

        // Organization information template, the info in en_US lang is
        // recomended, add more if required.
        //    'organization' => array (
        //        'en-US' => array(
        //            'name' => '',
        //            'displayname' => '',
        //            'url' => ''
        //        ),
        //    ),
    ];

    $settings = array_merge($settings, $advancedSettings);

    if($xmlMetadataSource == 'localFile') $idp_metadata = IdPMetadataParser::parseFileXML($xmlMetadataFilePath);
    else $idp_metadata = IdPMetadataParser::parseRemoteXML($xmlMetadataUrl); // Url

    $settings_compiled = IdPMetadataParser::injectIntoSettings($settings, $idp_metadata);
    $settings_compiled['sp'] = $settings['sp'];
    return $settings_compiled;
}

/**
 * Get SAML Auth object
 *
 * @param int $fk_idp
 * @return Auth
 * @throws \OneLogin\Saml2\Error
 */
function get_saml(int $fk_idp = 0): Auth {
    return new Auth(saml_settings($fk_idp));
}
