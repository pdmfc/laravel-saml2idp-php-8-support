<?php

namespace PDMFC\Saml2Idp\Http\Middleware;

use App\AuditEventType;
use App\Ldap\SSOConstants;
use App\Providers\RouteServiceProvider;
use App\ServiceProvider;
use App\Support\EventAuditable;
use App\Support\Helper;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyAuthorizedServiceProviders extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @throws TokenMismatchException
     * @throws ValidationException
     */
    public function handle($request, \Closure $next)
    {
        if( $request->filled('SAMLRequest') && !request()->is('sso/logout') && !request()->is('saml/logout') && !request()->is('api/v1/sso/logout')) {

            $authNRequest = Helper::deserializeAuthNRequest(request('SAMLRequest'));

            $entityId = base64_encode($authNRequest->getAssertionConsumerServiceURL());
            $serviceProvider = ServiceProvider::findByEntityId($entityId);

            if (!$serviceProvider) {
                $authRequest=[
                    'error_message' => 'Application not authorized',
                    'entity_id' => $entityId,
                    'acs_endpoint' => $authNRequest->getAssertionConsumerServiceURL(),
                    'request_id' => $authNRequest->getID(),
                ];


				app(EventAuditable::class)->audit(AuditEventType::SSO_APPLICATION_NOT_AUTHORIZED,array_merge($authRequest,$request->all()),['error_message' => 'Application not authorized']);
                throw new AccessDeniedHttpException();
            }
        }

        return $next($request);
    }

}
