<?php

namespace PDMFC\Saml2Idp\Http\Controllers;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\ServiceProvider;
use App\Support\EventAuditable;
use PDMFC\Saml2Idp\Jobs\SamlSlo;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;

/**
 * Class LoginController
 * @package App\Http\Controllers\Auth
 */
class LogoutController extends Controller
{
    public const SAML_SLO_REDIRECT_KEY = 'saml.slo_redirect';
    public const SAML_SLO_KEY = 'saml.slo';
    /**
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function index(Request $request)
    {

        $slo_redirect = $request->session()->get(self::SAML_SLO_REDIRECT_KEY);

        if (!$slo_redirect) {
            $this->setSloRedirect($request);
            $slo_redirect = $request->session()->get(self::SAML_SLO_REDIRECT_KEY);
        }

        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        $serviceProviders = \PDMFC\Saml2Idp\Application::get()->keyBy('entity_id')
            ->map(static function($sp){
                return [
                    'destination'=>$sp->acs_callback,
                    'logout'=>$sp->sls_callback,
                    'certificate'=>$sp->certificate,
                    'query_params' =>false
                ];
            })->toArray();

        foreach ($serviceProviders as $key => $sp) {
            // Check if the service provider supports SLO
            if (! empty($sp['logout']) && ! in_array($key, $request->session()->get(self::SAML_SLO_KEY, []))) {
                // Push this SP onto the saml slo array
                $request->session()->push('saml.slo', $key);
                $domainParts = explode('/',$slo_redirect);

                $domain = count($domainParts) > 2 ?  $domainParts[2] : '';
                if(Str::contains($sp['destination'], $domain) || $slo_redirect === config('samlidp.login_uri')){
                    return redirect(SamlSlo::dispatchNow($sp));
                }

            }
        }

        if (config('samlidp.logout_after_slo')) {
            auth()->logout();
            $request->session()->invalidate();
        }

        $request->session()->forget(self::SAML_SLO_KEY);
        $request->session()->forget(self::SAML_SLO_REDIRECT_KEY);

        if ($request->ajax()){
            return ['success' =>true , 'message' => 'user loged out'];

        }
        return redirect($slo_redirect);
    }

    /**
     * @param Request $request
     */
    private function setSloRedirect(Request $request): void
    {
        // Look for return_to query in case of not relying on HTTP_REFERER
        $http_referer = $request->has('return_to') ? $request->get('return_to') : $request->server('HTTP_REFERER');
        $redirects = config('samlidp.sp_slo_redirects', []);
        $slo_redirect = $request->has('SAMLRelay') ? $request->input('SAMLRelay') : config('samlidp.login_uri');
        foreach ($redirects as $referer => $redirectPath) {
            if (Str::startsWith($http_referer, $referer)) {
                $slo_redirect = $redirectPath;
                break;
            }
        }

        $request->session()->put(self::SAML_SLO_REDIRECT_KEY, $slo_redirect);
    }
}
