<?php

namespace elseym\AgatheBundle\EventListener;

use \elseym\AgatheBundle\Agathe;
use \Symfony\Component\HttpFoundation\Session\Session;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SetupListener
{
    private $agathe;
    private $session;

    public function __construct(Agathe $agathe, Session $session) {
        $this->agathe = $agathe;
        $this->session = $session;
    }

    public function onRequest(GetResponseEvent $responseEvent) {
        if ($responseEvent->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            if ($this->session->has("setupComplete")
                && true === $this->session->get("setupComplete", false)) {
                $this->agathe->needsSetup();
            } else {
                $this->agathe->needsSetup();
            }
        }
    }
}