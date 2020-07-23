<?php


namespace PDMFC\Saml2Idp\Listeners;


use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Str;

class SamlLogout
{
    /**
     * Upon logout, initiate SAML SLO process for each Service Provider
     * Simply redirect to the saml/logout route to handle SLO
     *
     * @return void
     */
    public function handle()
    {
        // Make sure we are not in the process of SLO when handling the redirect
        // the redirect should not happen when the session guard is managed by Nova
        if (!session('saml.slo') && !Str::contains(auth()->guard()->getName(), config('nova.guard'))) {
			//app(EventAuditable::class)->audit(AuditEventType::SSO_LOGOUT);
            //abort(redirect('saml/logout'), 200);
        }
    }
}
