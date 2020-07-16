<?php

namespace PDMFC\Saml2Idp\Listeners;

use LightSaml\ClaimTypes;
use LightSaml\Model\Assertion\Attribute;
use CodeGreenCreative\SamlIdp\Events\Assertion;

class SamlAssertionAttributes
{
    /**
     * Handle the event.
     *
     * @param Assertion $event
     * @return void
     */
    public function handle(Assertion $event): void
    {

        $event->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::PPID, auth()->user()->id))
            ->addAttribute(new Attribute(ClaimTypes::NAME, auth()->user()->name));

    }
}
