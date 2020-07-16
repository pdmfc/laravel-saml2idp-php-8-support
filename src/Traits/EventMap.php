<?php

namespace PDMFC\Saml2Idp\Traits;

use PDMFC\Saml2Idp\Events\Assertion;
use PDMFC\Saml2Idp\Listeners\SamlAuthenticated;
use PDMFC\Saml2Idp\Listeners\SamlLogin;
use PDMFC\Saml2Idp\Listeners\SamlLogout;

trait EventMap
{
    /**
     * All of the Laravel SAML IdP event / listener mappings.
     *
     * @var array
     */
    protected $events = [
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
    ];
}
