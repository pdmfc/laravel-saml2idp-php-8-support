<?php


namespace PDMFC\Saml2Idp\Support;


use App\AuditEventType;
use App\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\AuthnRequest;

class Helper
{
    /**
     * @param $samlRequest
     * @return ServiceProvider|bool
     * @throws ValidationException
     */
    public static function getServiceProviderFrom($samlRequest)
    {
        if ($samlRequest) {
                $authRequest = self::deserializeAuthNRequest($samlRequest);
                return ServiceProvider::findByEntityId(base64_encode($authRequest->getAssertionConsumerServiceURL()));

        }

        return false;
    }

    /**
     * @param $samlRequest
     * @return bool|AuthnRequest
     * @throws ValidationException
     */
    public static function deserializeAuthNRequest($samlRequest)
    {
        if ($samlRequest) {
            try {
                $deserializationContext = new DeserializationContext;
                $deserializationContext->getDocument()->loadXML(gzinflate(base64_decode($samlRequest)));

                $authRequest = new AuthnRequest;
                $authRequest->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

                return $authRequest;

            } catch (\ErrorException $e) {
                \Log::error($samlRequest);
                throw ValidationException::withMessages(['invalid_xml'=>$e->getMessage()]);
            }
        }

        return false;
    }
}
