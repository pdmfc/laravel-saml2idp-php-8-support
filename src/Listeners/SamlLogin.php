<?php

namespace PDMFC\Saml2Idp\Listeners;



use PDMFC\Saml2Idp\Jobs\SamlSso;
use Illuminate\Auth\Events\Login;

/**
 * Class SamlLogin
 * @package App\Listeners
 */
class SamlLogin
{
	/**
	 * Handle the event.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		if (request()->filled('SAMLRequest') && !request()->is('sso/logout')
            && !request()->is('saml/logout') ) {

			abort(response(SamlSso::dispatchNow()), 302);
		}
	}

}
