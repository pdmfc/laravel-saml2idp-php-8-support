<?php


namespace PDMFC\Saml2Idp\Listeners;


use PDMFC\Saml2Idp\Jobs\SamlSso;
use App\TwoFactorCode;
use Illuminate\Auth\Events\Authenticated;

/**
 * Class SamlAuthenticated
 * @package App\Listeners
 */
class SamlAuthenticated
{
	/**
	 * Listen for the Authenticated event
	 *
	 * @return void [type]               [description]
	 */
	public function handle()
	{
		if (request()->filled('SAMLRequest') && !request()->is('sso/logout') && !request()->is('saml/logout') && !request()->is('api/v1/sso/logout')
			&& request()->isMethod('get')
            //&& !$this->isPendingTwoFactor()
            ) {

			abort(response(SamlSso::dispatchNow()), 302);
		}
	}

	/**
	 * @return bool
	 */
	private function isPendingTwoFactor()
	{
		if (auth()->check() && auth()->user() && auth()->user()->twoFactorCode() instanceof TwoFactorCode) {
			return true;
		}

		return false;
	}

}
