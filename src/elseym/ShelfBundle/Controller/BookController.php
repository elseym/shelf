<?php

namespace elseym\ShelfBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use elseym\ShelfBundle\Entity\Book;
use elseym\ShelfBundle\Form\BookType;

/**
 * Book controller.
 *
 */
class BookController
{
    /** @var $agathe \elseym\AgatheBundle\Agathe */
    private $agathe;

    /** @var $templating \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface */
    private $templating;

    /** @var $em \Doctrine\ORM\EntityManager */
    private $em;

    /** @var $formFactory \Symfony\Component\Form\FormFactory */
    private $formFactory;

    /** @var $router \Symfony\Bundle\FrameworkBundle\Routing\Router */
    private $router;

    public function __construct($agathe, $templating, $em, $formFactory, $router) {
        $this->agathe = $agathe;
        $this->templating = $templating;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    /**
     * Lists all Book entities.
     *
     */
    public function indexAction()
    {
        $entities = $this->em->getRepository('elseymShelfBundle:Book')->findAll();

        return $this->templating->renderResponse('elseymShelfBundle:Book:index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Finds and displays a Book entity.
     *
     */
    public function showAction($slug)
    {
        $entity = $this->em->getRepository('elseymShelfBundle:Book')->findOneBySlug($slug);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find Book entity.');
        }

        $this->agathe->resourceRequested($entity);

        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->templating->renderResponse('elseymShelfBundle:Book:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to create a new Book entity.
     *
     */
    public function newAction()
    {
        $entity = new Book();
        $form   = $this->formFactory->create(new BookType(), $entity);

        return $this->templating->renderResponse('elseymShelfBundle:Book:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a new Book entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Book();
        $form = $this->formFactory->create(new BookType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $this->agathe->resourceCreated($entity);

            return new RedirectResponse($this->router->generate('book_show', array('slug' => $entity->getSlug())), 302);
        }

        return $this->templating->renderResponse('elseymShelfBundle:Book:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Book entity.
     *
     */
    public function editAction($id)
    {
        $entity = $this->em->getRepository('elseymShelfBundle:Book')->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find Book entity.');
        }

        $editForm = $this->formFactory->create(new BookType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->templating->renderResponse('elseymShelfBundle:Book:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Book entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $entity = $this->em->getRepository('elseymShelfBundle:Book')->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find Book entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->formFactory->create(new BookType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $this->agathe->resourceModified($entity);
            return new RedirectResponse($this->router->generate('book_edit', array('id' => $id)));
        }

        return $this->templating->renderResponse('elseymShelfBundle:Book:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Book entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $entity = $this->em->getRepository('elseymShelfBundle:Book')->find($id);

            if (!$entity) {
                throw new NotFoundHttpException('Unable to find Book entity.');
            }

            $this->em->remove($entity);
            $this->em->flush();

            $this->agathe->resourceDeleted($entity);
        }

        return new RedirectResponse($this->router->generate('book'));
    }

    private function createDeleteForm($id)
    {
        return $this->formFactory->createBuilder('form', array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
