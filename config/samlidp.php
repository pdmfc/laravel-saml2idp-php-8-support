<?php

use PDMFC\Saml2Idp\Events\Assertion;
use PDMFC\Saml2Idp\Listeners\SamlAuthenticated;
use PDMFC\Saml2Idp\Listeners\SamlLogin;
use PDMFC\Saml2Idp\Listeners\SamlLogout;

return [

    /*
    |--------------------------------------------------------------------------
    | SAML idP configuration file
    |--------------------------------------------------------------------------
    |
    | Use this file to configure the service providers you want to use.
    |
     */
    // Outputs data to your laravel.log file for debugging
    'debug' => true,
    // use database to authorize service providers
    'use_database' => false,
    // Defines the field name in the users table
    'nameid_field' => 'username',
    // The URI to your login page
    'login_uri' => 'sso/login',
    // Define the middleware's to use with sso routes
    'middleware' => ['web'],
    // Log out of the IdP after SLO
    'logout_after_slo' => env('LOGOUT_AFTER_SLO', true),
    // The URI to the saml metadata file, this describes your idP
    'issuer_uri' => 'saml/metadata',
    // Name of the certificate PEM file
    'certname' => 'cert.pem',
    // Name of the certificate key PEM file
    'keyname' => 'key.pem',
    // Encrypt requests and reponses
    'encrypt_assertion' => false,
    // Make sure messages are signed
    'messages_signed' => false,

    //All of the Laravel SAML IdP event / listener mappings.
    'saml_events' => [
        Assertion::class => [],
        'Illuminate\Auth\Events\Logout' => [
            SamlLogout::class,
        ],
        'Illuminate\Auth\Events\Authenticated' => [
            SamlAuthenticated::class,
        ],
        'Illuminate\Auth\Events\Login' => [
            SamlLogin::class,
        ],
    ],
    // list of all service providers
    'sp' => [
        //Example:
        // Base64 encoded ACS URL
        // 'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
        //     // Your destination is the ACS URL of the Service Provider
        //     'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
        //     'logout' => 'https://myfacebookworkplace.facebook.com/work/sls.php',
        //     'certificate' => '',
        //     'query_params' => false
        // ]
    ],

    // If you need to redirect after SLO depending on SLO initiator
    // key is beginning of HTTP_REFERER value from SERVER, value is redirect path
    'sp_slo_redirects' => [
         'http://app1.pdm' => 'http://app1.pdm',
    ]
];
