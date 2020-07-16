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

    public const SAMLREQUEST_KEY = 'SAMLRequest';

	/**
	 * Handle the event.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		if (request()->filled(self::SAMLREQUEST_KEY) && !request()->is('sso/logout') && !request()->is('saml/logout') && !request()->is('api/v1/sso/logout')) {
//			if ($this->twoFactorIsEnable()) {
//				abort(redirect('verify'), 302);
//			}

			abort(response(SamlSso::dispatchNow()), 302);
		}
	}

	/**
	 * @return bool
	 */
	private function twoFactorIsEnable()
	{
		if (config('twofactor.enable') && !request()->expectsJson()
			&& auth()->check() && auth()->user()
			&& !SupportTwoFactor::isContainInWhitelist(request())) {
			$config = SupportTwoFactor::getCompatibleConfig(request());

			if (!empty($config)) {
				$config->put(self::SAMLREQUEST_KEY, request()->get(self::SAMLREQUEST_KEY));
				$notify = (count($config->get('channels')) === 1) ;
				auth()->user()->generateTwoFactorCode($config, $notify);
				return true;
			}
		}

		return false;
	}
}
