<?php

namespace elseym\ShelfBundle\Controller;

class ShelfController
{
    /** @var $agathe \elseym\AgatheBundle\Agathe */
    private $agathe;

    /** @var $templating \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface */
    private $templating;

    /** @var $em \Doctrine\ORM\EntityManager */
    private $em;

    function __construct($agathe, $templating, $em) {
        $this->agathe = $agathe;
        $this->templating = $templating;
        $this->em = $em;
    }

    public function indexAction()
    {
        $this->agathe->doUserSetup();

        $templateArgs = array(
            "books" => $this->em->getRepository('elseymShelfBundle:Book')->findAll()
        );

        return $this->templating->renderResponse('elseymShelfBundle:Default:index.html.twig', $templateArgs);
    }

    public function getBooksAction() {

    }

    public function getBookAction($slug) {

    }

    public function addBookAction() {

    }

    public function editBookAction() {

    }

    public function saveBookAction() {

    }

    public function removeBookAction($slug) {

    }
}