<?php

namespace PDMFC\Saml2Idp\Jobs;


use App\AuditEventType;
use App\Ldap\SSOConstants;
use App\ServiceProvider;
use App\Support\EventAuditable;
use CodeGreenCreative\SamlIdp\Events\Assertion as AssertionEvent;
use CodeGreenCreative\SamlIdp\Jobs\SamlSso as LaravelSamlIdpSamlSso;
use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\Helper\MessageContextHelper;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use PDMFC\Saml2Idp\Application;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class SamlSso
 * @property  authn_request
 * @package App\Jobs
 */
class SamlSsoBD extends LaravelSamlIdpSamlSso
{

    use PerformsSingleSignOn;

    /**
     * Execute the job.
     */

    public function handle()
    {

        try {
            $deserializationContext = new DeserializationContext;
            $deserializationContext->getDocument()->loadXML(gzinflate(base64_decode(request('SAMLRequest'))));

        }catch (\ErrorException $e) {

            //AUDIT Invalid Request LOG
            //app(EventAuditable::class)->audit(AuditEventType::SSO_FAILED,[],['error_message' => $e->getMessage()]);

            return ['error'=>SSOConstants::SAML_INVALID_REQUEST];
        }

        $this->authn_request = new AuthnRequest;
        $this->authn_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

        $this->setDestination();
        return $this->response();
    }

    /**
     * @return array|false|string
     */
    public function response()
    {
        $this->response = (new Response)->setIssuer(new Issuer($this->issuer))
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setDestination($this->destination)
            ->setInResponseTo($this->authn_request->getId());

        $assertion = new Assertion;
        $nameID = auth()->user()->username ?: auth()->user()->email;
        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setIssuer(new Issuer($this->issuer))
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key))
            ->setSubject(
                (new Subject)
                    ->setNameID((new NameID($nameID, SamlConstants::NAME_ID_FORMAT_EMAIL)))
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation)
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData())
                                    ->setInResponseTo($this->authn_request->getId())
                                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                                    ->setRecipient($this->authn_request->getAssertionConsumerServiceURL())
                            )
                    )
            )
            ->setConditions(
                (new Conditions)
                    ->setNotBefore(new \DateTime)
                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                    ->addItem(
                        new AudienceRestriction([$this->authn_request->getIssuer()->getValue()])
                    )
            )
            ->addItem(
                (new AuthnStatement)
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext)
                            ->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );

        $attribute_statement = new AttributeStatement;
        event(new AssertionEvent($attribute_statement));
        // Add the attributes to the assertion
        $assertion->addItem($attribute_statement);

        // Encrypt the assertion
        if (config('samlidp.encrypt_assertion')) {
            $this->setSpCertificate();

            $encryptedAssertion = new EncryptedAssertionWriter();
            $encryptedAssertion->encrypt($assertion, KeyHelper::createPublicKey(
                (new X509Certificate)->loadPem($this->sp_certificate)
            ));
            $this->response->addEncryptedAssertion($encryptedAssertion);
        } else {
            $this->response->addAssertion($assertion);
        }

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(new SignatureWriter($this->certificate, $this->private_key));
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_POST);
    }

    /**
     * [sendSamlRequest description]
     *
     * @param $binding_type
     * @return array|false|string [type]           [description]
     */
    public function send($binding_type)
    {

        $messageContext = new MessageContext;
        $messageContext->setMessage($this->response)->asResponse();
        $message = $messageContext->getMessage();
        $message->setRelayState(request('RelayState'));


        if ( request()->expectsJson()) {

            $wsResponse = $this->serializeSAMLForWS($messageContext);
            //AUDIT SSO WS Interaction
//            app(EventAuditable::class)->audit(AuditEventType::SSO_LOGIN,[], $wsResponse);

            return $wsResponse;
        }

        $bindingFactory = new BindingFactory;
        $postBinding = $bindingFactory->create($binding_type);
        $httpResponse = $postBinding->send($messageContext);

        //AUDIT SSO POST Interaction
//        app(EventAuditable::class)->audit(AuditEventType::SSO_LOGIN,[], $this->serializeSAMLForWS($messageContext));

        return $httpResponse->getContent();
    }

    /**
     * @param $context
     * @return array
     */
    private function serializeSAMLForWS($context): array
    {

        $messageEnvelop = MessageContextHelper::asSamlMessage($context);

        $serializationContext = $context->getSerializationContext();
        $messageEnvelop->serialize($serializationContext->getDocument(), $serializationContext);
        $msgStr = $serializationContext->getDocument()->saveXML();

        $msgStr = base64_encode($msgStr);

        $type = $messageEnvelop instanceof AbstractRequest ? 'SAMLRequest' : 'SAMLResponse';

        $data['success'] = true;
        if ($messageEnvelop->getRelayState()) {
            $data['RelayState'] = $messageEnvelop->getRelayState();
        }

        $data['id_token']= csrf_token();
        $data[$type] = $msgStr;
        $data['expires_at'] = config('session.lifetime')*60;
        return $data;
    }


    /**
     *  set the Destination endpoint to be redirect in SSO flow
     *  @return void
     */
    private function setDestination(): void
    {

        $destination = optional($this->getServiceProvider($this->authn_request))->acs_callback;

        $queryParams = $this->getQueryParams();
        if (!empty($queryParams)) {
            $destination = Str::finish(url($destination), '?') . Arr::query($queryParams);
        }

        $this->destination = $destination;
    }

    private function getQueryParams()
    {
        return [
            'idp' => config('app.url')
        ];
    }

    /**
     * set certificate of the service provider
     * @return void
     */
    public function setSpCertificate(): void
    {
        $sp = $this->getServiceProvider($this->authn_request);
        if ($sp) {
            $this->sp_certificate = $sp->certificate;
        }

    }

    /**
     * Get service provider from AuthNRequest
     *
     * @param $request
     * @return ServiceProvider
     */
    public function getServiceProvider($request)
    {

        $serviceProvider = Application::findByEntityId(base64_encode($request->getAssertionConsumerServiceURL()));
        if (!$serviceProvider) {
            throw new AccessDeniedHttpException();
        }

        return $serviceProvider;
    }
}
