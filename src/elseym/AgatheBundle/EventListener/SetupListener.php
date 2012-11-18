<?php

namespace elseym\AgatheBundle\EventListener;

use \elseym\AgatheBundle\Agathe;
use \Symfony\Component\HttpFoundation\Session\Session;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use \Symfony\Component\Security\Core\SecurityContextInterface;

class SetupListener
{
    private $agathe;
    private $session;
    private $security;

    public function __construct(Agathe $agathe, Session $session, SecurityContextInterface $security) {
        $this->agathe = $agathe;
        $this->session = $session;
        $this->security = $security;
    }

    public function onRequest(GetResponseEvent $responseEvent) {
        $request = $responseEvent->getRequest();

        if ($responseEvent->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            if ($this->session->has("setupComplete")
                && true === $this->session->get("setupComplete", false)) {
                // gut
            } else {
                $token = $this->security->getToken();
                $user = null;

                if (!is_null($token)) {
                    $user = $token->getUser();
                }

                $this->session->set("setupComplete", $this->agathe->registerNewUser($user));
            }
        }
    }
}